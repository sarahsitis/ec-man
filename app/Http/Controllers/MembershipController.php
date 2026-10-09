<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Membership;
use App\Models\Student;
use App\Services\ClassCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class MembershipController extends Controller {
    public function index(Request $request) {
        $filters = $request->validate([
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'status' => ['nullable', Rule::in(array_merge(['all'], array_keys(Student::STATUSES)))],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $years = AcademicYear::orderByDesc('name')->orderBy('semester')->get();
        $yearId = $filters['academic_year_id'] ?? $years->firstWhere('is_active', true)?->id ?? $years->first()?->id;
        $status = $filters['status'] ?? 'all';
        $search = $filters['search'] ?? '';
        $query = Membership::with('student:id,full_name,student_number,class_name,status')->where('academic_year_id', $yearId);
        if ($status !== 'all') { $query->where('status', $status); }
        if ($search !== '') { $query->whereHas('student', fn ($q) => $q->where('full_name', 'like', '%'.$search.'%')->orWhere('student_number', 'like', '%'.$search.'%')->orWhere('class_name', 'like', '%'.$search.'%')); }
        return Inertia::render('Memberships/Index', [
            'memberships' => $query->orderByDesc('id')->paginate(20)->withQueryString(), 'years' => $years,
            'students' => Student::whereHas('user', fn ($q) => $q->whereIn('role', ['siswa', 'panitia']))
                ->whereDoesntHave('memberships', fn ($q) => $q->where('academic_year_id', $yearId))
                ->select('id', 'full_name', 'student_number', 'class_name', 'status')->orderBy('full_name')->get(),
            'statuses' => Student::STATUSES, 'filters' => ['academic_year_id' => $yearId, 'status' => $status, 'search' => $search],
        ]);
    }

    public function store(Request $request) {
        $data = $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['required', 'integer', 'distinct', 'exists:students,id'],
            'status' => ['required', Rule::in(array_keys(Student::STATUSES))],
        ]);
        DB::transaction(function () use ($data) {
            AcademicYear::lockForUpdate()->findOrFail($data['academic_year_id']);
            $students = Student::whereIn('id', $data['student_ids'])->lockForUpdate()->get();
            $duplicates = Membership::where('academic_year_id', $data['academic_year_id'])->whereIn('student_id', $data['student_ids'])->exists();
            if ($duplicates) { throw ValidationException::withMessages(['student_ids' => 'Sebagian siswa sudah terdaftar pada semester ini. Muat ulang daftar sebelum menambahkan.']); }
            foreach ($students as $student) {
                if ($student->user->isPembina() || ($data['status'] === 'active' && $student->status !== 'active')) {
                    throw ValidationException::withMessages(['student_ids' => $student->full_name.' tidak dapat didaftarkan sebagai anggota aktif. Periksa status siswa.']);
                }
            }
            foreach ($students as $student) {
                Membership::create(['student_id' => $student->id, 'academic_year_id' => $data['academic_year_id'], 'class_name' => $student->class_name, 'status' => $data['status']]);
            }
        });
        return back()->with('success', count($data['student_ids']).' keanggotaan ditambahkan.');
    }

    public function update(Request $request, Membership $membership) {
        $data = $request->validate(['class_name' => ClassCatalog::rules($membership->class_name), 'status' => ['required', Rule::in(array_keys(Student::STATUSES))]]);
        if ($data['status'] === 'active' && $membership->student->status !== 'active') {
            throw ValidationException::withMessages(['status' => 'Aktifkan status siswa terlebih dahulu sebelum mengaktifkan keanggotaan.']);
        }
        $membership->update($data);
        return back()->with('success', 'Keanggotaan diperbarui.');
    }

    public function destroy(Membership $membership) {
        $membership->delete();
        return back()->with('success', 'Keanggotaan semester dihapus. Profil dan riwayat presensi tetap tersimpan.');
    }
}
