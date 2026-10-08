<?php
namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use App\Services\StudentProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class StudentController extends Controller
{
    public function index() {
        return Inertia::render('Students/Index', [
            'students' => Student::with(['user', 'memberships.academicYear'])->latest()->get(),
        ]);
    }
    public function create() { return Inertia::render('Students/Create'); }

    private function rules(?Student $student = null): array {
        return array_merge(StudentProfileService::rules(), [
            'student_number' => ['required', 'string', 'max:255',
                Rule::unique('students', 'student_number')->ignore($student?->id),
                Rule::unique('users', 'username')->ignore($student?->user_id)],
            'role' => ['sometimes', 'required', Rule::in(['siswa', 'panitia'])],
            'full_name' => ['required', 'string', 'max:255'],
            'joined_year' => ['required', 'integer', 'min:2000', 'max:'.date('Y')],
        ]);
    }
    private function validatePanitiaClass(array $data, ?Student $student = null): void {
        $role = $data['role'] ?? $student?->user->role ?? 'siswa';
        $class = array_key_exists('class_name', $data) ? ($data['class_name'] ?? '') : ($student?->class_name ?? '');
        if ($role === 'panitia' && !preg_match('/^(XI|XII)(?:\s|$)/i', trim($class))) {
            throw ValidationException::withMessages(['class_name' => 'Panitia EC harus merupakan siswa kelas XI atau XII. Contoh: XI RPL 1.']);
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
}
