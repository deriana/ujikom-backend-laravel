<?php

namespace App\Traits;

use App\Enums\CrudAction;
use App\Enums\UserRole;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

trait Notificationable
{
    /**
     * Boot the trait and register Eloquent model event listeners.
     */
    protected static function bootNotificationable(): void
    {
        // 1. Define standard CRUD events to listen for
        $events = [
            'created' => CrudAction::CREATED,
            'updated' => CrudAction::UPDATED,
            'deleted' => CrudAction::DELETED,
        ];

        // 2. Add soft delete specific events if the model uses SoftDeletes trait
        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(static::class))) {
            $events['restored'] = CrudAction::RESTORED;
            $events['forceDeleted'] = CrudAction::FORCE_DELETED;
        }

        // 3. Register listeners for each identified event
        foreach ($events as $event => $action) {
            switch ($event) {
                case 'created':
                    static::created(function ($model) use ($action) {
                        self::handleNotification($model, $action);
                    });
                    break;
                case 'updated':
                    static::updated(function ($model) use ($action) {
                        self::handleNotification($model, $action);
                    });
                    break;
                case 'deleted':
                    static::deleted(function ($model) use ($action) {
                        self::handleNotification($model, $action);
                    });
                    break;
                case 'restored':
                    static::restored(function ($model) use ($action) {
                        self::handleNotification($model, $action);
                    });
                    break;
                case 'forceDeleted':
                    static::forceDeleted(function ($model) use ($action) {
                        self::handleNotification($model, $action);
                    });
                    break;
            }
        }
    }

    /**
     * Send a custom notification manually for the current model.
     */
    public function notifyCustom(string $title, string $message, ?string $url = null, $customUsers = null): void
    {
        // 1. Resolve notification service and determine recipients
        $notificationService = App::make(\App\Services\NotificationService::class);
        $notifyUsers = $customUsers ?? self::getNotifyUsers($this);

        // Log::info("Custom Notification [{$title}] for [".class_basename($this).":{$this->id}]");

        // 2. Dispatch the notification using the service
        $notificationService->notifyCrud(
            \App\Enums\CrudAction::UPDATED,
            $this,
            $notifyUsers,
            $title,
            $message,
            $url
        );
    }

    /**
     * Handle automatic notification logic triggered by model events.
     */
    protected static function handleNotification($model, $action)
    {
        // 1. Check if default notifications should be skipped for this instance
        if (! empty($model->skipDefaultNotification)) {
            return;
        }

        // 2. Resolve service and recipients
        $notificationService = App::make(\App\Services\NotificationService::class);
        $notifyUsers = self::getNotifyUsers($model);

        // 3. Prepare notification content from custom properties or defaults
        $title = $model->customNotification['title'] ?? self::defaultTitle($action, $model);
        $message = $model->customNotification['message'] ?? self::defaultMessage($action, $model);
        $url = $model->customNotification['url'] ?? null;

        // Log penerima
        $userInfo = $notifyUsers->map(function ($user) {
            $roleName = $user->roles->first()?->name ?? 'unknown';

            return "{$user->name}({$roleName})";
        })->join(', ');

        // Log::info("Notification [{$action->value}] for model [".class_basename($model).":{$model->id}] will be sent to: $userInfo");

        // 4. Dispatch the notification
        $notificationService->notifyCrud($action, $model, $notifyUsers, $title, $message, $url);
    }

    /**
     * Generate a default title for the notification.
     */
    protected static function defaultTitle($action, $model): string
    {
        return ucfirst($action->value).' '.class_basename($model);
    }

    /**
     * Generate a default message based on the action and model context.
     */
    protected static function defaultMessage($action, $model): string
    {
        // 1. If the model has a user relationship, provide detailed identity info
        if (method_exists($model, 'user') && $model->user) {
            $name = $model->user->name ?? '-';
            $nik = $model->nik ?? '-';

            return match ($action) {
                CrudAction::CREATED => "Employee {$name} (NIK: {$nik}) has been created.",
                CrudAction::UPDATED => "Employee {$name} (NIK: {$nik}) has been updated.",
                CrudAction::DELETED => "Employee {$name} (NIK: {$nik}) has been deleted.",
                CrudAction::RESTORED => "Employee {$name} (NIK: {$nik}) has been restored.",
                CrudAction::FORCE_DELETED => "Employee {$name} (NIK: {$nik}) has been permanently deleted.",
            };
        }

        // 2. Fallback to generic message
        return class_basename($model)." has been {$action->value}";
    }

    /**
     * Determine the list of users who should receive the notification.
     */
    protected static function getNotifyUsers($model)
    {
        // 1. Always include Admin and HR roles
        $users = \App\Models\User::role([
            UserRole::ADMIN->value,
            UserRole::HR->value,
        ])->get();

        // 2. Identify the related employee to find their manager
        $employee = null;
        if (class_basename($model) === 'Employee') {
            $employee = $model;
        } elseif (method_exists($model, 'employee')) {
            $employee = $model->employee;
        }

        // 3. Include the direct manager if applicable
        if ($employee && $employee->manager_id) {
            $managerUser = \App\Models\User::whereHas('employee', function ($q) use ($employee) {
                $q->where('id', $employee->manager_id);
            })->first();

            if ($managerUser) {
                $users->push($managerUser);
            }
        }

        // 4. Include the employee themselves if the model has a user relation
        if ($model->user) {
            $users->push($model->user);
        }

        // 5. Return unique list of users
        return $users->unique('id');
    }
}
