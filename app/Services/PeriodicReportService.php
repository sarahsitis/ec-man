<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Membership;
use App\Models\Score;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class PeriodicReportService
{
    public function period(array $filters): array
    {
        $year = AcademicYear::findOrFail($filters['academic_year_id']);
        $parts = explode('/', $year->name);
        if (count($parts) !== 2 || ! ctype_digit($parts[0]) || ! ctype_digit($parts[1]) || (int) $parts[1] !== (int) $parts[0] + 1) {
            throw ValidationException::withMessages(['academic_year_id' => 'Format tahun ajaran harus berurutan, misalnya 2026/2027.']);
        }
        $semesterStart = Carbon::create((int) ($year->semester === 'ganjil' ? $parts[0] : $parts[1]), $year->semester === 'ganjil' ? 7 : 1, 1, 0, 0, 0, 'Asia/Jakarta');
        $semesterEnd = $semesterStart->copy()->addMonths(6)->subDay()->endOfDay();
        $anchor = Carbon::createFromFormat('!Y-m-d', $filters['date'], 'Asia/Jakarta');
        [$from, $until] = match ($filters['period']) {
            'week' => [$anchor->copy()->startOfWeek(Carbon::MONDAY), $anchor->copy()->endOfWeek(Carbon::SUNDAY)],
            'month' => [$anchor->copy()->startOfMonth(), $anchor->copy()->endOfMonth()],
            default => [$semesterStart, $semesterEnd],
        };
        if ($until->lt($semesterStart) || $from->gt($semesterEnd)) {
            throw ValidationException::withMessages(['date' => 'Tanggal laporan harus berada dalam semester yang dipilih.']);
        }
        $from = $from->max($semesterStart)->startOfDay();
        $until = $until->min($semesterEnd)->endOfDay();

        return [
            'from' => $from->toDateString(), 'until' => $until->toDateString(),
            'label' => ['week' => 'Mingguan', 'month' => 'Bulanan', 'semester' => 'Semester'][$filters['period']],
            'year' => $year->only('id', 'name', 'semester'),
        ];
    }

    public function section(string $dataset, array $filters, array $period): array
    {
        $yearId = $period['year']['id'];
        $class = $filters['class_name'] ?? '';
        $memberships = Membership::where('academic_year_id', $yearId)->when($class !== '', fn ($q) => $q->where('class_name', $class));
        $studentIds = (clone $memberships)->select('student_id');
        $rows = match ($dataset) {
            'students' => $this->students($class),
            'memberships' => $memberships->with('student')->orderBy('class_name')->orderBy('student_id')->get()->map(fn ($m) => [
                'nis' => $m->student->student_number, 'nama_lengkap' => $m->student->full_name, 'tahun_ajaran' => $period['year']['name'], 'semester' => $period['year']['semester'],
                'kelas' => $m->class_name, 'status' => $m->status,
            ])->all(),
            'attendances' => Attendance::with(['student', 'activity'])->whereHas('activity', fn ($q) => $q->where('academic_year_id', $yearId)
                ->whereBetween('activity_date', [$period['from'], $period['until']])->where('status', '!=', 'cancelled'))
                ->when($class !== '', fn ($q) => $q->whereIn('student_id', $studentIds))
                ->orderBy('activity_id')->orderBy('student_id')->get()->map(fn ($a) => [
                    'nis' => $a->student->student_number, 'nama_lengkap' => $a->student->full_name, 'kegiatan_id' => $a->activity_id,
                    'tanggal_kegiatan' => $a->activity->activity_date->toDateString(), 'judul_kegiatan' => $a->activity->title,
                    'tahun_ajaran' => $period['year']['name'], 'semester' => $period['year']['semester'], 'status' => $a->status,
                ])->all(),
            // Scores have no semester FK: scope by the approval date, without dropping students whose membership was later removed.
            'scores' => Score::with(['student', 'assessment'])->whereBetween('approved_at', [
                Carbon::parse($period['from'], 'Asia/Jakarta')->startOfDay()->utc(),
                Carbon::parse($period['until'], 'Asia/Jakarta')->endOfDay()->utc(),
            ])->when($class !== '', fn ($q) => $q->whereIn('student_id', $studentIds))
                ->orderBy('approved_at')->orderBy('id')->get()->map(fn ($s) => [
                    'nis' => $s->student->student_number, 'nama_lengkap' => $s->student->full_name, 'judul_penilaian' => $s->assessment->title,
                    'aspek' => $s->assessment->aspect, 'nilai' => $s->value,
                    'tanggal_pengesahan' => $s->approved_at->copy()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    'umpan_balik' => $s->feedback, 'catatan_pembina' => $s->review_note,
                ])->all(),
        };
        $summary = ['Jumlah Data' => count($rows), 'Jumlah Siswa' => collect($rows)->pluck('nis')->unique()->count()];
        if (in_array($dataset, ['students', 'memberships'])) {
            $summary['Status Aktif'] = collect($rows)->where('status', 'active')->count();
        }
        if ($dataset === 'attendances') {
            foreach (Attendance::STATUSES as $status => $label) {
                $summary[$label] = collect($rows)->where('status', $status)->count();
            }
            $recorded = collect($rows)->where('status', '!=', 'belum_diisi')->count();
            $summary['Persentase Kehadiran'] = $recorded ? round(collect($rows)->where('status', 'hadir')->count() / $recorded * 100, 2).'%' : '—';
        }
        if ($dataset === 'scores') {
            $summary['Rata-rata Nilai'] = count($rows) ? number_format(collect($rows)->avg('nilai'), 2, ',', '.') : '—';
            foreach (AssessmentService::ASPECTS as $aspect) {
                $average = collect($rows)->where('aspek', $aspect)->avg('nilai');
                $summary[ucfirst($aspect)] = $average === null ? '—' : number_format($average, 2, ',', '.');
            }
        }

        return ['dataset' => $dataset, 'title' => DataTransferSchema::LABELS[$dataset], 'columns' => DataTransferSchema::COLUMNS[$dataset], 'rows' => $rows, 'summary' => $summary];
    }

    public function students(string $class = ''): array
    {
        return Student::whereHas('user', fn ($q) => $q->whereIn('role', ['siswa', 'panitia']))
            ->when($class !== '', fn ($q) => $q->where('class_name', $class))->orderBy('full_name')->orderBy('id')->get()->map(fn ($s) => [
                'nis' => $s->student_number, 'nama_lengkap' => $s->full_name, 'tahun_bergabung' => $s->joined_year,
                'kelas' => $s->class_name, 'status' => $s->status, 'telepon' => $s->phone, 'email' => $s->email, 'alamat' => $s->address,
            ])->all();
    }
}
