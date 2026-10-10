<?php

namespace App\Http\Controllers;

use App\Models\AssessmentAssignment;
use App\Models\Interest;
use App\Models\Student;
use App\Services\ClassCatalog;
use App\Services\StudentReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ReportController extends Controller
{
    public static function dates(Request $request): array
    {
        $until = ['nullable', 'date_format:Y-m-d'];
        if ($request->filled('from')) { $until[] = 'after_or_equal:from'; }
        $data = $request->validate(['from' => ['nullable', 'date_format:Y-m-d'], 'until' => $until]);
        return ['from' => $data['from'] ?? '', 'until' => $data['until'] ?? ''];
    }

    public function student(Request $request, Student $student, StudentReportService $reports)
    {
        $user = $request->user();
        $owner = $student->user_id === $user->id;
        $assigned = $user->isPanitia() && AssessmentAssignment::where('assessor_id', $user->id)->where('student_id', $student->id)->exists();
        abort_unless($owner || $user->isPembina() || $assigned, 403);
        $filters = self::dates($request);
        return Inertia::render('Reports/Student', [
            'student' => [...$student->only(['id', 'full_name', 'student_number', 'class_name', 'status', 'joined_year']),
                'profile_photo_url' => $owner || $user->isPembina() ? $student->profile_photo_url : null],
            'report' => $reports->summary($student, $filters), 'grades' => $reports->grades($student, $filters), 'filters' => $filters,
        ]);
    }

    public function interests(Request $request)
    {
        abort_unless($request->user()->isPembina(), 403);
        $legacyClasses = Student::whereNotNull('class_name')->where('class_name', '!=', '')->distinct()->orderBy('class_name')->pluck('class_name')->all();
        $classes = array_values(array_unique([...ClassCatalog::all(), ...$legacyClasses]));
        $data = $request->validate([
            'status' => ['nullable', Rule::in(['all', ...array_keys(Student::STATUSES)])],
            'class_name' => ['nullable', 'string', Rule::in($classes)],
        ]);
        $status = $data['status'] ?? 'active';
        $class = $data['class_name'] ?? '';
        $scope = function ($query) use ($status, $class) {
            $query->whereHas('user', fn ($q) => $q->whereIn('role', ['siswa', 'panitia']));
            if ($status !== 'all') { $query->where('status', $status); }
            if ($class !== '') { $query->where('class_name', $class); }
        };
        $members = Student::query();
        $scope($members);
        $total = (clone $members)->count();
        $respondents = (clone $members)->whereHas('interests')->count();
        $distribution = Interest::withCount([
            'studentInterests as selections_count' => fn ($q) => $q->whereHas('student', $scope),
            'studentInterests as primary_count' => fn ($q) => $q->where('is_primary', true)->whereHas('student', $scope),
        ])->orderBy('name')->get()->map(fn ($interest) => [
            'id' => $interest->id, 'name' => $interest->name,
            'selections_count' => (int) $interest->selections_count, 'primary_count' => (int) $interest->primary_count,
            'percentage' => $respondents ? round($interest->selections_count / $respondents * 100, 1) : 0,
            'primary_percentage' => $respondents ? round($interest->primary_count / $respondents * 100, 1) : 0,
        ]);
        return Inertia::render('Reports/Interests', [
            'distribution' => $distribution,
            'counts' => ['total' => $total, 'respondents' => $respondents, 'pending' => $total - $respondents, 'selections' => $distribution->sum('selections_count')],
            'filters' => ['status' => $status, 'class_name' => $class], 'classes' => $classes, 'statuses' => Student::STATUSES,
        ]);
    }
}
