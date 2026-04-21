<?php

namespace App\Observers;

use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

class ActivityLogObserver
{
    /**
     * Handle the Model "created" event.
     */
    public function created(Model $model): void
    {
        ActivityLogger::log(
            'Created',
            'Created ' . class_basename($model) . ' data',
            class_basename($model),
            $model->toArray()
        );
    }

    /**
     * Handle the Model "updated" event.
     */
    public function updated(Model $model): void
    {
        $oldValues = [];
        $newValues = [];

        foreach ($model->getChanges() as $key => $value) {
            if ($key === 'updated_at') {
                continue;
            }
            $oldValues[$key] = $model->getOriginal($key);
            $newValues[$key] = $value;
        }

        // Only log if there are actual changes
        if (!empty($newValues)) {
            ActivityLogger::log(
                'Updated',
                'Updated ' . class_basename($model) . ' data',
                class_basename($model),
                [
                    'old' => $oldValues,
                    'new' => $newValues,
                ]
            );
        }
    }

    /**
     * Handle the Model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        ActivityLogger::log(
            'Deleted',
            'Deleted ' . class_basename($model) . ' data',
            class_basename($model),
            $model->toArray()
        );
    }
}
