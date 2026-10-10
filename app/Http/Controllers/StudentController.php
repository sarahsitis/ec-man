<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::with(['user', 'memberships.academicYear'])->latest()->get();
        return Inertia::render('Students/Index', [
            'students' => $students
        ]);
    }

    public function create()
    {
        return Inertia::render('Students/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_number' => ['required', 'string', 'max:255', 'unique:students,student_number', 'unique:users,username'],
            'full_name' => 'required|string|max:255',
            'joined_year' => 'required|integer|min:2000|max:'.date('Y'),
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['full_name'],
                'username' => $validated['student_number'],
                'role' => 'siswa',
                'password' => Hash::make($validated['student_number']), // default pass
            ]);

            Student::create([
                'user_id' => $user->id,
                'student_number' => $validated['student_number'],
                'full_name' => $validated['full_name'],
                'joined_year' => $validated['joined_year'],
            ]);
        });

        return redirect()->route('students.index')->with('success', 'Anggota Siswa ditambahkan.');
    }

    public function edit(Student $student)
    {
        return Inertia::render('Students/Edit', ['student' => $student]);
    }

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'student_number' => [
                'required', 'string', 'max:255',
                Rule::unique('students', 'student_number')->ignore($student->id),
                Rule::unique('users', 'username')->ignore($student->user_id),
            ],
            'full_name' => 'required|string|max:255',
            'joined_year' => 'required|integer|min:2000|max:'.date('Y'),
        ]);

        DB::transaction(function () use ($student, $validated) {
            $student->update($validated);
            $student->user()->update([
                'name' => $validated['full_name'],
                'username' => $validated['student_number'],
            ]);
        });

        return redirect()->route('students.index')->with('success', 'Data anggota diperbarui. Password tetap sama.');
    }
}
