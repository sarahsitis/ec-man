<?php
namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use App\Models\AssessmentAssignment;
use App\Models\AssessmentEvent;
use App\Models\Activity;
use App\Models\Attendance;
use App\Services\StudentProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class StudentController extends Controller
{
    public function index(Request $request) {
        $filters = $request->validate(['status' => ['nullable', Rule::in(array_merge(['all'], array_keys(Student::STATUSES)))], 'search' => ['nullable', 'string', 'max:100']]);
        $status = $filters['status'] ?? 'all';
        $search = $filters['search'] ?? '';
        $query = Student::with(['user', 'memberships.academicYear']);
        if ($status !== 'all') { $query->where('status', $status); }
        if ($search !== '') { $query->where(fn ($q) => $q->where('full_name', 'like', '%'.$search.'%')->orWhere('student_number', 'like', '%'.$search.'%')->orWhere('class_name', 'like', '%'.$search.'%')); }
        return Inertia::render('Students/Index', [
            'students' => $query->latest()->get(), 'statuses' => Student::STATUSES,
            'filters' => ['status' => $status, 'search' => $search],
        ]);
    }
    public function create() { return Inertia::render('Students/Create'); }

    private function rules(?Student $student = null): array {
        return array_merge(StudentProfileService::rules($student?->class_name), [
            'student_number' => ['required', 'string', 'max:255',
                Rule::unique('students', 'student_number')->ignore($student?->id),
                Rule::unique('users', 'username')->ignore($student?->user_id)],
            'role' => ['sometimes', 'required', Rule::in(['siswa', 'panitia'])],
            'status' => ['sometimes', 'required', Rule::in(array_keys(Student::STATUSES))],
            'full_name' => ['required', 'string', 'max:255'],
            'joined_year' => ['required', 'integer', 'min:2000', 'max:'.date('Y')],
        ]);
    }
    private function validatePanitiaClass(array $data, ?Student $student = null): void {
        $role = $data['role'] ?? $student?->user->role ?? 'siswa';
        $class = array_key_exists('class_name', $data) ? ($data['class_name'] ?? '') : ($student?->class_name ?? '');
        if ($role === 'panitia' && !preg_match('/^(XI|XII)(?:\s|$)/i', trim($class))) {
            throw ValidationException::withMessages(['class_name' => 'Panitia EC harus merupakan siswa kelas XI atau XII. Contoh: XI PPLG - RPL 1.']);
        }
    }
    public function store(Request $request, StudentProfileService $service) {
        $data = $request->validate($this->rules());
        $this->validatePanitiaClass($data);
        $service->save(new Student(), $data, $request->file('profile_photo'), function (Student $student) use ($data) {
            $user = User::create([
                'name' => $data['full_name'], 'username' => $data['student_number'],
                'role' => $data['role'] ?? 'siswa', 'password' => Hash::make($data['student_number']),
            ]);
            $student->user_id = $user->id;
        });
        return redirect()->route('students.index')->with('success', 'Anggota ditambahkan.');
    }
    public function edit(Student $student) {
        return Inertia::render('Students/Edit', ['student' => $student->load('user')]);
    }
    public function update(Request $request, Student $student, StudentProfileService $service) {
        $data = $request->validate($this->rules($student));
        $this->validatePanitiaClass($data, $student);
        $service->save($student, $data, $request->file('profile_photo'), function () use ($student, $data) {
            $student->user()->update(['name' => $data['full_name'], 'username' => $data['student_number'], 'role' => $data['role'] ?? $student->user->role]);
        });
        return redirect()->route('students.index')->with('success', 'Anggota diperbarui. Password tetap sama.');
    }
    public function photo(Request $request, Student $student) {
        abort_unless($request->user()->isPembina() || $request->user()->id === $student->user_id, 403);
        abort_unless($student->profile_photo_path && Storage::disk('local')->exists($student->profile_photo_path), 404);
        return Storage::disk('local')->response($student->profile_photo_path, null, [
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function bulkStatus(Request $request) {
        $data = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['required', 'integer', 'distinct', 'exists:students,id'],
            'status' => ['required', Rule::in(array_keys(Student::STATUSES))],
        ]);
        Student::whereIn('id', $data['student_ids'])->update(['status' => $data['status']]);
        return back()->with('success', 'Status '.count($data['student_ids']).' siswa diperbarui.');
    }

    public function updateStatus(Request $request, Student $student) {
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(Student::STATUSES))]]);
        $student->update($data);
        $message = $data['status'] === 'alumni' ? 'Siswa ditandai lulus dan menjadi alumni.' : 'Status siswa diubah menjadi '.Student::STATUSES[$data['status']].'.';
        return back()->with('success', $message);
    }

    public function destroy(Student $student) {
        $photo = $student->profile_photo_path;
        DB::transaction(function () use ($student) {
            $user = User::lockForUpdate()->findOrFail($student->user_id);
            $member = Student::lockForUpdate()->findOrFail($student->id);
            $hasHistory = $member->memberships()->exists() || $member->attendances()->exists() || $member->preTestResult()->exists()
                || AssessmentAssignment::where('student_id', $member->id)->exists()
                || AssessmentAssignment::where('assessor_id', $user->id)->orWhere('created_by', $user->id)->orWhere('reviewed_by', $user->id)->exists()
                || AssessmentEvent::where('actor_id', $user->id)->exists()
                || Activity::where('created_by', $user->id)->exists() || Attendance::where('recorded_by', $user->id)->exists();
            if ($hasHistory) { throw ValidationException::withMessages(['student' => 'Siswa memiliki riwayat keanggotaan, kegiatan, atau penilaian. Ubah status menjadi nonaktif, keluar, atau alumni untuk mempertahankan riwayat.']); }
            abort_if($user->isPembina(), 403);
            $member->delete();
            $user->delete();
        });
        if ($photo) { Storage::disk('local')->delete($photo); }
        return redirect()->route('students.index')->with('success', 'Siswa dan akun terkait dihapus.');
    }
}
