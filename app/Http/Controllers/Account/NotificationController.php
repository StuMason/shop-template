<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * The customer's notification feed.
     */
    public function index(Request $request): Response
    {
        $notifications = $request->user()
            ->notifications()
            ->paginate(15)
            ->through(fn ($notification): array => [
                'id' => $notification->id,
                'message' => $notification->data['message'] ?? '',
                'read' => $notification->read_at !== null,
                'created_at' => $notification->created_at?->diffForHumans(),
            ]);

        return Inertia::render('account/notifications', [
            'notifications' => $notifications,
            'unreadCount' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark everything as read.
     */
    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
