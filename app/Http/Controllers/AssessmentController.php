<?php
namespace App\Http\Controllers;
use App\Models\AssessmentAssignment;
use App\Models\Student;
use App\Models\User;
use App\Services\AssessmentService;
use App\Services\StudentReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AssessmentController extends Controller {
    private function panitia(Request $request): User {
        abort_unless($request->user()->isPanitia(), 403);
        return $request->user();
    }
    private function scoped(Request $request, AssessmentAssignment $assignment): void {
        $user = $this->panitia($request);
        abort_unless($assignment->assessor_id === $user->id && $assignment->student->user_id !== $user->id, 403);
    }
    private function entries($query) {
        return $query->with(['student:id,user_id,full_name,student_number,class_name', 'assessor:id,name', 'reviewer:id,name', 'officialScore:id,assessment_assignment_id,value'])->latest()->paginate(20)->withQueryString();
    }
    public function index(Request $request) {
        abort_unless($request->user()->isPembina(), 403);
        $status = $request->validate(['status' => ['nullable', Rule::in(['all', 'assigned', 'draft', 'submitted', 'approved', 'revision', 'rejected'])]])['status'] ?? 'all';
        $query = AssessmentAssignment::query();
        if ($status !== 'all') { $query->where('status', $status); }
        return Inertia::render('Assessments/Index', [
            'status' => $status, 'pendingCount' => AssessmentAssignment::where('status', 'submitted')->count(),
            'assignments' => $this->entries($query),
            'students' => Student::where('status', 'active')->select('id', 'user_id', 'full_name', 'student_number', 'class_name')->orderBy('full_name')->get(),
            'assessors' => User::activeCommittee()->select('id', 'name')->orderBy('name')->get(),
            'aspects' => AssessmentService::ASPECTS,
        ]);
    }
    public function store(Request $request, AssessmentService $service) {
        abort_unless($request->user()->isPembina(), 403);
        $data = $request->validate([
            'student_id' => ['required_without:student_ids', Rule::prohibitedIf($request->has('student_ids')), 'integer', 'exists:students,id'],
            'student_ids' => ['required_without:student_id', Rule::prohibitedIf($request->has('student_id')), 'array', 'min:1'],
            'student_ids.*' => ['required', 'integer', 'distinct', 'exists:students,id'],
            'assessor_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'panitia')],
            'aspect' => ['required', Rule::in(AssessmentService::ASPECTS)],
            'title' => ['required', 'string', 'max:150'],
            'instructions' => ['nullable', 'string', 'max:4000'],
            'due_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:'.now('Asia/Jakarta')->format('Y-m-d')],
        ]);
        $service->delegate($request->user(), $data);
        $studentIds = $data['student_ids'] ?? [$data['student_id']];
        return back()->with('success', 'Penugasan untuk '.count($studentIds).' siswa dibuat.');
    }

    public function reviewQueue(Request $request) {
        abort_unless($request->user()->isPembina(), 403);
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['submitted', 'approved', 'revision', 'rejected'])],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $status = $filters['status'] ?? 'submitted';
        $search = $filters['search'] ?? '';
        $query = AssessmentAssignment::with(['student:id,user_id,full_name,student_number,class_name', 'assessor:id,name', 'reviewer:id,name', 'officialScore:id,assessment_assignment_id,value'])
            ->where('status', $status);
        if ($search !== '') {
            $query->where(fn ($q) => $q->where('title', 'like', '%'.$search.'%')
                ->orWhereHas('student', fn ($s) => $s->where('full_name', 'like', '%'.$search.'%')->orWhere('student_number', 'like', '%'.$search.'%'))
                ->orWhereHas('assessor', fn ($a) => $a->where('name', 'like', '%'.$search.'%')));
        }
        $status === 'submitted' ? $query->orderBy('submitted_at')->orderBy('id') : $query->orderByDesc('reviewed_at')->orderByDesc('id');
        return Inertia::render('Assessments/Queue', [
            'assignments' => $query->paginate(20)->withQueryString(),
            'filters' => ['status' => $status, 'search' => $search],
            'counts' => AssessmentAssignment::select('status')->selectRaw('count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }
    public function committeeDashboard(Request $request) {
        $user = $this->panitia($request);
        $query = AssessmentAssignment::where('assessor_id', $user->id)->whereHas('student', fn ($q) => $q->where('user_id', '!=', $user->id));
        $counts = (clone $query)->select('status')->selectRaw('count(*) as total')->groupBy('status')->pluck('total', 'status');
        return Inertia::render('Panitia/Dashboard', [
            'counts' => $counts,
            'committeeTerm' => $user->committeeRoles()->active()->first(['starts_on', 'ends_on']),
            'recent' => (clone $query)->with(['student:id,full_name,student_number,class_name', 'assessor:id,name'])->latest()->limit(5)->get(),
        ]);
    }
    public function committeeIndex(Request $request) {
        $user = $this->panitia($request);
        $status = $request->validate(['status' => ['nullable', Rule::in(['active', 'history'])]])['status'] ?? 'active';
        $query = AssessmentAssignment::where('assessor_id', $user->id)->whereHas('student', fn ($q) => $q->where('user_id', '!=', $user->id));
        $status === 'history' ? $query->whereIn('status', ['submitted', 'approved', 'rejected']) : $query->whereIn('status', ['assigned', 'draft', 'revision']);
        return Inertia::render('Panitia/Assignments', ['assignments' => $this->entries($query), 'status' => $status]);
    }
    public function show(Request $request, AssessmentAssignment $assignment) {
        if (!$request->user()->isPembina()) { $this->scoped($request, $assignment); }
        return Inertia::render('Assessments/Show', [
            'assignment' => $assignment->load(['assessment:id,instructions', 'student:id,user_id,full_name,student_number,class_name', 'assessor:id,name', 'reviewer:id,name', 'officialScore.approver:id,name', 'events']),
            'expired' => $assignment->expired(),
            'nextPendingId' => $request->user()->isPembina() ? AssessmentAssignment::where('status', 'submitted')->where('id', '!=', $assignment->id)->orderBy('submitted_at')->orderBy('id')->value('id') : null,
        ]);
    }
    public function recommend(Request $request, AssessmentAssignment $assignment, AssessmentService $service) {
        $this->scoped($request, $assignment);
        $submit = $request->input('action') === 'submit';
        $data = $request->validate([
            'action' => ['required', Rule::in(['draft', 'submit'])],
            'proposed_score' => [$submit ? 'required' : 'nullable', 'integer', 'between:1,4'],
            'observations' => [$submit ? 'required' : 'nullable', 'string', $submit ? 'min:10' : 'min:0', 'max:2000'],
            'feedback' => [$submit ? 'required' : 'nullable', 'string', $submit ? 'min:10' : 'min:0', 'max:2000'],
        ]);
        $service->recommend($assignment, $request->user(), $data);
        return back()->with('success', $submit ? 'Rekomendasi dikirim ke pembina.' : 'Draf disimpan.');
    }
    public function review(Request $request, AssessmentAssignment $assignment, AssessmentService $service) {
        abort_unless($request->user()->isPembina(), 403);
        $approve = $request->input('decision') === 'approve';
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'revise', 'reject'])],
            'final_score' => [$approve ? 'required' : 'nullable', 'integer', 'between:1,4'],
            'review_note' => [$approve ? 'nullable' : 'required', 'string', 'max:2000'],
        ]);
        $service->review($assignment, $request->user(), $data);
        return back()->with('success', $approve ? 'Rekomendasi disahkan dan nilai resmi diterbitkan.' : 'Keputusan pembina disimpan.');
    }
    public function extendDeadline(Request $request, AssessmentAssignment $assignment, AssessmentService $service) {
        abort_unless($request->user()->isPembina(), 403);
        $data = $request->validate(['due_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:'.now('Asia/Jakarta')->format('Y-m-d')]]);
        DB::transaction(function () use ($request, $assignment, $data, $service) {
            $item = AssessmentAssignment::lockForUpdate()->findOrFail($assignment->id);
            abort_unless(in_array($item->status, ['assigned', 'draft', 'revision', 'submitted'], true), 409);
            $item->update(['due_date' => $data['due_date'] ?? null]);
            $service->event($item, $request->user(), 'deadline', ['due_date' => $data['due_date'] ?? null]);
        });
        return back()->with('success', 'Batas waktu diperbarui.');
    }
    public function progress(Request $request, StudentReportService $reports) {
        abort_unless(!$request->user()->isPembina(), 403);
        $student = $request->user()->student;
        $filters = ReportController::dates($request);
        return Inertia::render('Progress/Index', [
            'grades' => $reports->grades($student, $filters), 'report' => $reports->summary($student, $filters),
            'student' => $student?->only(['id', 'full_name', 'student_number', 'class_name']), 'filters' => $filters,
        ]);
    }
}
