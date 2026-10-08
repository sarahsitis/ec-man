<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\StudentProfileService;
use App\Models\Student;
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

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
