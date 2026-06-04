<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $notifications = Notification::query()
            ->with('from:id,name,email')
            ->where('to_id', $userId)
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (Notification $notification) => $this->notificationPayload($notification));

        return response()->json([
            'unread_count' => $this->unreadCount($userId),
            'notifications' => $notifications,
        ]);
    }

    public function markRead(Request $request, int $notificationId): JsonResponse
    {
        $notification = Notification::query()
            ->with('from:id,name,email')
            ->where('to_id', $request->user()->id)
            ->findOrFail($notificationId);

        $notification->update(['is_read' => true]);

        return response()->json([
            'notification' => $this->notificationPayload($notification->fresh('from:id,name,email')),
            'unread_count' => $this->unreadCount($request->user()->id),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        Notification::query()
            ->where('to_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'unread_count' => 0,
        ]);
    }

    private function unreadCount(int $userId): int
    {
        return Notification::query()
            ->where('to_id', $userId)
            ->where('is_read', false)
            ->count();
    }

    private function notificationPayload(Notification $notification): array
    {
        return [
            'id' => $notification->id,
            'from_id' => $notification->from_id,
            'from_name' => $notification->from?->name ?? 'System',
            'from_email' => $notification->from?->email,
            'message' => $notification->message,
            'is_read' => $notification->is_read,
            'created_at' => $notification->created_at,
        ];
    }
}
