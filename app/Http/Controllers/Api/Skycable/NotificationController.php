<?php

namespace App\Http\Controllers\Api\Skycable;

use App\Http\Concerns\CachesSkycableResponses;
use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Services\RedisCache;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use CachesSkycableResponses;

    /** GET /skycable/notifications */
    public function index(Request $request)
    {
        $userId   = $request->user()->id;
        $cacheKey = "cache:skycable:notifications.user.{$userId}";

        return $this->cachedResponse(
            cacheKey:   $cacheKey,
            ttl:        30,  // 30s — short because notifications change often
            callback:   function () use ($userId) {
                $notifications = AppNotification::where('user_id', $userId)
                    ->orderByRaw('read_at IS NOT NULL')
                    ->latest()
                    ->limit(30)
                    ->get();

                $unreadCount = AppNotification::where('user_id', $userId)
                    ->unread()
                    ->count();

                return [
                    'notifications' => $notifications,
                    'unread_count'  => $unreadCount,
                ];
            },
            request:    $request,
            visibility: 'private',
        );
    }

    /** GET /skycable/notifications/unread-count */
    public function unreadCount(Request $request)
    {
        $userId   = $request->user()->id;
        $cacheKey = "cache:skycable:notifications.unread.{$userId}";

        return $this->cachedResponse(
            cacheKey:   $cacheKey,
            ttl:        15,  // 15s — very light poll
            callback:   fn () => [
                'unread_count' => AppNotification::where('user_id', $userId)->unread()->count(),
            ],
            request:    $request,
            visibility: 'private',
        );
    }

    /** POST /skycable/notifications/{id}/read */
    public function markRead(Request $request, AppNotification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $notification->update(['read_at' => now()]);

        // Immediately bust this user's notification cache so next poll shows fresh data
        $userId = $request->user()->id;
        RedisCache::forget(
            "cache:skycable:notifications.user.{$userId}",
            "cache:skycable:notifications.unread.{$userId}"
        );

        return response()->json(['ok' => true]);
    }

    /** POST /skycable/notifications/read-all */
    public function markAllRead(Request $request)
    {
        $userId = $request->user()->id;

        AppNotification::where('user_id', $userId)->unread()->update(['read_at' => now()]);

        RedisCache::forget(
            "cache:skycable:notifications.user.{$userId}",
            "cache:skycable:notifications.unread.{$userId}"
        );

        return response()->json(['ok' => true]);
    }
}
