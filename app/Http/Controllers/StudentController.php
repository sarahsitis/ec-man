<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
            'student_number' => 'required|string|unique:students,student_number',
            'full_name' => 'required|string|max:255',
            'joined_year' => 'required|integer',
        ]);

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

        return redirect()->route('students.index')->with('success', 'Anggota Siswa ditambahkan.');
    }
}
