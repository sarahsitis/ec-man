<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use App\Services\ClassCatalog;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'locale' => app()->getLocale(),
            'flash' => ['success' => fn () => $request->session()->get('success')],
            'classGroups' => fn () => ClassCatalog::groups(),
            'notificationSummary' => fn () => ['unreadCount' => $request->user()?->unreadNotifications()->where('type', \App\Notifications\ActivityScheduled::class)->count() ?? 0],
            'auth' => [
                'user' => fn () => $request->user() ? [
                    ...$request->user()->toArray(),
                    'is_panitia' => $request->user()->isPanitia(),
                    'committee_class_locked' => $request->user()->committeeClassLocked(),
                ] : null,
            ],
        ];
    }
}
