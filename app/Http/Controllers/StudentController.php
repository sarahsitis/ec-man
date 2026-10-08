<?php
namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use App\Services\StudentProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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
            'full_name' => ['required', 'string', 'max:255'],
            'joined_year' => ['required', 'integer', 'min:2000', 'max:'.date('Y')],
        ]);
    }
    public function store(Request $request, StudentProfileService $service) {
        $data = $request->validate($this->rules());
        $service->save(new Student(), $data, $request->file('profile_photo'), function (Student $student) use ($data) {
            $user = User::create([
                'name' => $data['full_name'], 'username' => $data['student_number'],
                'role' => 'siswa', 'password' => Hash::make($data['student_number']),
            ]);
            $student->user_id = $user->id;
        });
        return redirect()->route('students.index')->with('success', 'Anggota ditambahkan.');
    }
    public function edit(Student $student) {
        return Inertia::render('Students/Edit', ['student' => $student]);
    }
    public function update(Request $request, Student $student, StudentProfileService $service) {
        $data = $request->validate($this->rules($student));
        $service->save($student, $data, $request->file('profile_photo'), function () use ($student, $data) {
            $student->user()->update(['name' => $data['full_name'], 'username' => $data['student_number']]);
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
