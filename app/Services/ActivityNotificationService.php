<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\User;
use App\Notifications\ActivityScheduled;
use Illuminate\Support\Facades\Notification;

class ActivityNotificationService
{
    public function created(Activity $activity, User $creator): int
    {
        if ($activity->status !== 'scheduled') {
            return 0;
        }
        $count = 0;
        User::whereIn('role', ['siswa', 'panitia'])
            ->whereHas('student', fn ($students) => $students->where('status', 'active')
                ->whereHas('memberships', fn ($memberships) => $memberships->where('academic_year_id', $activity->academic_year_id)->where('status', 'active')))
            ->chunkById(200, function ($users) use ($activity, $creator, &$count) {
                Notification::send($users, new ActivityScheduled($activity, $creator));
                $count += $users->count();
            });

        return $count;
    }
}
