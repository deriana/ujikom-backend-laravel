<?php

namespace App\Traits;

use App\Enums\CrudAction;
use App\Enums\UserRole;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

trait Notificationable
{
    protected static function bootNotificationable(): void
    {
        $events = [
            'created' => CrudAction::CREATED,
            'updated' => CrudAction::UPDATED,
            'deleted' => CrudAction::DELETED,
        ];

        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(static::class))) {
            $events['restored'] = CrudAction::RESTORED;
            $events['forceDeleted'] = CrudAction::FORCE_DELETED;
        }

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

    public function notifyCustom(string $title, string $message, ?string $url = null, $customUsers = null): void
    {
        $notificationService = App::make(\App\Services\NotificationService::class);
        $notifyUsers = $customUsers ?? self::getNotifyUsers($this);

        Log::info("Custom Notification [{$title}] for [".class_basename($this).":{$this->id}]");

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
     * Handle notifikasi supaya tidak duplikat di tiap event
     */
    protected static function handleNotification($model, $action)
    {
        if (! empty($model->skipDefaultNotification)) {
            return;
        }

        $notificationService = App::make(\App\Services\NotificationService::class);
        $notifyUsers = self::getNotifyUsers($model);

        $title = $model->customNotification['title'] ?? self::defaultTitle($action, $model);
        $message = $model->customNotification['message'] ?? self::defaultMessage($action, $model);
        $url = $model->customNotification['url'] ?? null;

        // Log penerima
        $userInfo = $notifyUsers->map(function ($user) {
            $roleName = $user->roles->first()?->name ?? 'unknown';

            return "{$user->name}({$roleName})";
        })->join(', ');

        Log::info("Notification [{$action->value}] for model [".class_basename($model).":{$model->id}] will be sent to: $userInfo");

        $notificationService->notifyCrud($action, $model, $notifyUsers, $title, $message, $url);
    }

    protected static function defaultTitle($action, $model): string
    {
        return ucfirst($action->value).' '.class_basename($model);
    }

    protected static function defaultMessage($action, $model): string
    {
        // Jika model punya relasi employee->user, masukkan nama + nik
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

        return class_basename($model)." has been {$action->value}";
    }

    protected static function getNotifyUsers($model)
    {
        $users = \App\Models\User::role([
            UserRole::ADMIN->value,
            UserRole::HR->value,
        ])->get();

        // Manager
        $employee = null;
        if (class_basename($model) === 'Employee') {
            $employee = $model;
        } elseif (method_exists($model, 'employee')) {
            $employee = $model->employee;
        }

        // Ambil manager dari objek employee yang ditemukan
        if ($employee && $employee->manager_id) {
            $managerUser = \App\Models\User::whereHas('employee', function ($q) use ($employee) {
                $q->where('id', $employee->manager_id);
            })->first();

            if ($managerUser) {
                $users->push($managerUser);
            }
        }

        // Employee sendiri
        if ($model->user) {
            $users->push($model->user);
        }

        return $users->unique('id');
    }
}
