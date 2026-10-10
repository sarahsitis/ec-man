<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\ActivityScheme;
use App\Models\Attendance;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ActivityController extends Controller {
    private function staff(Request $request): bool {
        return $request->user()->isPembina() || $request->user()->isPanitia();
    }

    private function scope($query, Request $request) {
        if (!$this->staff($request)) {
            $studentId = $request->user()->student?->id;
            $query->where(fn ($q) => $q->whereHas('academicYear.memberships', fn ($m) => $m->where('student_id', $studentId))
                ->orWhereHas('attendances', fn ($a) => $a->where('student_id', $studentId)));
        }
        return $query;
    }

    public function index(Request $request) {
        $filters = $request->validate([
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'status' => ['nullable', Rule::in(array_merge(['all'], array_keys(Activity::STATUSES)))],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $status = $filters['status'] ?? 'all'; $search = $filters['search'] ?? '';
        $yearId = $filters['academic_year_id'] ?? null;
        $query = $this->scope(Activity::query(), $request)->with('academicYear');
        if ($yearId) { $query->where('academic_year_id', $yearId); }
        if ($status !== 'all') { $query->where('status', $status); }
        if ($search !== '') { $query->where(fn ($q) => $q->where('title', 'like', '%'.$search.'%')->orWhere('category', 'like', '%'.$search.'%')); }
        return Inertia::render('Activities/Index', [
            'activities' => $query->withCount(['attendances as present_count' => fn ($q) => $q->where('status', 'hadir')])->orderByDesc('activity_date')->orderBy('start_time')->paginate(20)->withQueryString(),
            'years' => AcademicYear::orderByDesc('name')->get(), 'statuses' => Activity::STATUSES,
            'filters' => ['academic_year_id' => $yearId, 'status' => $status, 'search' => $search],
        ]);
    }

    private function form(?Activity $activity = null) {
        return Inertia::render('Activities/Form', [
            'activity' => $activity, 'years' => AcademicYear::orderByDesc('name')->orderBy('semester')->get(),
            'schemes' => ActivityScheme::orderBy('name')->get(), 'statuses' => Activity::STATUSES,
        ]);
    }

    public function create() { return $this->form(); }
    public function edit(Activity $activity) { return $this->form($activity); }

    private function data(Request $request): array {
        return $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'activity_scheme_id' => ['nullable', 'integer', 'exists:activity_schemes,id'],
            'title' => ['required', 'string', 'max:255'], 'category' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:5000'], 'objectives' => ['nullable', 'string', 'max:5000'], 'agenda' => ['nullable', 'string', 'max:10000'],
            'activity_date' => ['required', 'date_format:Y-m-d'], 'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'location' => ['nullable', 'string', 'max:255'], 'pic' => ['nullable', 'string', 'max:255'],
            'target_audience' => ['nullable', 'string', 'max:255'], 'status' => ['required', Rule::in(array_keys(Activity::STATUSES))],
        ]);
    }

    public function store(Request $request) {
        $activity = Activity::create(array_merge($this->data($request), ['created_by' => $request->user()->id]));
        return redirect()->route('activities.show', $activity)->with('success', 'Kegiatan dibuat.');
    }

    public function update(Request $request, Activity $activity) {
        $data = $this->data($request);
        DB::transaction(function () use ($activity, $data) {
            $item = Activity::lockForUpdate()->findOrFail($activity->id);
            if ((int) $data['academic_year_id'] !== $item->academic_year_id && $item->attendances()->exists()) {
                throw ValidationException::withMessages(['academic_year_id' => 'Semester kegiatan dengan riwayat presensi tidak dapat dipindahkan.']);
            }
            $item->update($data);
        });
        return redirect()->route('activities.show', $activity)->with('success', 'Kegiatan diperbarui.');
    }

    public function show(Request $request, Activity $activity, AttendanceService $service) {
        abort_unless($this->scope(Activity::whereKey($activity->id), $request)->exists(), 403);
        $staff = $this->staff($request);
        return Inertia::render('Activities/Show', [
            'activity' => $activity->load(['academicYear', 'creator:id,name']),
            'students' => $staff ? $service->roster($activity)->select('id', 'full_name', 'student_number', 'class_name', 'status')->orderBy('full_name')->get() : [],
            'attendances' => $staff ? $activity->attendances()->with('recorder:id,name')->get() : [],
            'myAttendance' => $staff ? null : $activity->attendances()->where('student_id', $request->user()->student?->id)->first(),
            'attendanceStatuses' => Attendance::STATUSES, 'activityStatuses' => Activity::STATUSES,
        ]);
    }

    public function recordAttendance(Request $request, Activity $activity, AttendanceService $service) {
        abort_unless($this->staff($request), 403);
        $data = $request->validate([
            'attendances' => ['required', 'array', 'min:1'],
            'attendances.*' => ['required', 'array:student_id,status'],
            'attendances.*.student_id' => ['required', 'integer', 'distinct', 'exists:students,id'],
            'attendances.*.status' => ['required', Rule::in(array_keys(Attendance::STATUSES))],
        ]);
        $service->record($activity, $request->user(), $data['attendances']);
        return back()->with('success', 'Presensi '.count($data['attendances']).' siswa disimpan.');
    }

    public function destroy(Activity $activity) {
        DB::transaction(function () use ($activity) {
            $item = Activity::lockForUpdate()->findOrFail($activity->id);
            if ($item->attendances()->exists()) { throw ValidationException::withMessages(['activity' => 'Kegiatan memiliki riwayat presensi. Gunakan status dibatalkan agar riwayat tetap tersimpan.']); }
            $item->delete();
        });
        return redirect()->route('activities.index')->with('success', 'Kegiatan dihapus.');
    }
}
