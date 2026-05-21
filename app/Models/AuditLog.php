<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'company', 'action',
        'model_type', 'model_id',
        'old_values', 'new_values',
        'ip_address', 'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $action, Model $model, ?array $old = null, ?array $new = null): void
    {
        $map    = ['create' => 'created', 'update' => 'updated', 'delete' => 'deleted'];
        $action = $map[$action] ?? $action;
        $user   = auth()->user();

        static::create([
            'user_id'    => $user?->id,
            'company'    => $user?->company,
            'action'     => $action,
            'model_type' => class_basename($model),
            'model_id'   => $model->getKey(),
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
