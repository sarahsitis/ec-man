<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\ActivityScheme;
use App\Models\Interest;
use App\Models\Membership;
use App\Models\Student;
use App\Models\User;
use App\Services\ClassCatalog;
use App\Services\PreTestService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public const PASSWORD = 'demo12345';
    public const STUDENTS = 50;
    public const COMMITTEE = 15;
    public const COMPLETED_PRETESTS = 35;

    public function run(): void
    {
        $month = now('Asia/Jakarta')->startOfMonth();
        $password = Hash::make(self::PASSWORD);

        DB::transaction(function () use ($month, $password) {
            $pembina = User::where('role', 'pembina')->orderBy('id')->first()
                ?? User::firstOrCreate(['username' => 'demo_pembina'], [
                    'name' => '[Demo] Pembina EC', 'role' => 'pembina', 'password' => $password,
                ]);
            $startYear = $month->month >= 7 ? $month->year : $month->year - 1;
            $year = AcademicYear::firstOrCreate([
                'name' => $startYear.'/'.($startYear + 1), 'semester' => $month->month >= 7 ? 'ganjil' : 'genap',
            ], ['is_active' => false]);
            if (!AcademicYear::where('is_active', true)->exists()) {
                $year->update(['is_active' => true]);
            }

            $classes = ClassCatalog::all();
            $committeeClasses = array_values(array_filter($classes, fn ($class) => preg_match('/^(XI|XII) /', $class)));
            $firstNames = ['Alya', 'Bagas', 'Citra', 'Dimas', 'Eka', 'Farhan', 'Gita', 'Hafiz', 'Intan', 'Jihan'];
            $lastNames = ['Pratama', 'Lestari', 'Saputra', 'Ramadhani', 'Nugraha'];
            $interests = collect(['Percakapan', 'Debat', 'Storytelling', 'Menulis kreatif', 'Mendengarkan', 'Permainan bahasa'])
                ->map(fn ($name) => Interest::firstOrCreate(['name' => $name]));

            for ($index = 1; $index <= self::STUDENTS; $index++) {
                $isCommittee = $index <= self::COMMITTEE;
                $username = sprintf('demo%03d', $index);
                $name = '[Demo] '.$firstNames[($index - 1) % count($firstNames)].' '.$lastNames[intdiv($index - 1, count($firstNames))];
                $class = $isCommittee
                    ? $committeeClasses[(($index - 1) * 3) % count($committeeClasses)]
                    : $classes[(($index - self::COMMITTEE - 1) * 7) % count($classes)];
                $grade = str_starts_with($class, 'XII ') ? 12 : (str_starts_with($class, 'XI ') ? 11 : 10);
                $user = User::firstOrCreate(['username' => $username], [
                    'name' => $name, 'role' => $isCommittee ? 'panitia' : 'siswa', 'password' => $password,
                ]);
                $student = Student::firstOrCreate(['user_id' => $user->id], [
                    'student_number' => $username, 'full_name' => $name, 'class_name' => $class,
                    'joined_year' => $startYear - ($grade - 10), 'status' => 'active',
                    'email' => $username.'@example.test', 'address' => 'Alamat contoh siswa '.$index,
                ]);
                Membership::firstOrCreate(['student_id' => $student->id, 'academic_year_id' => $year->id], [
                    'class_name' => $student->class_name, 'status' => $student->status,
                ]);

                if ($isCommittee) {
                    // Preserve existing appointments and revocations when running the seeder again.
                    $overlap = $user->committeeRoles()->whereNull('revoked_at')
                        ->where('starts_on', '<=', $month->copy()->endOfMonth()->toDateString())
                        ->where('ends_on', '>=', $month->toDateString())->exists();
                    if (!$overlap) {
                        $user->committeeRoles()->firstOrCreate([
                            'starts_on' => $month->toDateString(), 'ends_on' => $month->copy()->endOfMonth()->toDateString(),
                        ], ['appointed_by' => $pembina->id, 'note' => '[Demo] Panitia EC bulan '.$month->format('Y-m')]);
                    }
                }

                if ($index <= self::COMPLETED_PRETESTS && !$student->preTestResult()->exists()) {
                    $this->completePretest($student, $index, $interests);
                }
            }

            $this->schedule($month, $year, $pembina);
        });

        $this->command?->info('Dummy data: 50 siswa (15 panitia), 35 pre-test selesai, jadwal Selasa/Kamis '.$month->format('Y-m').'.');
        $this->command?->info('Login: demo001–demo050, password: '.self::PASSWORD.'. Data yang sudah ada dipertahankan.');
    }

    private function completePretest(Student $student, int $index, Collection $interests): void
    {
        $answers = [];
        $correctCount = 3 + (($index * 3) % 10);
        foreach (config('pretest.questions') as $position => $question) {
            $wrong = array_values(array_diff(array_keys($question['options']), [$question['correct']]))[0];
            $answers[$question['id']] = $position < $correctCount ? $question['correct'] : $wrong;
        }
        $primary = $interests[($index - 1) % $interests->count()];
        $chosen = [$primary->id, $interests[$index % $interests->count()]->id];
        if ($index % 3 === 0) { $chosen[] = $interests[($index + 1) % $interests->count()]->id; }
        $selfAssessment = [];
        foreach (array_keys(config('pretest.skills')) as $position => $aspect) {
            $selfAssessment[$aspect] = 1 + (($index + $position) % 4);
        }
        $result = app(PreTestService::class)->submit($student, [
            'version' => config('pretest.version'), 'answers' => $answers, 'self_assessment' => $selfAssessment,
            'interest_ids' => $chosen, 'primary_interest_id' => $primary->id,
            'learning_goal' => 'Saya ingin meningkatkan kemampuan bahasa Inggris melalui latihan '.$primary->name.'.',
        ]);
        $result->update(['submitted_at' => now('Asia/Jakarta')->subDays(1 + ($index % 10))->setTime(9, 0)->utc()]);
    }

    private function schedule(Carbon $month, AcademicYear $year, User $pembina): void
    {
        $topics = [
            ['Percakapan', 'Melatih percakapan sehari-hari dan respons spontan.', 'Pemanasan 10 menit; latihan berpasangan 50 menit; praktik kelompok 20 menit; refleksi 10 menit.'],
            ['Storytelling', 'Menceritakan pengalaman dengan struktur yang runtut.', 'Pemanasan 10 menit; menyusun cerita 25 menit; bercerita 45 menit; refleksi 10 menit.'],
            ['Mendengarkan', 'Memahami gagasan utama dan informasi rinci dari audio.', 'Pemanasan 10 menit; menyimak audio 30 menit; diskusi 40 menit; refleksi 10 menit.'],
            ['Debat', 'Menyampaikan pendapat dan alasan secara sopan.', 'Pemanasan 10 menit; persiapan argumen 25 menit; debat kelompok 45 menit; refleksi 10 menit.'],
            ['Menulis kreatif', 'Menulis paragraf sederhana dengan ide yang jelas.', 'Pemanasan 10 menit; menyusun draf 35 menit; umpan balik teman 35 menit; refleksi 10 menit.'],
            ['Permainan bahasa', 'Memperkaya kosakata melalui permainan bersama.', 'Pemanasan 10 menit; permainan kosakata 40 menit; tantangan kelompok 30 menit; refleksi 10 menit.'],
        ];
        $schemes = collect($topics)->map(fn ($topic) => ActivityScheme::firstOrCreate(['name' => '[Demo] Latihan '.$topic[0]], [
            'category' => $topic[0], 'objectives' => $topic[1], 'agenda' => $topic[2], 'duration_minutes' => 90,
        ]));
        $meeting = 0;
        for ($date = $month->copy(); $date->month === $month->month; $date->addDay()) {
            if (!in_array($date->dayOfWeek, [Carbon::TUESDAY, Carbon::THURSDAY], true)) { continue; }
            $scheme = $schemes[$meeting % $schemes->count()];
            $meeting++;
            Activity::firstOrCreate([
                'title' => '[Demo] EC '.$date->toDateString().' - '.$scheme->category, 'academic_year_id' => $year->id,
            ], [
                'category' => $scheme->category, 'activity_scheme_id' => $scheme->id,
                'description' => 'Pertemuan contoh EC ke-'.$meeting.' untuk bulan '.$month->format('Y-m').'.',
                'objectives' => $scheme->objectives, 'agenda' => $scheme->agenda,
                'activity_date' => $date->toDateString(), 'start_time' => '14:30', 'end_time' => '16:00',
                'location' => 'Ruang English Club', 'pic' => $pembina->name.' dan Panitia EC',
                'target_audience' => 'Seluruh anggota EC', 'status' => 'scheduled', 'created_by' => $pembina->id,
            ]);
        }
    }
}
