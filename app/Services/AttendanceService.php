<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService {
    public function roster(Activity $activity): Builder {
        return Student::where(function ($query) use ($activity) {
            $query->where(function ($q) use ($activity) {
                $q->where('status', 'active')->whereHas('memberships', fn ($m) => $m->where('academic_year_id', $activity->academic_year_id)->where('status', 'active'));
            })->orWhereHas('attendances', fn ($a) => $a->where('activity_id', $activity->id));
        });
    }

    public function record(Activity $activity, User $actor, array $entries): void {
        abort_unless($actor->isPembina() || $actor->isPanitia(), 403);
        DB::transaction(function () use ($activity, $actor, $entries) {
            $item = Activity::lockForUpdate()->findOrFail($activity->id);
            if ($item->status === 'cancelled') { throw ValidationException::withMessages(['attendances' => 'Presensi tidak dapat dicatat untuk kegiatan yang dibatalkan.']); }
            $ids = array_column($entries, 'student_id');
            Student::whereIn('id', $ids)->lockForUpdate()->get();
            $allowed = $this->roster($item)->whereIn('id', $ids)->pluck('id')->all();
            foreach ($entries as $index => $entry) {
                if (!in_array((int) $entry['student_id'], $allowed)) {
                    throw ValidationException::withMessages(['attendances.'.$index.'.student_id' => 'Siswa harus aktif dan terdaftar pada semester kegiatan.']);
                }
            }
            foreach ($entries as $entry) {
                Attendance::updateOrCreate(['activity_id' => $item->id, 'student_id' => $entry['student_id']], [
                    'status' => $entry['status'], 'recorded_by' => $actor->id,
                ]);
            }
        });
    }
}
