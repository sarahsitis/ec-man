<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AcademicYearController extends Controller {
    public function index() {
        $now = now('Asia/Jakarta');
        $startYear = $now->month >= 7 ? $now->year : $now->year - 1;
        return Inertia::render('AcademicYears/Index', [
            'years' => AcademicYear::withCount(['memberships', 'activities'])->orderByDesc('name')->orderBy('semester')->get(),
            'defaults' => ['name' => $startYear.'/'.($startYear + 1), 'semester' => $now->month >= 7 ? 'ganjil' : 'genap'],
        ]);
    }

    public function store(Request $request) {
        $data = $request->validate([
            'name' => ['bail', 'required', 'string', 'regex:/^\d{4}\/\d{4}$/', Rule::unique('academic_years', 'name')->where('semester', $request->input('semester')),
                function ($attribute, $value, $fail) { $years = explode('/', $value); if (count($years) === 2 && (int) $years[1] !== (int) $years[0] + 1) { $fail('Tahun ajaran harus berurutan, misalnya 2026/2027.'); } }],
            'semester' => ['required', Rule::in(['ganjil', 'genap'])], 'is_active' => ['required', 'boolean'],
        ]);
        DB::transaction(function () use ($data) {
            AcademicYear::query()->lockForUpdate()->get();
            if ($data['is_active']) { AcademicYear::where('is_active', true)->update(['is_active' => false]); }
            AcademicYear::create($data);
        });
        return back()->with('success', 'Tahun ajaran ditambahkan.');
    }

    public function activate(AcademicYear $academicYear) {
        DB::transaction(function () use ($academicYear) {
            AcademicYear::query()->lockForUpdate()->get();
            AcademicYear::where('is_active', true)->update(['is_active' => false]);
            $academicYear->update(['is_active' => true]);
        });
        return back()->with('success', 'Tahun ajaran aktif diperbarui.');
    }
}
