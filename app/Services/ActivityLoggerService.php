<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLoggerService
{
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
}
