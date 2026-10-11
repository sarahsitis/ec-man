<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Notifications\ActivityScheduled;
use App\Support\TablePagination;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['status' => ['nullable', Rule::in(['all', 'unread'])]]);
        $status = $filters['status'] ?? 'all';
        $query = $request->user()->notifications()->where('type', ActivityScheduled::class);
        if ($status === 'unread') {
            $query->whereNull('read_at');
        }
        $notifications = $query->orderByDesc('created_at')->orderByDesc('id')->paginate(TablePagination::perPage($request))->withQueryString();
        $ids = $notifications->getCollection()->map(fn ($item) => $item->data['activity_id'])->unique()->all();
        $activities = Activity::visibleTo($request->user())->whereIn('id', $ids)->get()->keyBy('id');
        $notifications->through(function ($notification) use ($activities) {
            $data = $notification->data;
            $activity = $activities->get($data['activity_id']);
            if ($activity) {
                $data = array_merge($data, [
                    'title' => $activity->title, 'activity_date' => $activity->activity_date->format('Y-m-d'),
                    'start_time' => substr($activity->start_time, 0, 5),
                    'end_time' => $activity->end_time ? substr($activity->end_time, 0, 5) : null,
                    'location' => $activity->location, 'status' => $activity->status,
                ]);
            }

            return ['id' => $notification->id, 'data' => $data, 'read_at' => $notification->read_at?->toIso8601String(), 'created_at' => $notification->created_at->toIso8601String(), 'activity_available' => (bool) $activity];
        });

        return Inertia::render('Notifications/Index', ['notifications' => $notifications, 'filters' => ['status' => $status], 'activityStatuses' => Activity::STATUSES]);
    }

    public function summary(Request $request)
    {
        return response()->json(['unreadCount' => $request->user()->unreadNotifications()->where('type', ActivityScheduled::class)->count()])->header('Cache-Control', 'private, no-store');
    }

    private function owned(Request $request, string $notification)
    {
        return $request->user()->notifications()->where('type', ActivityScheduled::class)->findOrFail($notification);
    }

    public function read(Request $request, string $notification)
    {
        $this->owned($request, $notification)->markAsRead();

        return back(303);
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications()->where('type', ActivityScheduled::class)->update(['read_at' => now()]);

        return back(303)->with('success', __('Semua notifikasi ditandai sudah dibaca.'));
    }

    public function open(Request $request, string $notification)
    {
        $item = $this->owned($request, $notification);
        $activity = Activity::visibleTo($request->user())->whereKey($item->data['activity_id'])->first();
        $item->markAsRead();
        if (! $activity) {
            return redirect()->route('notifications.index', [], 303)->with('success', __('Kegiatan tidak lagi tersedia untuk akun Anda.'));
        }

        return redirect()->route('activities.show', $activity, 303);
    }
}
