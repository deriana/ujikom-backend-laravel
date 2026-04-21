<?php

namespace App\Traits;

use App\Observers\ActivityLogObserver;

trait LogsActivity
{
    /**
     * Boot the LogsActivity trait for a model.
     * This attaches the ActivityLogObserver automatically.
     *
     * @return void
     */
    public static function bootLogsActivity()
    {
        static::observe(ActivityLogObserver::class);
    }
}
