<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\Attendance;
use App\Support\TablePagination;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ActivityHistoryController extends Controller
{
    public const PERIODS = [
        'upcoming' => 'Akan & sedang berlangsung',
        'completed' => 'Telah dilaksanakan',
        'pending' => 'Menunggu konfirmasi',
        'cancelled' => 'Dibatalkan',
    ];

    public const PARTICIPATION = [
        'all' => 'Semua',
        'attended' => 'Diikuti',
        'missed' => 'Dilewatkan',
        'unrecorded' => 'Belum dicatat',
    ];

    private function participation(Builder $query, string $participation, ?int $studentId): Builder
    {
        if ($participation === 'all') {
            return $query;
        }
        $own = fn ($records) => $records->where('student_id', $studentId);
        if ($participation === 'unrecorded') {
            return $query->where(fn ($activities) => $activities->whereDoesntHave('attendances', $own)
                ->orWhereHas('attendances', fn ($records) => $own($records)->where('status', 'belum_diisi')));
        }

        return $query->whereHas('attendances', fn ($records) => $own($records)
            ->whereIn('status', $participation === 'attended' ? ['hadir'] : ['izin', 'sakit', 'alpa']));
    }

    private function period(Builder $query, string $period, CarbonImmutable $now): Builder
    {
        if (in_array($period, ['completed', 'cancelled'], true)) {
            return $query->where('status', $period);
        }
        if ($period === 'upcoming') {
            return $query->upcoming($now);
        }

        return $query->where('status', 'scheduled')->where(fn ($dates) => $dates
            ->where('activity_date', '<', $now->toDateString())
            ->orWhere(fn ($today) => $today->where('activity_date', $now->toDateString())
                ->whereNotNull('end_time')->whereTime('end_time', '<', $now->format('H:i:s'))));
    }

    public function index(Request $request)
    {
        $staff = $request->user()->isPembina() || $request->user()->isPanitia();
        $studentId = $request->user()->student?->id;
        $filters = $request->validate([
            'period' => ['nullable', Rule::in(array_keys(self::PERIODS))],
            'participation' => ['nullable', Rule::in(array_keys(self::PARTICIPATION))],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'search' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('date_from'), 'after_or_equal:date_from')],
        ]);
        $filters = array_merge(['period' => $staff ? 'upcoming' : 'completed', 'participation' => 'all', 'academic_year_id' => null, 'search' => '', 'date_from' => '', 'date_to' => ''], array_filter($filters, fn ($value) => $value !== null));
        if ($staff || $filters['period'] !== 'completed') {
            $filters['participation'] = 'all';
        }
        $perPage = TablePagination::perPage($request);
        $now = CarbonImmutable::now('Asia/Jakarta');
        $query = Activity::visibleTo($request->user());
        if ($filters['academic_year_id']) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }
        if ($filters['search'] !== '') {
            $query->where(fn ($search) => $search->where('title', 'like', '%'.$filters['search'].'%')->orWhere('category', 'like', '%'.$filters['search'].'%'));
        }
        if ($filters['date_from']) {
            $query->where('activity_date', '>=', $filters['date_from']);
        }
        if ($filters['date_to']) {
            $query->where('activity_date', '<=', $filters['date_to']);
        }
        $counts = [];
        foreach (array_keys(self::PERIODS) as $period) {
            $counts[$period] = $this->period(clone $query, $period, $now)->count();
        }
        if (! $staff && ! $request->filled('period') && $filters['participation'] === 'all' && $counts['upcoming'] > 0) {
            $filters['period'] = 'upcoming';
        }
        $participationCounts = null;
        if (! $staff) {
            $completed = $this->period(clone $query, 'completed', $now);
            $participationCounts = ['all' => $counts['completed']];
            foreach (['attended', 'missed', 'unrecorded'] as $participation) {
                $participationCounts[$participation] = $this->participation(clone $completed, $participation, $studentId)->count();
            }
        }
        $activities = $this->period($query, $filters['period'], $now);
        if (! $staff && $filters['period'] === 'completed') {
            $this->participation($activities, $filters['participation'], $studentId);
        }
        $activities = $activities
            ->with(['academicYear', 'creator:id,name'])
            ->withCount([
                'attachments as material_count' => fn ($files) => $files->where('kind', 'material'),
                'attachments as documentation_count' => fn ($files) => $files->where('kind', 'documentation'),
            ]);
        if ($staff) {
            $activities->withCount(['attendances as present_count' => fn ($records) => $records->where('status', 'hadir')]);
        } else {
            $activities->with(['attendances' => fn ($records) => $records->where('student_id', $studentId)->select('id', 'activity_id', 'status')]);
        }
        $direction = $filters['period'] === 'upcoming' ? 'asc' : 'desc';
        $activities = $activities->orderBy('activity_date', $direction)->orderBy('start_time', $direction)->orderBy('id', $direction)
            ->paginate($perPage)->withQueryString();
        $activities->through(function ($activity) use ($now, $staff) {
            $data = $activity->toArray();
            $data['phase'] = $activity->status;
            if ($activity->status === 'scheduled') {
                $start = CarbonImmutable::parse($activity->activity_date->format('Y-m-d').' '.$activity->start_time, 'Asia/Jakarta');
                $end = CarbonImmutable::parse($activity->activity_date->format('Y-m-d').' '.($activity->end_time ?: '23:59:59'), 'Asia/Jakarta');
                $data['phase'] = $end->lt($now) ? 'pending' : ($start->lte($now) ? 'ongoing' : 'upcoming');
            }
            $data['my_attendance'] = $staff ? null : $activity->attendances->first()?->status;
            $data['my_participation'] = null;
            if (! $staff && $activity->status === 'completed') {
                $data['my_participation'] = match ($data['my_attendance']) {
                    'hadir' => 'attended',
                    'izin', 'sakit', 'alpa' => 'missed',
                    default => 'unrecorded',
                };
            }
            unset($data['attendances']);

            return $data;
        });

        return Inertia::render('Activities/History', [
            'activities' => $activities, 'filters' => $filters, 'counts' => $counts,
            'periods' => self::PERIODS, 'attendanceStatuses' => Attendance::STATUSES,
            'participationOptions' => self::PARTICIPATION, 'participationCounts' => $participationCounts,
            'years' => AcademicYear::orderByDesc('name')->orderBy('semester')->get(),
            'asOf' => $now->toIso8601String(),
        ]);
    }
}
