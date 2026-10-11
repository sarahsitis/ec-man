<?php

namespace App\Notifications;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Notifications\Notification;

class ActivityScheduled extends Notification
{
    public function __construct(private Activity $activity, private User $creator) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'activity_id' => $this->activity->id,
            'title' => $this->activity->title,
            'activity_date' => $this->activity->activity_date->format('Y-m-d'),
            'start_time' => substr($this->activity->start_time, 0, 5),
            'end_time' => $this->activity->end_time ? substr($this->activity->end_time, 0, 5) : null,
            'location' => $this->activity->location,
            'creator_name' => $this->creator->name,
            'status' => $this->activity->status,
        ];
    }
}
