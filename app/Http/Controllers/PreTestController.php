<?php

namespace App\Http\Controllers;

use App\Models\AssessmentAssignment;
use App\Models\InterestCategory;
use App\Models\Student;
use App\Services\PreTestService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PreTestController extends Controller {
    private function metadata(): array {
        return [
            'skills' => config('pretest.skills'), 'scales' => config('pretest.scales'),
            'domains' => config('pretest.domains'),
        ];
    }

    public function index(Request $request, PreTestService $service) {
        if ($request->user()->isPembina()) { return redirect()->route('pretests.reports'); }
        $student = $request->user()->student;
        return Inertia::render('PreTests/Index', array_merge($this->metadata(), [
            'student' => $student, 'result' => $student?->preTestResult,
            'version' => config('pretest.version'),
            'questions' => $student?->preTestResult ? [] : $service->publicQuestions(),
            'categories' => InterestCategory::select('id', 'name')->orderBy('name')->get(),
            'initialInterests' => $student?->interests()->get(['interest_category_id', 'is_primary', 'learning_goal']) ?? [],
        ]));
    }

    public function store(Request $request, PreTestService $service) {
        abort_if($request->user()->isPembina(), 403);
        $student = $request->user()->student;
        if (!$student) {
            throw ValidationException::withMessages(['pre_test' => 'Lengkapi profil siswa terlebih dahulu sebelum mengisi pre-test.']);
        }
        $questions = config('pretest.questions');
        $questionIds = array_column($questions, 'id');
        $skills = array_keys(config('pretest.skills'));
        $rules = [
            'version' => ['required', Rule::in([config('pretest.version')])],
            'answers' => ['required', 'array:'.implode(',', $questionIds)],
            'self_assessment' => ['required', 'array:'.implode(',', $skills)],
            'interest_ids' => ['required', 'array', 'min:1'],
            'interest_ids.*' => ['required', 'integer', 'distinct', 'exists:interests,id'],
            'primary_interest_id' => ['required', 'integer', 'exists:interests,id'],
            'learning_goal' => ['required', 'string', 'min:10', 'max:1000'],
        ];
        foreach ($questions as $question) {
            $rules['answers.'.$question['id']] = ['required', Rule::in(array_keys($question['options']))];
        }
        foreach ($skills as $skill) {
            $rules['self_assessment.'.$skill] = ['required', 'integer', 'between:1,4'];
        }
        $data = $request->validate($rules, [
            'answers.*.required' => 'Jawab semua soal sebelum mengirim pre-test.',
            'answers.*.in' => 'Pilih salah satu jawaban yang tersedia.',
            'self_assessment.*.required' => 'Pilih tingkat kemampuan untuk setiap keterampilan.',
            'version.in' => 'Soal pre-test telah diperbarui. Muat ulang halaman sebelum mengerjakan.',
            'interest_ids.required' => 'Pilih minimal satu minat.',
        ]);
        if (!in_array((int) $data['primary_interest_id'], array_map('intval', $data['interest_ids']), true)) {
            throw ValidationException::withMessages(['primary_interest_id' => 'Minat utama harus termasuk dalam minat yang dipilih.']);
        }
        $service->submit($student, $data);
        return redirect()->route('pretests.index')->with('success', 'Pre-test berhasil dikirim. Kemampuan awal dan minat Anda sudah tersimpan.');
    }

    public function reports(Request $request) {
        abort_unless($request->user()->isPembina(), 403);
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['all', 'completed', 'pending'])],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $status = $filters['status'] ?? 'all';
        $search = $filters['search'] ?? '';
        $members = Student::whereHas('user', fn ($q) => $q->whereIn('role', ['siswa', 'panitia']));
        $total = (clone $members)->count();
        $completed = (clone $members)->whereHas('preTestResult')->count();
        $query = clone $members;
        if ($status === 'completed') { $query->whereHas('preTestResult'); }
        if ($status === 'pending') { $query->whereDoesntHave('preTestResult'); }
        if ($search !== '') {
            $query->where(fn ($q) => $q->where('full_name', 'like', '%'.$search.'%')
                ->orWhere('student_number', 'like', '%'.$search.'%')->orWhere('class_name', 'like', '%'.$search.'%'));
        }
        return Inertia::render('PreTests/Reports', [
            'students' => $query->with('preTestResult')->orderBy('full_name')->paginate(20)->withQueryString(),
            'filters' => ['status' => $status, 'search' => $search],
            'counts' => ['total' => $total, 'completed' => $completed, 'pending' => $total - $completed],
        ]);
    }

    public function show(Request $request, Student $student) {
        $user = $request->user();
        $isOwner = $student->user_id === $user->id;
        $isAssigned = $user->isPanitia() && AssessmentAssignment::where('student_id', $student->id)->where('assessor_id', $user->id)->exists();
        abort_unless($user->isPembina() || $isOwner || $isAssigned, 403);
        return Inertia::render('PreTests/Show', array_merge($this->metadata(), [
            'student' => $student, 'result' => $student->preTestResult,
        ]));
    }
}
