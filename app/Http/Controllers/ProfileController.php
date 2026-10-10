<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\StudentProfileService;
use App\Models\Student;
use App\Models\AssessmentAssignment;
use App\Models\AssessmentEvent;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\CommitteeRole;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'student' => $request->user()->student,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request, StudentProfileService $service): RedirectResponse
    {
        $user = $request->user();
        if (!$user->isPembina()) {
            // Accommodate existing example accounts without a member record.
            $student = $user->student ?? new Student([
                'user_id' => $user->id, 'student_number' => $user->username,
                'full_name' => $user->name, 'joined_year' => date('Y'),
            ]);
            $service->save($student, $request->validated(), $request->file('profile_photo'));
        } else {
            DB::transaction(function () use ($user, $request) {
                $user->fill($request->validated())->save();
                $user->student?->update(['full_name' => $user->name, 'student_number' => $user->username]);
            });
        }
        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        $hasAssignments = AssessmentAssignment::where('assessor_id', $user->id)
            ->orWhere('created_by', $user->id)->orWhere('reviewed_by', $user->id)->exists();
        $hasGrades = $user->student && AssessmentAssignment::where('student_id', $user->student->id)->exists();
        $hasEvents = AssessmentEvent::where('actor_id', $user->id)->exists();
        $hasPreTest = $user->student && $user->student->preTestResult()->exists();
        $hasMembership = $user->student && ($user->student->memberships()->exists() || $user->student->attendances()->exists());
        $hasActivity = Activity::where('created_by', $user->id)->exists() || Attendance::where('recorded_by', $user->id)->exists();
        $hasCommitteeHistory = CommitteeRole::where('user_id', $user->id)->orWhere('appointed_by', $user->id)->orWhere('revoked_by', $user->id)->exists();
        if ($hasAssignments || $hasGrades || $hasEvents || $hasPreTest || $hasMembership || $hasActivity || $hasCommitteeHistory) {
            throw ValidationException::withMessages(['password' => 'Akun ini terkait riwayat keanggotaan, kegiatan, atau penilaian dan tidak dapat dihapus. Hubungi pembina.']);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
