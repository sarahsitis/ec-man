<?php
namespace App\Http\Controllers;
use App\Models\AssessmentAssignment;
use App\Models\Student;
use App\Models\User;
use App\Services\AssessmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
        return $query->with(['student:id,user_id,full_name,student_number,class_name', 'assessor:id,name', 'reviewer:id,name'])->latest()->paginate(20)->withQueryString();
    }
    public function index(Request $request) {
        abort_unless($request->user()->isPembina(), 403);
        $status = $request->validate(['status' => ['nullable', Rule::in(['all', 'submitted', 'approved', 'revision'])]])['status'] ?? 'all';
        $query = AssessmentAssignment::query();
        if ($status !== 'all') { $query->where('status', $status); }
        return Inertia::render('Assessments/Index', [
            'status' => $status, 'pendingCount' => AssessmentAssignment::where('status', 'submitted')->count(),
            'assignments' => $this->entries($query),
            'students' => Student::select('id', 'user_id', 'full_name', 'student_number', 'class_name')->orderBy('full_name')->get(),
            'assessors' => User::where('role', 'panitia')->select('id', 'name')->orderBy('name')->get(),
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
            'due_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:'.now('Asia/Jakarta')->format('Y-m-d')],
        ]);
        $studentIds = $data['student_ids'] ?? [$data['student_id']];
        $students = Student::whereIn('id', $studentIds)->get()->keyBy('id');
        $assessor = User::findOrFail($data['assessor_id']);
        foreach ($studentIds as $index => $studentId) {
            if ($students[$studentId]->user_id === $assessor->id) {
                throw ValidationException::withMessages([isset($data['student_ids']) ? 'student_ids.'.$index : 'student_id' => 'Panitia tidak boleh menilai dirinya sendiri.']);
            }
        }
        if (!$assessor->student || !preg_match('/^(XI|XII)(?:\s|$)/i', trim($assessor->student->class_name ?? ''))) {
            throw ValidationException::withMessages(['assessor_id' => 'Lengkapi kelas XI/XII panitia melalui Edit Anggota.']);
        }
        DB::transaction(function () use ($request, $data, $studentIds, $students, $service) {
            $duplicates = AssessmentAssignment::whereIn('student_id', $studentIds)
                ->where('title', $data['title'])->where('aspect', $data['aspect'])->pluck('student_id')->all();
            $errors = [];
            foreach ($studentIds as $index => $studentId) {
                if (in_array((int) $studentId, $duplicates)) {
                    $errors[isset($data['student_ids']) ? 'student_ids.'.$index : 'title'] = 'Penugasan dengan judul dan aspek ini sudah ada untuk '.$students[$studentId]->full_name.'.';
                }
            }
            if ($errors) { throw ValidationException::withMessages($errors); }
            foreach ($studentIds as $studentId) {
                $assignment = AssessmentAssignment::create([
                    'student_id' => $studentId, 'assessor_id' => $data['assessor_id'],
                    'title' => $data['title'], 'aspect' => $data['aspect'], 'due_date' => $data['due_date'] ?? null,
                    'created_by' => $request->user()->id, 'status' => 'assigned', 'rubric' => AssessmentService::rubric($data['aspect']),
                ]);
                $service->event($assignment, $request->user(), 'assigned', ['assessor_id' => $assignment->assessor_id, 'student_id' => $assignment->student_id]);
            }
        });
        return back()->with('success', 'Penugasan untuk '.count($studentIds).' siswa dibuat.');
    }
    public function committeeDashboard(Request $request) {
        $user = $this->panitia($request);
        $query = AssessmentAssignment::where('assessor_id', $user->id)->whereHas('student', fn ($q) => $q->where('user_id', '!=', $user->id));
        $counts = (clone $query)->select('status')->selectRaw('count(*) as total')->groupBy('status')->pluck('total', 'status');
        return Inertia::render('Panitia/Dashboard', [
            'counts' => $counts,
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
            'assignment' => $assignment->load(['student:id,user_id,full_name,student_number,class_name', 'assessor:id,name', 'reviewer:id,name', 'events']),
            'expired' => $assignment->expired(),
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
        return back()->with('success', 'Keputusan pembina disimpan.');
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
    public function progress(Request $request) {
        abort_unless(!$request->user()->isPembina(), 403);
        $query = AssessmentAssignment::whereHas('student', fn ($q) => $q->where('user_id', $request->user()->id))->where('status', 'approved');
        // Only published outcomes; do not expose proposals or internal observation/audit data.
        $grades = $query->with('reviewer:id,name')->select('id', 'title', 'aspect', 'final_score', 'feedback', 'review_note', 'reviewed_at', 'reviewed_by')->orderByDesc('reviewed_at')->paginate(20);
        return Inertia::render('Progress/Index', ['grades' => $grades]);
    }
}
