<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait LogsActivity
{
    /**
     * Boot the trait and register Eloquent event listeners.
     */
    protected static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            static::logActivity($model, 'created');
        });

        static::updated(function ($model) {
            // Only log if something actually changed
            if ($model->wasChanged()) {
                static::logActivity($model, 'updated');
            }
        });

        static::deleted(function ($model) {
            static::logActivity($model, 'deleted');
        });
    }

    /**
     * Record the activity in the database.
     */
    protected static function logActivity($model, string $action): void
    {
        // Don't log activity logs themselves to avoid recursion
        if ($model instanceof ActivityLog) {
            return;
        }

        ActivityLog::create([
            'user_id'      => Auth::id(),
            'action'       => "model.{$action}",
            'subject_type' => get_class($model),
            'subject_id'   => $model->getKey(),
            'description'  => ucfirst($action) . ' ' . class_basename($model),
            'properties'   => [
                'attributes' => $model->getAttributes(),
                'old'        => $action === 'updated' ? array_intersect_key($model->getOriginal(), $model->getChanges()) : null,
            ],
            'ip_address'   => Request::ip(),
            'user_agent'   => Request::userAgent(),
        ]);
    }
}
