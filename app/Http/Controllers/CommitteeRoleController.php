<?php

namespace App\Http\Controllers;

use App\Models\CommitteeRole;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CommitteeRoleController extends Controller
{
    public function index()
    {
        $today = now('Asia/Jakarta')->toDateString();
        $roles = CommitteeRole::with(['user.student', 'appointedBy:id,name', 'revokedBy:id,name'])->latest()->get();
        $roles->each(function ($role) use ($today) {
            $role->setAttribute('status', $role->revoked_at ? 'revoked'
                : ($role->ends_on->toDateString() < $today ? 'expired'
                : ($role->starts_on->toDateString() > $today ? 'scheduled'
                : ($role->user->role === 'panitia' && $role->user->studentEligibleForCommittee() ? 'active' : 'suspended'))));
        });
        return Inertia::render('CommitteeRoles/Index', [
            'appointments' => $roles,
            'candidates' => Student::with('user:id,name,role')->where('status', 'active')
                ->whereHas('user', fn ($q) => $q->whereIn('role', ['siswa', 'panitia']))
                ->where(fn ($q) => $q->where('class_name', 'like', 'XI %')->orWhere('class_name', 'like', 'XII %'))
                ->orderBy('full_name')->get(['id', 'user_id', 'full_name', 'student_number', 'class_name']),
            'today' => $today,
        ]);
    }

    private function dates(Request $request): array
    {
        return $request->validate([
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on', 'after_or_equal:'.now('Asia/Jakarta')->toDateString()],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function validateAppointment(User $user, array $dates, ?int $ignore = null): void
    {
        if ($user->isPembina() || !$user->studentEligibleForCommittee()) {
            throw ValidationException::withMessages(['user_id' => 'Pilih siswa aktif kelas XI atau XII sebagai panitia.']);
        }
        $overlap = $user->committeeRoles()->whereNull('revoked_at')
            ->where('starts_on', '<=', $dates['ends_on'])->where('ends_on', '>=', $dates['starts_on'])
            ->when($ignore, fn ($q) => $q->where('id', '!=', $ignore))->exists();
        if ($overlap) {
            throw ValidationException::withMessages(['starts_on' => 'Masa tugas bertumpang tindih dengan penugasan siswa ini. Edit masa tugas yang sudah ada.']);
        }
    }

    public function store(Request $request)
    {
        $identity = $request->validate(['user_id' => ['required', 'integer', 'exists:users,id']]);
        $dates = $this->dates($request);
        DB::transaction(function () use ($request, $identity, $dates) {
            $user = User::lockForUpdate()->findOrFail($identity['user_id']);
            $this->validateAppointment($user, $dates);
            $user->committeeRoles()->create([...$dates, 'appointed_by' => $request->user()->id]);
            $user->update(['role' => 'panitia']);
        });
        return back()->with('success', 'Panitia diangkat. Hak akses berlaku sesuai masa tugas.');
    }

    public function update(Request $request, CommitteeRole $committeeRole)
    {
        $dates = $this->dates($request);
        DB::transaction(function () use ($committeeRole, $dates) {
            $user = User::lockForUpdate()->findOrFail($committeeRole->user_id);
            $role = CommitteeRole::lockForUpdate()->findOrFail($committeeRole->id);
            if ($role->revoked_at) {
                throw ValidationException::withMessages(['starts_on' => 'Hak yang dicabut tidak dapat diaktifkan ulang. Buat penugasan baru.']);
            }
            $this->validateAppointment($user, $dates, $role->id);
            $role->update($dates);
            $user->update(['role' => 'panitia']);
        });
        return back()->with('success', 'Masa tugas panitia diperbarui.');
    }

    public function destroy(Request $request, CommitteeRole $committeeRole)
    {
        $data = $request->validate(['revoke_reason' => ['nullable', 'string', 'max:2000']]);
        DB::transaction(function () use ($request, $committeeRole, $data) {
            $user = User::lockForUpdate()->findOrFail($committeeRole->user_id);
            $role = CommitteeRole::lockForUpdate()->findOrFail($committeeRole->id);
            if ($role->revoked_at) { return; }
            $role->update(['revoked_at' => now(), 'revoked_by' => $request->user()->id, 'revoke_reason' => $data['revoke_reason'] ?? null]);
            if (!$user->committeeRoles()->upcomingOrActive()->exists() && $user->role === 'panitia') {
                $user->update(['role' => 'siswa']);
            }
        });
        return back()->with('success', 'Hak panitia dicabut. Riwayat penugasan dan penilaian tetap tersimpan.');
    }
}
