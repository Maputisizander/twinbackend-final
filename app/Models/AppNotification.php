<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppNotification extends Model
{
    protected $table = 'app_notifications';

    protected $fillable = ['user_id', 'type', 'title', 'body', 'data', 'read_at'];

    protected $casts = [
        'data'    => 'array',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /** Send a notification to one or more users. */
    public static function send(int|array $userIds, string $type, string $title, string $body, array $data = []): void
    {
        $ids = is_array($userIds) ? $userIds : [$userIds];
        $now = now();

        $rows = array_map(fn($id) => [
            'user_id'    => $id,
            'type'       => $type,
            'title'      => $title,
            'body'       => $body,
            'data'       => json_encode($data),
            'read_at'    => null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $ids);

        static::insert($rows);
    }

    /** Notify all admins and executives. */
    public static function notifyAdmins(string $type, string $title, string $body, array $data = []): void
    {
        $adminIds = User::where(function ($q) {
            $q->where('is_admin', true)->orWhere('is_executive', true);
        })->pluck('id')->toArray();

        if (!empty($adminIds)) {
            static::send($adminIds, $type, $title, $body, $data);
        }
    }

    /** Notify all warehouse users associated with a specific warehouse (via subcontractor). */
    public static function notifyWarehouseUsers(int $warehouseId, string $type, string $title, string $body, array $data = []): void
    {
        $warehouse = \App\Models\Warehouse::find($warehouseId);
        if (!$warehouse) return;

        $userIds = User::where('subcontractor_id', $warehouse->subcontractor_id)
            ->pluck('id')->toArray();

        // Also notify admins
        $adminIds = User::where(function ($q) {
            $q->where('is_admin', true)->orWhere('is_executive', true);
        })->pluck('id')->toArray();

        $all = array_unique(array_merge($userIds, $adminIds));
        if (!empty($all)) {
            static::send($all, $type, $title, $body, $data);
        }
    }
}
