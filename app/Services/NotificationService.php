<?php

namespace App\Services;

use App\Enums\CrudAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;
use App\Notifications\CrudActivityNotification;

class NotificationService
{
    /**
     * Kirim notifikasi ke satu user
     */
    public function sendToUser(User $user, string $title, string $message, ?string $url = null)
    {
        $user->notify(new CrudActivityNotification($title, $message, $url));
    }

    /**
     * Kirim notifikasi ke banyak user
     */
    public function sendToUsers($users, string $title, string $message, ?string $url = null)
    {
        Notification::send($users, new CrudActivityNotification($title, $message, $url));
    }

    /**
     * Notifikasi CRUD generik
     */
    public function notifyCrud(
        CrudAction $action,
        Model $model,
        $users,
        ?string $customTitle = null,
        ?string $customMessage = null
    ) {
        $modelName = class_basename($model);
        $modelSlug = strtolower($modelName);

        $title = $customTitle ?? $this->generateTitle($action, $model);
        $message = $customMessage ?? $this->generateMessage($action, $model);
        $url = "/{$modelSlug}/{$model->id}";

        $this->sendToUsers($users, $title, $message, $url);
    }

    /**
     * Kirim notifikasi ke semua user dengan role tertentu + optional manager
     */
    public function notifyToRoles(array $roles, CrudAction $action, Model $model, $includeManager = true)
    {
        $users = User::role($roles)->get();

        if ($includeManager && isset($model->employee) && $model->employee->manager_id) {
            $managerUser = User::whereHas('employee', function ($q) use ($model) {
                $q->where('id', $model->employee->manager_id);
            })->first();

            if ($managerUser) {
                $users->push($managerUser);
            }
        }

        $this->notifyCrud($action, $model, $users->unique('id'));
    }

    /**
     * Generate title default berdasarkan action
     */
    protected function generateTitle(CrudAction $action, Model $model): string
    {
        $modelName = class_basename($model);
        return ucfirst($action->value) . " {$modelName}";
    }

    /**
     * Generate message default berdasarkan action
     */
    protected function generateMessage(CrudAction $action, Model $model): string
    {
        $modelName = class_basename($model);

        if (method_exists($model, 'employee') && $model->employee?->user) {
            $user = $model->employee->user;
            $nik = $model->employee->nik ?? '-';
            return match($action) {
                CrudAction::CREATED => "Employee {$user->name} (NIK: {$nik}) has been created.",
                CrudAction::UPDATED => "Employee {$user->name} (NIK: {$nik}) data has been updated.",
                CrudAction::DELETED => "Employee {$user->name} (NIK: {$nik}) has been deleted.",
            };
        }

        return "{$modelName} has been {$action->value}";
    }
}
