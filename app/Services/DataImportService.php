<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\Attendance;
use App\Models\Membership;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DataImportService
{
    public const MAX_ROWS = 2000;

    public function read(UploadedFile $file, string $dataset): array
    {
        $content = file_get_contents($file->getRealPath());
        if (! mb_check_encoding($content, 'UTF-8') || str_contains($content, "\0")) {
            throw ValidationException::withMessages(['file' => 'File harus berupa CSV dengan encoding UTF-8.']);
        }
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, preg_replace('/^\xEF\xBB\xBF/', '', $content));
        rewind($handle);
        $firstLine = fgets($handle);
        $delimiter = count(str_getcsv($firstLine ?: '', ';', '"', '')) > count(str_getcsv($firstLine ?: '', ',', '"', '')) ? ';' : ',';
        rewind($handle);
        $headers = fgetcsv($handle, 0, $delimiter, '"', '');
        $sourceRow = 1;
        if ($headers && strtoupper(trim((string) $headers[0])) === DataTransferSchema::TEMPLATE_TITLES[$dataset]
            && $this->isBlankRow(array_slice($headers, 1))) {
            $school = fgetcsv($handle, 0, $delimiter, '"', '');
            $sourceRow++;
            if (! $school || trim((string) $school[0]) === '' || ! $this->isBlankRow(array_slice($school, 1))) {
                fclose($handle);
                throw ValidationException::withMessages(['file' => 'Kop template tidak sesuai. Gunakan template untuk jenis data yang dipilih.']);
            }
            do {
                $headers = fgetcsv($handle, 0, $delimiter, '"', '');
                $sourceRow++;
            } while ($headers !== false && $this->isBlankRow($headers));
        }
        $headers = $headers ? array_map(fn ($h) => strtolower(trim((string) $h)), $headers) : [];
        $expected = DataTransferSchema::COLUMNS[$dataset];
        if (count($headers) !== count(array_unique($headers)) || array_diff($expected, $headers) || array_diff($headers, $expected)) {
            fclose($handle);
            throw ValidationException::withMessages(['file' => 'Kolom CSV tidak sesuai. Gunakan template untuk jenis data yang dipilih.']);
        }
        $rows = [];
        while (($values = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
            $sourceRow++;
            if ($this->isBlankRow($values)) {
                continue;
            }
            if (count($rows) >= self::MAX_ROWS || count($values) !== count($headers)) {
                fclose($handle);
                throw ValidationException::withMessages(['file' => 'Maksimal 2.000 baris data; setiap baris harus memiliki jumlah kolom yang sesuai.']);
            }
            // Plan and preview use index + 2 for CSV row numbers, including the letterhead and blank rows.
            $rows[$sourceRow - 2] = array_combine($headers, array_map(fn ($value) => DataTransferSchema::importCell((string) $value), $values));
        }
        fclose($handle);
        if (! $rows) {
            throw ValidationException::withMessages(['file' => 'File belum berisi data. Isi baris setelah judul kolom.']);
        }

        return $rows;
    }

    private function isBlankRow(array $values): bool
    {
        foreach ($values as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    public function plan(string $dataset, array $rows, string $mode): array
    {
        $commands = [];
        $errors = [];
        $seen = [];
        $counts = ['create' => 0, 'update' => 0, 'skip' => 0];
        foreach ($rows as $index => $row) {
            try {
                $command = $this->prepare($dataset, $row, $mode);
                if (isset($seen[$command['key']])) {
                    throw ValidationException::withMessages(['row' => 'Identitas data berulang dalam file yang sama.']);
                }
                $seen[$command['key']] = true;
                $command['row'] = $index + 2;
                $commands[] = $command;
                $counts[$command['action']]++;
            } catch (ValidationException $e) {
                $errors[] = ['row' => $index + 2, 'nis' => $row['nis'] ?? '', 'messages' => array_values(array_merge(...array_values($e->errors())))];
            }
        }

        return ['commands' => $commands, 'counts' => $counts, 'errors' => $errors, 'total' => count($rows)];
    }

    private function prepare(string $dataset, array $row, string $mode): array
    {
        Validator::make($row, ['nis' => ['required', 'string', 'max:255']])->validate();
        $student = Student::with('user')->where('student_number', $row['nis'])->first();
        if ($dataset !== 'students' && ! $student) {
            throw ValidationException::withMessages(['nis' => 'NIS belum terdaftar. Impor data siswa terlebih dahulu.']);
        }
        if ($student?->user->isPembina()) {
            throw ValidationException::withMessages(['nis' => 'Akun pembina tidak dapat diimpor sebagai siswa.']);
        }
        if ($dataset === 'students') {
            return $this->student($row, $student, $mode);
        }
        if ($row['nama_lengkap'] !== '' && $row['nama_lengkap'] !== $student->full_name) {
            throw ValidationException::withMessages(['nama_lengkap' => 'Nama lengkap tidak sesuai dengan NIS siswa.']);
        }

        return match ($dataset) {
            'memberships' => $this->membership($row, $student, $mode),
            'attendances' => $this->attendance($row, $student, $mode),
            'scores' => $this->score($row, $student),
        };
    }

    private function action(?object $existing, array $data, string $mode): string
    {
        if (! $existing) {
            return 'create';
        }
        $same = collect($data)->every(fn ($value, $key) => (string) $existing->{$key} === (string) ($value ?? ''));
        if ($same) {
            return 'skip';
        }
        if ($mode === 'update') {
            return 'update';
        }
        throw ValidationException::withMessages(['row' => 'Data sudah ada dengan isi berbeda. Pilih perbarui data atau koreksi file.']);
    }

    private function student(array $row, ?Student $student, string $mode): array
    {
        $data = [
            'student_number' => $row['nis'], 'full_name' => $row['nama_lengkap'], 'joined_year' => $row['tahun_bergabung'],
            'class_name' => $row['kelas'] ?: null, 'status' => $row['status'], 'phone' => $row['telepon'] ?: null,
            'email' => $row['email'] ?: null, 'address' => $row['alamat'] ?: null,
        ];
        Validator::make($data, array_merge(StudentProfileService::rules($student?->class_name), [
            'student_number' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($student?->user_id)],
            'full_name' => ['required', 'string', 'max:255'],
            'joined_year' => ['required', 'integer', 'min:2000', 'max:'.now('Asia/Jakarta')->year],
            'status' => ['required', Rule::in(array_keys(Student::STATUSES))],
        ]))->validate();
        if ($student?->user->committeeClassLocked() && ! preg_match('/^(XI|XII)(?:\s|$)/i', $data['class_name'] ?? '')) {
            throw ValidationException::withMessages(['kelas' => 'Siswa dengan masa jabatan panitia harus tetap di kelas XI atau XII.']);
        }

        return ['key' => $row['nis'], 'id' => $student?->id, 'version' => $this->version($student), 'data' => $data, 'nis' => $row['nis'], 'action' => $this->action($student, $data, $mode)];
    }

    private function membership(array $row, Student $student, string $mode): array
    {
        Validator::make($row, ['tahun_ajaran' => ['required', 'string', 'max:20'], 'semester' => ['required', Rule::in(['ganjil', 'genap'])]])->validate();
        $year = AcademicYear::where('name', $row['tahun_ajaran'])->where('semester', $row['semester'])->first();
        if (! $year) {
            throw ValidationException::withMessages(['tahun_ajaran' => 'Tahun ajaran dan semester belum terdaftar.']);
        }
        $existing = Membership::where('student_id', $student->id)->where('academic_year_id', $year->id)->first();
        $data = ['student_id' => $student->id, 'academic_year_id' => $year->id, 'class_name' => $row['kelas'] ?: null, 'status' => $row['status']];
        Validator::make($data, ['class_name' => ClassCatalog::rules($existing?->class_name), 'status' => ['required', Rule::in(array_keys(Student::STATUSES))]])->validate();
        $action = $this->action($existing, $data, $mode);
        if ($action !== 'skip' && $data['status'] === 'active' && $student->status !== 'active') {
            throw ValidationException::withMessages(['status' => 'Aktifkan siswa sebelum menambahkan keanggotaan aktif.']);
        }

        return ['key' => $student->id.':'.$year->id, 'id' => $existing?->id, 'version' => $this->version($existing), 'data' => $data, 'nis' => $row['nis'], 'action' => $action];
    }

    private function attendance(array $row, Student $student, string $mode): array
    {
        Validator::make($row, ['kegiatan_id' => ['required', 'integer'], 'status' => ['required', Rule::in(array_keys(Attendance::STATUSES))]])->validate();
        $activity = Activity::with('academicYear')->find($row['kegiatan_id']);
        if (! $activity || $activity->status === 'cancelled') {
            throw ValidationException::withMessages(['kegiatan_id' => 'Kegiatan tidak ditemukan atau sudah dibatalkan.']);
        }
        $metadata = ['tanggal_kegiatan' => $activity->activity_date->toDateString(), 'judul_kegiatan' => $activity->title,
            'tahun_ajaran' => $activity->academicYear->name, 'semester' => $activity->academicYear->semester];
        foreach ($metadata as $key => $value) {
            if ($row[$key] !== (string) $value) {
                throw ValidationException::withMessages([$key => 'Identitas kegiatan tidak sesuai dengan ID kegiatan.']);
            }
        }
        if (! (new AttendanceService)->roster($activity)->whereKey($student->id)->exists()) {
            throw ValidationException::withMessages(['nis' => 'Siswa harus aktif dan terdaftar pada semester kegiatan.']);
        }
        $existing = Attendance::where('activity_id', $activity->id)->where('student_id', $student->id)->first();
        $data = ['activity_id' => $activity->id, 'student_id' => $student->id, 'status' => $row['status']];

        return ['key' => $student->id.':'.$activity->id, 'id' => $existing?->id, 'version' => $this->version($existing), 'data' => $data, 'nis' => $row['nis'], 'action' => $this->action($existing, $data, $mode)];
    }

    private function score(array $row, Student $student): array
    {
        Validator::make($row, [
            'judul_penilaian' => ['required', 'string', 'max:150'], 'aspek' => ['required', Rule::in(AssessmentService::ASPECTS)],
            'nilai' => ['required', 'integer', 'between:1,4'], 'tanggal_pengesahan' => ['required', 'date_format:Y-m-d H:i:s'],
            'umpan_balik' => ['nullable', 'string', 'max:5000'], 'catatan_pembina' => ['nullable', 'string', 'max:5000'],
        ])->validate();
        $date = Carbon::createFromFormat('!Y-m-d H:i:s', $row['tanggal_pengesahan'], 'Asia/Jakarta');
        if ($date->gt(now('Asia/Jakarta'))) {
            throw ValidationException::withMessages(['tanggal_pengesahan' => 'Pengesahan tidak boleh berada pada waktu mendatang.']);
        }
        $existing = AssessmentAssignment::with('officialScore')->where('student_id', $student->id)
            ->where('title', $row['judul_penilaian'])->where('aspect', $row['aspek'])->first();
        $data = ['student_id' => $student->id, 'title' => $row['judul_penilaian'], 'aspect' => $row['aspek'], 'value' => (int) $row['nilai'],
            'approved_at' => $date->utc()->format('Y-m-d H:i:s'), 'feedback' => $row['umpan_balik'] ?: null, 'review_note' => $row['catatan_pembina'] ?: null];
        $action = 'create';
        if ($existing) {
            $score = $existing->officialScore;
            if (! $score || $score->value !== $data['value'] || $score->approved_at?->format('Y-m-d H:i:s') !== $data['approved_at']
                || (string) $score->feedback !== (string) $data['feedback'] || (string) $score->review_note !== (string) $data['review_note']) {
                throw ValidationException::withMessages(['nilai' => 'Penilaian sudah ada. Impor tidak boleh menimpa nilai resmi atau mengambil alih penugasan yang sedang berjalan.']);
            }
            $action = 'skip';
        }

        return ['key' => $student->id.':'.$data['title'].':'.$data['aspect'], 'id' => $existing?->id, 'version' => $this->version($existing).$this->version($existing?->officialScore), 'data' => $data, 'nis' => $row['nis'], 'action' => $action];
    }

    private function version(?Model $model): string
    {
        return $model ? hash('sha256', json_encode($model->getRawOriginal(), JSON_THROW_ON_ERROR)) : '';
    }

    public function commit(string $dataset, array $rows, string $mode, User $actor, array $expectedCommands): array
    {
        abort_unless($actor->isPembina(), 403);

        return DB::transaction(function () use ($dataset, $rows, $mode, $actor, $expectedCommands) {
            // Shared parent locks serialize imports and keep related student/semester state stable during revalidation.
            if ($dataset === 'memberships') {
                AcademicYear::query()->orderBy('id')->lockForUpdate()->get();
            }
            if ($dataset === 'attendances') {
                Activity::whereIn('id', array_column($rows, 'kegiatan_id'))->orderBy('id')->lockForUpdate()->get();
            }
            if ($dataset === 'students') {
                User::whereIn('username', array_column($rows, 'nis'))->orderBy('id')->lockForUpdate()->get();
            }
            $students = Student::whereIn('student_number', array_column($rows, 'nis'))->orderBy('id')->lockForUpdate()->get();
            if ($dataset === 'memberships') {
                Membership::whereIn('student_id', $students->pluck('id'))->orderBy('id')->lockForUpdate()->get();
            }
            if ($dataset === 'scores') {
                AssessmentAssignment::whereIn('student_id', $students->pluck('id'))->orderBy('id')->lockForUpdate()->get();
            }
            $plan = $this->plan($dataset, $rows, $mode);
            if ($plan['errors'] || $plan['commands'] !== $expectedCommands) {
                throw ValidationException::withMessages(['import' => 'Data berubah sejak pratinjau dibuat. Unggah ulang file untuk memeriksa hasil terbaru.']);
            }
            foreach ($plan['commands'] as $command) {
                if ($command['action'] === 'skip') {
                    continue;
                }
                $data = $command['data'];
                if ($dataset === 'students') {
                    if ($command['action'] === 'create') {
                        $user = User::create(['name' => $data['full_name'], 'username' => $data['student_number'], 'role' => 'siswa', 'password' => $data['student_number']]);
                        Student::create([...$data, 'user_id' => $user->id]);
                    } else {
                        $student = Student::findOrFail($command['id']);
                        $student->update($data);
                        $student->user()->update(['name' => $data['full_name']]);
                    }
                } elseif ($dataset === 'memberships') {
                    $command['id'] ? Membership::findOrFail($command['id'])->update($data) : Membership::create($data);
                } elseif ($dataset === 'attendances') {
                    (new AttendanceService)->record(Activity::findOrFail($data['activity_id']), $actor, [['student_id' => $data['student_id'], 'status' => $data['status']]]);
                } else {
                    $rubric = AssessmentService::rubric($data['aspect']);
                    $assessment = Assessment::create(['created_by' => $actor->id, 'title' => $data['title'], 'aspect' => $data['aspect'], 'rubric' => $rubric, 'instructions' => 'Arsip nilai resmi yang diimpor oleh pembina.']);
                    $assignment = $assessment->assignments()->create([
                        'student_id' => $data['student_id'], 'assessor_id' => $actor->id, 'created_by' => $actor->id,
                        'title' => $data['title'], 'aspect' => $data['aspect'], 'rubric' => $rubric, 'status' => 'approved',
                        'final_score' => $data['value'], 'feedback' => $data['feedback'], 'review_note' => $data['review_note'],
                        'reviewed_by' => $actor->id, 'reviewed_at' => $data['approved_at'],
                    ]);
                    $score = $assignment->officialScore()->create([
                        'assessment_id' => $assessment->id, 'student_id' => $data['student_id'], 'value' => $data['value'],
                        'feedback' => $data['feedback'], 'review_note' => $data['review_note'], 'approved_by' => $actor->id, 'approved_at' => $data['approved_at'],
                    ]);
                    $assignment->events()->create(['actor_id' => $actor->id, 'action' => 'imported', 'details' => [
                        'score_id' => $score->id, 'final_score' => $data['value'], 'approved_at' => $data['approved_at'], 'row' => $command['row'],
                    ]]);
                }
            }

            return $plan['counts'];
        });
    }
}
