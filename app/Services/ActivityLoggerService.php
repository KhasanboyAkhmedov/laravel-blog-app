<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLoggerService
{
    /**
     * Log an event tied to an Eloquent model instance.
     * Used by observers (Post created/updated/deleted).
     */
    public static function log(Model $model, string $action, ?array $payload = null): void
    {
        ActivityLog::create([
            'user_id'  => auth()->id(),
            'action'   => $action,
            'model'    => get_class($model),
            'model_id' => $model->getKey(),
            'payload'  => $payload,
        ]);
    }

    /**
     * Log an event without a model instance.
     * Used for auth events, role changes, profile updates, etc.
     */
    public static function logRaw(
        string $action,
        string $model,
        int $modelId,
        ?array $payload = null,
        ?int $userId = null
    ): void {
        ActivityLog::create([
            'user_id'  => $userId ?? auth()->id(),
            'action'   => $action,
            'model'    => $model,
            'model_id' => $modelId,
            'payload'  => $payload,
        ]);
    }
}
