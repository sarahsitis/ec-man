<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Membership;
use App\Models\Score;
use App\Models\Student;
use App\Models\User;
use App\Services\AssessmentService;
use App\Services\DataImportService;
use App\Services\DataTransferSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DataTransferTest extends TestCase
{
    use RefreshDatabase;

    private User $pembina;

    private AcademicYear $year;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo(Carbon::parse('2026-10-11 12:00:00', 'Asia/Jakarta'));
        $this->pembina = User::factory()->create(['role' => 'pembina']);
        $this->year = AcademicYear::create(['name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true]);
        $this->student = $this->member('000123');
        Membership::create(['student_id' => $this->student->id, 'academic_year_id' => $this->year->id, 'class_name' => 'X PPLG 1', 'status' => 'active']);
    }

    private function member(string $nis): Student
    {
        $user = User::factory()->create(['username' => $nis, 'role' => 'siswa']);

        return Student::create(['user_id' => $user->id, 'student_number' => $nis, 'full_name' => 'Siswa '.$nis, 'joined_year' => 2026, 'class_name' => 'X PPLG 1', 'status' => 'active']);
    }

    private function activity(string $date = '2026-10-09', string $status = 'completed'): Activity
    {
        return Activity::create(['academic_year_id' => $this->year->id, 'title' => 'Latihan Speaking', 'category' => 'conversation', 'activity_date' => $date,
            'start_time' => '15:00', 'status' => $status, 'created_by' => $this->pembina->id]);
    }

    private function csv(string $dataset, array $rows): UploadedFile
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, DataTransferSchema::COLUMNS[$dataset], ',', '"', '');
        foreach ($rows as $row) {
            fputcsv($handle, array_map(fn ($column) => DataTransferSchema::csvCell($row[$column] ?? ''), DataTransferSchema::COLUMNS[$dataset]), ',', '"', '');
        }
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return UploadedFile::fake()->createWithContent('data.csv', $content);
    }

    private function preview(string $dataset, array $rows, string $mode = 'create')
    {
        return $this->actingAs($this->pembina)->from('/laporan/arsip')->post('/laporan/arsip/pratinjau', [
            'dataset' => $dataset, 'mode' => $mode, 'file' => $this->csv($dataset, $rows),
        ]);
    }

    private function studentLetterheadCsv(array $rows = []): string
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "DAFTAR PESERTA EKTRAKULIKULER ENGLISH CLUB;;;;;;;\r\nSMKN 1 CIANJUR;;;;;;;\r\n;;;;;;;\r\nNIS;NAMA_LENGKAP;TAHUN_BERGABUNG;KELAS;STATUS;TELEPON;EMAIL;ALAMAT\r\n");
        foreach ($rows as $row) {
            fputcsv($handle, array_map(fn ($column) => $row[$column] ?? '', DataTransferSchema::COLUMNS['students']), ';', '"', '', "\r\n");
        }
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return $content;
    }

    public function test_inline_import_returns_json_preview_and_saves_each_dataset_without_redirecting(): void
    {
        $activity = $this->activity();
        $otherYear = AcademicYear::create(['name' => '2026/2027', 'semester' => 'genap']);
        $membership = ['nis' => $this->student->student_number, 'nama_lengkap' => $this->student->full_name,
            'tahun_ajaran' => $otherYear->name, 'semester' => $otherYear->semester, 'kelas' => 'X PPLG 1', 'status' => 'active'];
        foreach (['students' => $this->studentRow(), 'memberships' => $membership, 'attendances' => $this->attendanceRow($activity), 'scores' => $this->scoreRow()] as $dataset => $row) {
            $response = $this->actingAs($this->pembina)->from('/students')->post('/laporan/arsip/pratinjau', [
                'dataset' => $dataset, 'mode' => 'create', 'file' => $this->csv($dataset, [$row]),
            ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('preview.dataset', $dataset)
                ->assertJsonPath('preview.counts.create', 1)->assertJsonPath('preview.rows.0.values.nis', $row['nis'])
                ->assertJsonPath('headings.nis', 'Nomor Induk Siswa')->assertJsonPath('preview.errors', []);
            $token = $response->json('preview.token');
            if ($dataset === 'students') {
                $this->assertDatabaseMissing('students', ['student_number' => '000456']);
            }
            if ($dataset === 'scores') {
                $this->postJson('/laporan/arsip/impor', ['token' => $token])->assertUnprocessable()->assertJsonValidationErrors('approve_scores');
                $this->assertDatabaseCount('scores', 0);
            }
            $this->postJson('/laporan/arsip/impor', ['token' => $token, 'approve_scores' => true])->assertOk()
                ->assertJsonPath('counts.create', 1)->assertJsonPath('counts.update', 0)->assertJsonPath('counts.skip', 0)
                ->assertJsonStructure(['message']);
            $this->postJson('/laporan/arsip/impor', ['token' => $token, 'approve_scores' => true])->assertUnprocessable()->assertJsonValidationErrors('import');
        }
        $this->assertDatabaseHas('students', ['student_number' => '000456']);
        $this->assertDatabaseHas('memberships', ['academic_year_id' => $otherYear->id, 'student_id' => $this->student->id]);
        $this->assertDatabaseHas('attendances', ['activity_id' => $activity->id, 'status' => 'hadir']);
        $this->assertDatabaseCount('scores', 1);
    }

    public function test_inline_import_validation_cancellation_and_permissions_are_enforced(): void
    {
        $payload = ['dataset' => 'students', 'mode' => 'create', 'file' => $this->csv('students', [$this->studentRow()])];
        $this->post('/laporan/arsip/pratinjau', $payload, ['Accept' => 'application/json'])->assertUnauthorized();
        $this->actingAs($this->student->user)->post('/laporan/arsip/pratinjau', $payload, ['Accept' => 'application/json'])->assertForbidden();
        $this->actingAs($this->pembina)->post('/laporan/arsip/pratinjau', [
            'dataset' => 'students', 'mode' => 'create', 'file' => UploadedFile::fake()->createWithContent('bad.csv', 'nis,nama'),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('file');
        $payload['file'] = $this->csv('students', [[...$this->studentRow(), 'status' => 'invalid']]);
        $response = $this->post('/laporan/arsip/pratinjau', $payload, ['Accept' => 'application/json'])->assertOk()
            ->assertJsonPath('preview.errors.0.row', 2);
        $this->postJson('/laporan/arsip/impor', ['token' => $response->json('preview.token')])->assertUnprocessable()->assertJsonValidationErrors('import');
        $this->deleteJson('/laporan/arsip/pratinjau')->assertNoContent();
        $this->postJson('/laporan/arsip/impor', ['token' => $response->json('preview.token')])->assertUnprocessable()->assertJsonValidationErrors('import');
        $this->assertDatabaseCount('students', 1);
    }

    public function test_direct_student_export_works_without_an_academic_year_and_respects_class_filter(): void
    {
        $this->member('OTHER')->update(['class_name' => 'XI PPLG - RPL 1']);
        Membership::query()->delete();
        $this->year->delete();
        $response = $this->actingAs($this->pembina)->get('/laporan/arsip/ekspor?dataset=students&period=semester')->assertOk()->assertDownload();
        $rows = (new DataImportService)->read(UploadedFile::fake()->createWithContent('export.csv', $response->streamedContent()), 'students');
        $this->assertCount(2, $rows);
        $response = $this->get('/laporan/arsip/ekspor?'.http_build_query(['dataset' => 'students', 'class_name' => 'X PPLG 1']))->assertOk();
        $rows = (new DataImportService)->read(UploadedFile::fake()->createWithContent('export.csv', $response->streamedContent()), 'students');
        $this->assertCount(1, $rows);
        $this->assertSame('000123', $rows[0]['nis']);
        $this->from('/students')->get('/laporan/arsip/ekspor?dataset=memberships')->assertSessionHasErrors('academic_year_id');
    }

    public function test_student_template_matches_the_supplied_letterhead_and_uppercase_semicolon_columns(): void
    {
        $response = $this->actingAs($this->pembina)->get('/laporan/arsip/template?dataset=students')
            ->assertOk()->assertDownload('template-students.csv');
        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $actual = array_map(fn ($line) => str_getcsv($line, ';', '"', ''), explode("\r\n", substr($content, 3)));
        $expected = array_map(fn ($line) => str_getcsv($line, ';', '"', ''), explode("\r\n", $this->studentLetterheadCsv()));
        $this->assertSame($expected, $actual);

    }

    public function test_every_import_template_has_a_separate_letterhead_blank_row_and_column_row(): void
    {
        foreach ([
            'students' => 'DAFTAR PESERTA EKTRAKULIKULER ENGLISH CLUB',
            'memberships' => 'DAFTAR KEANGGOTAAN EKSTRAKURIKULER ENGLISH CLUB',
            'attendances' => 'REKAP PRESENSI EKSTRAKURIKULER ENGLISH CLUB',
            'scores' => 'DAFTAR NILAI RESMI EKSTRAKURIKULER ENGLISH CLUB',
        ] as $dataset => $title) {
            $response = $this->actingAs($this->pembina)->get('/laporan/arsip/template?dataset='.$dataset)
                ->assertOk()->assertDownload('template-'.$dataset.'.csv');
            $content = $response->streamedContent();
            $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
            $records = array_map(fn ($line) => str_getcsv($line, ';', '"', ''), explode("\r\n", substr($content, 3)));
            $width = count(DataTransferSchema::COLUMNS[$dataset]);
            $this->assertSame(array_pad([$title], $width, ''), $records[0]);
            $this->assertSame(array_pad(['SMKN 1 CIANJUR'], $width, ''), $records[1]);
            $this->assertSame(array_fill(0, $width, ''), $records[2]);
            $this->assertSame(array_map('strtoupper', DataTransferSchema::COLUMNS[$dataset]), $records[3]);
            $this->assertSame([null], $records[4]);
        }
        config(['reports.school_name' => 'Sekolah; "Contoh"']);
        $content = $this->get('/laporan/arsip/template?dataset=memberships')->streamedContent();
        $this->assertSame('Sekolah; "Contoh"', str_getcsv(explode("\r\n", substr($content, 3))[1], ';', '"', '')[0]);
    }

    public function test_all_downloaded_templates_can_be_filled_previewed_and_imported_with_correct_row_numbers(): void
    {
        $otherYear = AcademicYear::create(['name' => '2026/2027', 'semester' => 'genap']);
        $activity = $this->activity();
        $membership = ['nis' => $this->student->student_number, 'nama_lengkap' => $this->student->full_name,
            'tahun_ajaran' => $otherYear->name, 'semester' => $otherYear->semester, 'kelas' => 'X PPLG 1', 'status' => 'active'];
        foreach (['students' => $this->studentRow(), 'memberships' => $membership, 'attendances' => $this->attendanceRow($activity), 'scores' => $this->scoreRow()] as $dataset => $row) {
            $template = $this->actingAs($this->pembina)->get('/laporan/arsip/template?dataset='.$dataset)->streamedContent();
            $handle = fopen('php://temp', 'r+');
            fwrite($handle, $template);
            fputcsv($handle, array_fill(0, count($row), ''), ';', '"', '', "\r\n");
            fputcsv($handle, array_map(fn ($column) => DataTransferSchema::csvCell($row[$column]), DataTransferSchema::COLUMNS[$dataset]), ';', '"', '', "\r\n");
            rewind($handle);
            $file = UploadedFile::fake()->createWithContent('template-'.$dataset.'.csv', stream_get_contents($handle));
            fclose($handle);
            $response = $this->post('/laporan/arsip/pratinjau', ['dataset' => $dataset, 'mode' => 'create', 'file' => $file], ['Accept' => 'application/json'])
                ->assertOk()->assertJsonPath('preview.errors', [])->assertJsonPath('preview.total', 1)
                ->assertJsonPath('preview.rows.0.row', 6)->assertJsonPath('preview.rows.0.values.nis', $row['nis'])
                ->assertJsonPath('preview.counts.create', 1);
            $this->postJson('/laporan/arsip/impor', ['token' => $response->json('preview.token'), 'approve_scores' => true])
                ->assertOk()->assertJsonPath('counts.create', 1);
        }
        $this->assertDatabaseHas('students', ['student_number' => '000456', 'phone' => '+628123456']);
        $this->assertDatabaseHas('memberships', ['academic_year_id' => $otherYear->id, 'student_id' => $this->student->id]);
        $this->assertDatabaseHas('attendances', ['activity_id' => $activity->id, 'status' => 'hadir']);
        $this->assertDatabaseCount('scores', 1);
    }

    public function test_letterheads_for_every_dataset_reject_wrong_columns_school_rows_and_wrong_dataset_titles(): void
    {
        foreach (array_keys(DataTransferSchema::LABELS) as $dataset) {
            $template = $this->actingAs($this->pembina)->get('/laporan/arsip/template?dataset='.$dataset)->streamedContent();
            $columns = DataTransferSchema::COLUMNS[$dataset];
            $data = implode(';', array_fill(0, count($columns), 'data'))."\r\n";
            foreach ([
                $template,
                str_replace('NAMA_LENGKAP', 'KOLOM_SALAH', $template).$data,
                str_replace('"SMKN 1 CIANJUR";', '"SMKN 1 CIANJUR";isi tambahan', $template).$data,
                str_replace(DataTransferSchema::TEMPLATE_TITLES[$dataset], DataTransferSchema::TEMPLATE_TITLES[$dataset === 'students' ? 'memberships' : 'students'], $template).$data,
            ] as $content) {
                $this->post('/laporan/arsip/pratinjau', [
                    'dataset' => $dataset, 'mode' => 'create', 'file' => UploadedFile::fake()->createWithContent('bad.csv', $content),
                ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('file');
            }
        }
        $this->assertDatabaseCount('students', 1);
        $this->assertDatabaseCount('memberships', 1);
        $this->assertDatabaseCount('attendances', 0);
        $this->assertDatabaseCount('scores', 0);
    }

    public function test_student_letterhead_import_preserves_row_numbers_values_and_commits_after_preview(): void
    {
        $row = $this->studentRow();
        $row['alamat'] = "Jalan Merdeka; No. 1\nCianjur";
        $content = $this->studentLetterheadCsv([[], $row]);
        $this->actingAs($this->pembina)->from('/laporan/arsip')->post('/laporan/arsip/pratinjau', [
            'dataset' => 'students', 'mode' => 'create', 'file' => UploadedFile::fake()->createWithContent('students.csv', $content),
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('students', ['student_number' => '000456']);
        $this->get('/laporan/arsip')->assertInertia(fn (Assert $page) => $page
            ->where('importPreview.total', 1)->where('importPreview.rows.0.row', 6)
            ->where('importPreview.rows.0.values.nis', '000456')->where('importPreview.rows.0.values.telepon', '+628123456')
            ->where('importPreview.rows.0.values.alamat', $row['alamat']));
        $this->commit()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('students', ['student_number' => '000456', 'address' => $row['alamat']]);

        // The downloaded template is also importable after adding a row, including its UTF-8 BOM.
        $template = $this->get('/laporan/arsip/template?dataset=students')->streamedContent();
        $data = substr($this->studentLetterheadCsv([$this->studentRow('000789')]), strlen($this->studentLetterheadCsv()));
        $this->post('/laporan/arsip/pratinjau', [
            'dataset' => 'students', 'mode' => 'create', 'file' => UploadedFile::fake()->createWithContent('downloaded.csv', $template.$data),
        ])->assertSessionHasNoErrors();
        $this->get('/laporan/arsip')->assertInertia(fn (Assert $page) => $page
            ->where('importPreview.rows.0.row', 5)->where('importPreview.rows.0.values.nis', '000789'));
        $this->commit()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('students', ['student_number' => '000789']);
    }

    public function test_invalid_student_letterhead_rows_report_the_spreadsheet_row_and_block_import(): void
    {
        $content = $this->studentLetterheadCsv([
            $this->studentRow(), [], $this->studentRow('000789'),
            [...$this->studentRow('000999'), 'status' => 'invalid'],
            $this->studentRow(),
        ]);
        $this->actingAs($this->pembina)->from('/laporan/arsip')->post('/laporan/arsip/pratinjau', [
            'dataset' => 'students', 'mode' => 'create', 'file' => UploadedFile::fake()->createWithContent('invalid.csv', $content),
        ])->assertSessionHasNoErrors();
        $this->get('/laporan/arsip')->assertInertia(fn (Assert $page) => $page
            ->where('importPreview.total', 4)->where('importPreview.rows.0.row', 5)->where('importPreview.rows.1.row', 7)
            ->where('importPreview.errors.0.row', 8)->where('importPreview.errors.1.row', 9));
        $this->commit()->assertSessionHasErrors('import');
        $this->assertDatabaseCount('students', 1);
    }

    public function test_letterhead_does_not_allow_wrong_headers_other_datasets_or_skipping_partial_data(): void
    {
        foreach ([
            $this->studentLetterheadCsv(),
            str_replace('NAMA_LENGKAP', 'KOLOM_SALAH', $this->studentLetterheadCsv([$this->studentRow()])),
            str_replace('SMKN 1 CIANJUR;;;;;;;', 'SMKN 1 CIANJUR;isi tambahan;;;;;;', $this->studentLetterheadCsv([$this->studentRow()])),
            str_replace('DAFTAR PESERTA EKTRAKULIKULER ENGLISH CLUB', 'Judul tidak sesuai', $this->studentLetterheadCsv([$this->studentRow()])),
        ] as $content) {
            $this->actingAs($this->pembina)->from('/laporan/arsip')->post('/laporan/arsip/pratinjau', [
                'dataset' => 'students', 'mode' => 'create', 'file' => UploadedFile::fake()->createWithContent('bad.csv', $content),
            ])->assertSessionHasErrors('file');
        }
        $this->post('/laporan/arsip/pratinjau', [
            'dataset' => 'memberships', 'mode' => 'create', 'file' => UploadedFile::fake()->createWithContent('wrong-dataset.csv', $this->studentLetterheadCsv([$this->studentRow()])),
        ])->assertSessionHasErrors('file');
        $this->post('/laporan/arsip/pratinjau', [
            'dataset' => 'students', 'mode' => 'create', 'file' => UploadedFile::fake()->createWithContent('partial.csv', $this->studentLetterheadCsv([['nama_lengkap' => 'Tanpa NIS']])),
        ])->assertSessionHasNoErrors();
        $this->assertSame(5, session('data_import_preview.plan.errors.0.row'));
        $this->assertDatabaseCount('students', 1);
    }

    private function commit(array $extra = [])
    {
        return $this->actingAs($this->pembina)->from('/laporan/arsip')->post('/laporan/arsip/impor', ['token' => session('data_import_preview.token'), ...$extra]);
    }

    private function studentRow(string $nis = '000456', string $name = 'Siswa Baru'): array
    {
        return ['nis' => $nis, 'nama_lengkap' => $name, 'tahun_bergabung' => 2026, 'kelas' => 'X PPLG 1', 'status' => 'active', 'telepon' => '+628123456', 'email' => 'siswa@example.test', 'alamat' => "Jalan Merdeka, No. 1\nKota"];
    }

    private function attendanceRow(Activity $activity, ?Student $student = null): array
    {
        $student ??= $this->student;

        return ['nis' => $student->student_number, 'nama_lengkap' => $student->full_name, 'kegiatan_id' => $activity->id,
            'tanggal_kegiatan' => $activity->activity_date->toDateString(), 'judul_kegiatan' => $activity->title,
            'tahun_ajaran' => $this->year->name, 'semester' => $this->year->semester, 'status' => 'hadir'];
    }

    private function scoreRow(string $title = 'Arsip Speaking'): array
    {
        return ['nis' => $this->student->student_number, 'nama_lengkap' => $this->student->full_name, 'judul_penilaian' => $title,
            'aspek' => 'speaking', 'nilai' => 3, 'tanggal_pengesahan' => '2026-10-10 14:00:00', 'umpan_balik' => 'Latihan artikulasi.', 'catatan_pembina' => 'Disahkan dari arsip semester.'];
    }

    public function test_every_endpoint_requires_pembina_and_guests_must_login(): void
    {
        $endpoints = [
            ['get', '/laporan/arsip'], ['get', '/laporan/arsip/ekspor'], ['get', '/laporan/arsip/template'], ['get', '/laporan/arsip/cetak'],
            ['post', '/laporan/arsip/pratinjau'], ['post', '/laporan/arsip/impor'], ['delete', '/laporan/arsip/pratinjau'],
        ];
        foreach ($endpoints as [$method, $url]) {
            $this->{$method}($url)->assertRedirect('/login');
        }
        foreach (['siswa', 'panitia'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            foreach ($endpoints as [$method, $url]) {
                $this->actingAs($user)->{$method}($url)->assertForbidden();
            }
        }
    }

    public function test_periods_use_monday_sunday_calendar_month_and_semester_boundaries(): void
    {
        foreach ([['week', '2026-10-11', '2026-10-05', '2026-10-11'], ['month', '2026-10-11', '2026-10-01', '2026-10-31'],
            ['semester', '2026-10-11', '2026-07-01', '2026-12-31'], ['week', '2026-07-01', '2026-07-01', '2026-07-05']] as [$kind, $date, $from, $until]) {
            $this->actingAs($this->pembina)->get('/laporan/arsip?'.http_build_query(['period' => $kind, 'date' => $date, 'academic_year_id' => $this->year->id]))
                ->assertOk()->assertInertia(fn (Assert $page) => $page->where('period.from', $from)->where('period.until', $until));
        }
        $even = AcademicYear::create(['name' => '2027/2028', 'semester' => 'genap']);
        $this->get('/laporan/arsip?'.http_build_query(['period' => 'month', 'date' => '2028-02-10', 'academic_year_id' => $even->id]))
            ->assertInertia(fn (Assert $page) => $page->where('period.from', '2028-02-01')->where('period.until', '2028-02-29'));
        $this->from('/laporan/arsip')->get('/laporan/arsip?period=month&date=2027-01-01')->assertSessionHasErrors('date');
    }

    public function test_student_export_includes_unenrolled_master_data_and_all_rows_beyond_preview(): void
    {
        for ($i = 0; $i < 22; $i++) {
            $this->member('EX'.str_pad((string) $i, 3, '0', STR_PAD_LEFT));
        }
        $this->actingAs($this->pembina)->get('/laporan/arsip')->assertInertia(fn (Assert $page) => $page
            ->where('section.summary.Jumlah Data', 23)->has('section.rows', 20));
        $response = $this->get('/laporan/arsip/ekspor?dataset=students')->assertOk()->assertDownload();
        $rows = (new DataImportService)->read(UploadedFile::fake()->createWithContent('export.csv', $response->streamedContent()), 'students');
        $this->assertCount(23, $rows);
        $this->assertContains('000123', array_column($rows, 'nis'));
        $this->assertStringContainsString("'000123", $response->streamedContent());
        $this->assertStringNotContainsString('password', $response->streamedContent());
    }

    public function test_attendance_report_uses_activity_dates_excludes_cancellations_and_does_not_invent_absence(): void
    {
        foreach ([['2026-10-05', 'completed', 'hadir'], ['2026-10-11', 'completed', 'belum_diisi'], ['2026-10-12', 'completed', 'alpa'], ['2026-10-08', 'cancelled', 'hadir']] as [$date, $status, $attendance]) {
            $activity = $this->activity($date, $status);
            Attendance::create(['activity_id' => $activity->id, 'student_id' => $this->student->id, 'status' => $attendance, 'recorded_by' => $this->pembina->id]);
        }
        $this->member('NO-ATTENDANCE');
        $this->actingAs($this->pembina)->get('/laporan/arsip?dataset=attendances&period=week&date=2026-10-11')->assertInertia(fn (Assert $page) => $page
            ->has('section.rows', 2)->where('section.summary.Hadir', 1)->where('section.summary.Alpa', 0)->where('section.summary.Persentase Kehadiran', '100%'));
    }

    public function test_membership_export_filters_semester_and_snapshot_class(): void
    {
        $otherYear = AcademicYear::create(['name' => '2026/2027', 'semester' => 'genap']);
        Membership::create(['student_id' => $this->student->id, 'academic_year_id' => $otherYear->id, 'class_name' => 'XI PPLG - RPL 1', 'status' => 'active']);
        $this->student->update(['class_name' => 'XI PPLG - RPL 1']);
        $response = $this->actingAs($this->pembina)->get('/laporan/arsip/ekspor?dataset=memberships&class_name='.urlencode('X PPLG 1'))->assertOk();
        $rows = (new DataImportService)->read(UploadedFile::fake()->createWithContent('export.csv', $response->streamedContent()), 'memberships');
        $this->assertCount(1, $rows);
        $this->assertSame('ganjil', $rows[0]['semester']);
        $this->assertSame('X PPLG 1', $rows[0]['kelas']);
    }

    public function test_student_preview_does_not_write_and_confirmation_is_single_use(): void
    {
        $this->preview('students', [$this->studentRow()])->assertStatus(303)->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('students', ['student_number' => '000456']);
        $token = session('data_import_preview.token');
        $this->get('/laporan/arsip')->assertInertia(fn (Assert $page) => $page->where('importPreview.counts.create', 1)->where('importPreview.rows.0.values.nis', '000456'));
        $this->commit()->assertSessionHasNoErrors();
        $student = Student::where('student_number', '000456')->firstOrFail();
        $this->assertSame('+628123456', $student->phone);
        $this->assertSame("Jalan Merdeka, No. 1\nKota", $student->address);
        $this->assertSame('siswa', $student->user->role);
        $this->assertTrue(Hash::check('000456', $student->user->password));
        $this->post('/laporan/arsip/impor', ['token' => $token])->assertSessionHasErrors('import');
    }

    public function test_invalid_duplicate_and_conflicting_rows_block_entire_import(): void
    {
        $bad = $this->studentRow('000789');
        $bad['kelas'] = 'Kelas tidak valid';
        $this->preview('students', [$this->studentRow(), $bad])->assertSessionHasNoErrors();
        $this->assertCount(1, session('data_import_preview.plan.errors'));
        $this->commit()->assertSessionHasErrors('import');
        $this->assertDatabaseMissing('students', ['student_number' => '000456']);
        $this->preview('students', [$this->studentRow(), $this->studentRow()]);
        $this->assertCount(1, session('data_import_preview.plan.errors'));
        $this->commit()->assertSessionHasErrors('import');
        $this->preview('students', [$this->studentRow('000123', 'Nama Berbeda')]);
        $this->assertCount(1, session('data_import_preview.plan.errors'));
        $this->assertSame('Siswa 000123', $this->student->fresh()->full_name);
    }

    public function test_explicit_student_update_preserves_password_role_photo_and_rechecks_changes(): void
    {
        $this->student->user->update(['role' => 'panitia']);
        $this->student->update(['profile_photo_path' => 'private/photo.jpg']);
        $password = $this->student->user->fresh()->password;
        $this->preview('students', [$this->studentRow('000123', 'Nama Diperbarui')], 'update')->assertSessionHasNoErrors();
        $this->commit()->assertSessionHasNoErrors();
        $student = $this->student->fresh();
        $this->assertSame('Nama Diperbarui', $student->full_name);
        $this->assertSame($password, $student->user->password);
        $this->assertSame('panitia', $student->user->role);
        $this->assertSame('private/photo.jpg', $student->profile_photo_path);
        $this->preview('students', [$this->studentRow('000123', 'Versi Impor')], 'update');
        $this->student->update(['full_name' => 'Perubahan setelah pratinjau']);
        $this->commit()->assertSessionHasErrors('import');
        $this->assertSame('Perubahan setelah pratinjau', $this->student->fresh()->full_name);
    }

    public function test_membership_import_checks_existing_years_and_student_eligibility(): void
    {
        $new = $this->member('000456');
        $row = ['nis' => $new->student_number, 'nama_lengkap' => $new->full_name, 'tahun_ajaran' => '2026/2027', 'semester' => 'ganjil', 'kelas' => 'X PPLG 1', 'status' => 'active'];
        $this->preview('memberships', [$row])->assertSessionHasNoErrors();
        $this->commit()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('memberships', ['student_id' => $new->id, 'academic_year_id' => $this->year->id]);
        $this->preview('memberships', [$row]);
        $this->assertSame(1, session('data_import_preview.plan.counts.skip'));
        $row['tahun_ajaran'] = '2028/2029';
        $this->preview('memberships', [$row]);
        $this->assertCount(1, session('data_import_preview.plan.errors'));
        $new->update(['status' => 'inactive']);
        $row['tahun_ajaran'] = '2026/2027';
        $row['kelas'] = 'XI PPLG - RPL 1';
        $this->preview('memberships', [$row], 'update');
        $this->assertCount(1, session('data_import_preview.plan.errors'));
    }

    public function test_attendance_import_respects_roster_metadata_and_historical_corrections(): void
    {
        $activity = $this->activity();
        $outsider = $this->member('OUT');
        $this->preview('attendances', [$this->attendanceRow($activity), $this->attendanceRow($activity, $outsider)]);
        $this->commit()->assertSessionHasErrors('import');
        $this->assertDatabaseCount('attendances', 0);
        $this->preview('attendances', [$this->attendanceRow($activity)]);
        $this->commit()->assertSessionHasNoErrors();
        $this->student->update(['status' => 'alumni']);
        $row = $this->attendanceRow($activity);
        $row['status'] = 'izin';
        $this->preview('attendances', [$row], 'update');
        $this->commit()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('attendances', ['activity_id' => $activity->id, 'student_id' => $this->student->id, 'status' => 'izin', 'recorded_by' => $this->pembina->id]);
        $row['judul_kegiatan'] = 'ID keliru';
        $this->preview('attendances', [$row], 'update');
        $this->assertCount(1, session('data_import_preview.plan.errors'));
        $activity->update(['status' => 'cancelled']);
        $this->preview('attendances', [$this->attendanceRow($activity)], 'update');
        $this->assertCount(1, session('data_import_preview.plan.errors'));
    }

    public function test_score_import_requires_approval_records_audit_and_is_idempotent_without_overwriting(): void
    {
        $row = $this->scoreRow();
        $this->preview('scores', [$row])->assertSessionHasNoErrors();
        $this->commit()->assertSessionHasErrors('approve_scores');
        $this->assertDatabaseCount('scores', 0);
        $this->commit(['approve_scores' => true])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('scores', ['student_id' => $this->student->id, 'value' => 3, 'approved_at' => '2026-10-10 07:00:00', 'approved_by' => $this->pembina->id]);
        $this->assertDatabaseHas('assessment_events', ['action' => 'imported', 'actor_id' => $this->pembina->id]);
        $this->actingAs($this->student->user)->get('/perkembangan-saya')->assertInertia(fn (Assert $page) => $page->where('report.total_scores', 1)->where('report.skills.1.average', 3));
        $this->preview('scores', [$row]);
        $this->assertSame(1, session('data_import_preview.plan.counts.skip'));
        $this->commit(['approve_scores' => true])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('scores', 1);
        $this->assertDatabaseCount('assessment_events', 1);
        $row['nilai'] = 4;
        $this->preview('scores', [$row]);
        $this->assertCount(1, session('data_import_preview.plan.errors'));
        $this->commit(['approve_scores' => true])->assertSessionHasErrors('import');
        $this->assertSame(3, Score::first()->value);
    }

    public function test_score_import_rejects_running_assignments_future_dates_and_invalid_scale(): void
    {
        $assessment = Assessment::create(['title' => 'Tugas berjalan', 'aspect' => 'speaking', 'rubric' => AssessmentService::rubric('speaking'), 'created_by' => $this->pembina->id]);
        $assessment->assignments()->create(['student_id' => $this->student->id, 'assessor_id' => $this->pembina->id, 'created_by' => $this->pembina->id,
            'title' => $assessment->title, 'aspect' => 'speaking', 'rubric' => $assessment->rubric, 'status' => 'submitted']);
        $running = $this->scoreRow('Tugas berjalan');
        $future = $this->scoreRow('Masa depan');
        $future['tanggal_pengesahan'] = '2026-11-10 14:00:00';
        $bad = $this->scoreRow('Skala salah');
        $bad['nilai'] = 99;
        $this->preview('scores', [$running, $future, $bad]);
        $this->assertCount(3, session('data_import_preview.plan.errors'));
        $this->commit(['approve_scores' => true])->assertSessionHasErrors('import');
        $this->assertDatabaseCount('scores', 0);
    }

    public function test_score_report_filters_inclusive_jakarta_dates_and_only_published_scores(): void
    {
        foreach ([['Awal minggu', '2026-10-05 00:00:00'], ['Akhir minggu', '2026-10-11 23:59:59'], ['Di luar', '2026-10-12 00:00:00']] as [$title, $date]) {
            $row = $this->scoreRow($title);
            $row['tanggal_pengesahan'] = '2026-10-10 00:00:00';
            $this->preview('scores', [$row]);
            $this->commit(['approve_scores' => true]);
            Score::whereHas('assessment', fn ($q) => $q->where('title', $title))->update(['approved_at' => Carbon::parse($date, 'Asia/Jakarta')->utc()]);
        }
        $this->actingAs($this->pembina)->get('/laporan/arsip?dataset=scores&period=week&date=2026-10-11')->assertInertia(fn (Assert $page) => $page
            ->has('section.rows', 2)->where('section.rows.0.tanggal_pengesahan', '2026-10-05 00:00:00')->where('section.rows.1.tanggal_pengesahan', '2026-10-11 23:59:59'));
    }

    public function test_all_exports_can_be_previewed_for_reimport_without_changing_data(): void
    {
        $activity = $this->activity();
        $this->preview('attendances', [$this->attendanceRow($activity)]);
        $this->commit();
        $this->preview('scores', [$this->scoreRow()]);
        $this->commit(['approve_scores' => true]);
        foreach (array_keys(DataTransferSchema::LABELS) as $dataset) {
            $response = $this->actingAs($this->pembina)->get('/laporan/arsip/ekspor?dataset='.$dataset)->assertOk();
            $this->from('/laporan/arsip')->post('/laporan/arsip/pratinjau', ['dataset' => $dataset, 'mode' => 'create', 'file' => UploadedFile::fake()->createWithContent('export.csv', $response->streamedContent())])->assertSessionHasNoErrors();
            $this->assertSame([], session('data_import_preview.plan.errors'), $dataset);
            $this->assertSame(1, session('data_import_preview.plan.counts.skip'), $dataset);
        }
    }

    public function test_expired_tampered_and_other_user_previews_cannot_be_committed(): void
    {
        $this->preview('students', [$this->studentRow()]);
        $this->post('/laporan/arsip/impor', ['token' => (string) Str::uuid()])->assertSessionHasErrors('import');
        $this->withSession(['data_import_preview' => [...session('data_import_preview'), 'expires_at' => time() - 1]])->commit()->assertSessionHasErrors('import');
        $this->preview('students', [$this->studentRow()]);
        $other = User::factory()->create(['role' => 'pembina']);
        $this->actingAs($other)->post('/laporan/arsip/impor', ['token' => session('data_import_preview.token')])->assertSessionHasErrors('import');
        $this->assertDatabaseMissing('students', ['student_number' => '000456']);
    }

    public function test_csv_formulas_are_neutralized_and_literal_text_restores_on_import(): void
    {
        $row = $this->studentRow('FORMULA', '=HYPERLINK("example.test")');
        $this->preview('students', [$row]);
        $this->commit()->assertSessionHasNoErrors();
        $export = $this->get('/laporan/arsip/ekspor?dataset=students')->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $export);
        $this->assertStringContainsString("'+628123456", $export);
        $rows = (new DataImportService)->read(UploadedFile::fake()->createWithContent('export.csv', $export), 'students');
        $imported = collect($rows)->firstWhere('nis', 'FORMULA');
        $this->assertSame($row['nama_lengkap'], $imported['nama_lengkap']);
        $this->assertSame('+628123456', $imported['telepon']);
        $this->assertSame("'=SUM(A1)", DataTransferSchema::importCell(DataTransferSchema::csvCell("'=SUM(A1)")));
    }

    public function test_bad_headers_encoding_empty_and_oversized_files_are_rejected(): void
    {
        foreach (["nis,nis\n1,2", "nis,nama\n1,\xFF", implode(',', DataTransferSchema::COLUMNS['students'])] as $content) {
            $this->actingAs($this->pembina)->from('/laporan/arsip')->post('/laporan/arsip/pratinjau', ['dataset' => 'students', 'mode' => 'create', 'file' => UploadedFile::fake()->createWithContent('bad.csv', $content)])->assertSessionHasErrors('file');
        }
        $this->post('/laporan/arsip/pratinjau', ['dataset' => 'students', 'mode' => 'create', 'file' => UploadedFile::fake()->create('big.csv', 5121, 'text/csv')])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('students', 1);
    }

    public function test_print_report_has_formal_metadata_four_sections_and_escapes_user_content(): void
    {
        $this->student->update(['full_name' => '<script>alert(1)</script>']);
        $this->actingAs($this->pembina)->get('/laporan/arsip/cetak?'.http_build_query(['dataset' => 'all', 'school_name' => 'SMK Contoh', 'signatory' => 'Nama Pembina', 'signatory_role' => 'Pembina EC']))
            ->assertOk()->assertSee('SMK Contoh')->assertSee('Nomor Dokumen')->assertSee('Data Siswa')->assertSee('Keanggotaan')->assertSee('Presensi')->assertSee('Nilai Resmi')
            ->assertSee('Nama Pembina')->assertSee('A4 landscape', false)->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_school_and_signatory_defaults_use_the_supplied_school_identity(): void
    {
        $this->actingAs($this->pembina)->get('/laporan/arsip')->assertInertia(fn (Assert $page) => $page
            ->where('filters.school_name', 'SMKN 1 CIANJUR')->where('filters.signatory', 'Sarah Siti Sumaerah, ST.')
            ->where('filters.signatory_role', 'Pembina Ekstrakurikuler English Club')->where('filters.signatory_nip', '199306302022212017'));
        $this->get('/laporan/arsip/cetak')->assertOk()->assertSee('SMKN 1 CIANJUR')->assertSee('Sarah Siti Sumaerah, ST.')
            ->assertSee('NIP 199306302022212017');
        $this->from('/laporan/arsip')->get('/laporan/arsip/cetak?signatory_nip=123')->assertSessionHasErrors('signatory_nip');
        $this->get('/laporan/arsip/cetak?signatory_nip=')->assertOk()->assertDontSee('199306302022212017');
    }

    public function test_a_failure_while_saving_rolls_back_the_entire_grade_archive(): void
    {
        $this->preview('scores', [$this->scoreRow('Arsip pertama'), $this->scoreRow('Arsip kedua')])->assertSessionHasNoErrors();
        $writes = 0;
        Score::creating(function () use (&$writes) {
            if (++$writes === 2) {
                throw ValidationException::withMessages(['import' => 'Simulasi kegagalan penyimpanan.']);
            }
        });
        $this->commit(['approve_scores' => true])->assertSessionHasErrors('import');
        $this->assertSame(2, $writes);
        $this->assertDatabaseCount('scores', 0);
        $this->assertDatabaseCount('assessments', 0);
        $this->assertDatabaseCount('assessment_assignments', 0);
        $this->assertDatabaseCount('assessment_events', 0);
    }

    public function test_templates_and_semicolon_csv_work_without_requiring_an_academic_year(): void
    {
        foreach (array_keys(DataTransferSchema::LABELS) as $dataset) {
            $this->actingAs($this->pembina)->get('/laporan/arsip/template?dataset='.$dataset)->assertOk()->assertDownload('template-'.$dataset.'.csv');
        }
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, DataTransferSchema::COLUMNS['students'], ';', '"', '');
        $row = $this->studentRow();
        fputcsv($handle, array_map(fn ($column) => $row[$column], DataTransferSchema::COLUMNS['students']), ';', '"', '');
        rewind($handle);
        $file = UploadedFile::fake()->createWithContent('semicolon.csv', stream_get_contents($handle));
        fclose($handle);
        $this->assertSame('000456', (new DataImportService)->read($file, 'students')[0]['nis']);
        Membership::query()->delete();
        $this->year->delete();
        $this->get('/laporan/arsip')->assertInertia(fn (Assert $page) => $page->where('period', null)->where('section', null));
        $this->preview('students', [$this->studentRow()])->assertSessionHasNoErrors();
        $this->commit()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('students', ['student_number' => '000456']);
    }
}
