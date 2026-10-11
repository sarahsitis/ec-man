<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\Score;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    private function attendanceSummary(User $user): array
    {
        $total = Activity::visibleTo($user)->where('status', 'completed')->count();
        $recorded = Attendance::where('student_id', $user->student?->id ?? 0)
            ->whereHas('activity', fn ($activities) => $activities->where('status', 'completed'))
            ->select('status')->selectRaw('count(*) as total')->groupBy('status')->pluck('total', 'status');
        $counts = [];
        foreach (['hadir', 'izin', 'sakit', 'alpa'] as $status) {
            $counts[$status] = (int) ($recorded[$status] ?? 0);
        }
        $recordedTotal = array_sum($counts);
        $counts['belum_diisi'] = $total - $recordedTotal;

        return [
            'counts' => $counts, 'total' => $total, 'recorded' => $recordedTotal,
            'attendanceRate' => $recordedTotal ? round($counts['hadir'] / $recordedTotal * 100, 1) : null,
        ];
    }

    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->isPanitia()) {
            return redirect()->route('panitia.dashboard');
        }
        if ($user->isPembina()) {
            return Inertia::render('Dashboard');
        }

        $now = CarbonImmutable::now('Asia/Jakarta');
        $query = Activity::visibleTo($user)->upcoming($now);
        $count = (clone $query)->count();
        $activities = $query->with(['academicYear:id,name,semester,is_active', 'creator:id,name'])
            ->orderBy('activity_date')->orderBy('start_time')->orderBy('id')->limit(5)->get()
            ->map(function ($activity) use ($now) {
                $start = CarbonImmutable::parse($activity->activity_date->format('Y-m-d').' '.$activity->start_time, 'Asia/Jakarta');

                return [
                    'id' => $activity->id, 'title' => $activity->title, 'category' => $activity->category,
                    'activity_date' => $activity->activity_date->format('Y-m-d'),
                    'start_time' => $activity->start_time, 'end_time' => $activity->end_time,
                    'location' => $activity->location, 'pic' => $activity->pic ?: $activity->creator?->name,
                    'academic_year' => $activity->academicYear,
                    'phase' => $start->lte($now) ? 'ongoing' : 'upcoming',
                ];
            });

        $scores = Score::where('student_id', $user->student?->id ?? 0);
        $scoreCount = (clone $scores)->count();
        $recentGrades = $scores->with(['assessment:id,title,aspect', 'approver:id,name'])
            ->orderByDesc('approved_at')->orderByDesc('id')->limit(5)->get()->map(fn ($score) => [
                'id' => $score->id, 'title' => $score->assessment->title, 'aspect' => $score->assessment->aspect,
                'value' => $score->value, 'approvedAt' => $score->approved_at?->toISOString(),
                'approver' => $score->approver?->name,
            ]);

        return Inertia::render('Dashboard', [
            'upcomingActivities' => $activities,
            'upcomingActivityCount' => $count,
            'attendanceSummary' => $this->attendanceSummary($user),
            'recentGrades' => $recentGrades,
            'gradeCount' => $scoreCount,
        ]);
    }
}
