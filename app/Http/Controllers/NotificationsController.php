<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationsController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless((bool) config('features.notifications.enabled', true), 404);

        $notifications = $request->user()->notifications()->paginate(20)->withQueryString();

        return Inertia::render('notifications/index', [
            'notifications' => $notifications,
            'unreadCount' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request, string $notificationId): RedirectResponse
    {
        abort_unless((bool) config('features.notifications.enabled', true), 404);

        $notification = $request->user()->notifications()->whereKey($notificationId)->firstOrFail();
        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        abort_unless((bool) config('features.notifications.enabled', true), 404);

        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }

    public function destroy(Request $request, string $notificationId): RedirectResponse
    {
        abort_unless((bool) config('features.notifications.enabled', true), 404);

        $request->user()->notifications()->whereKey($notificationId)->delete();

        return back();
    }
}
