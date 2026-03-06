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
     * Send a notification to a single user.
     *
     * @param User $user
     * @param string $title
     * @param string $message
     * @param string|null $url
     * @return void
     */
    public function sendToUser(User $user, string $title, string $message, ?string $url = null)
    {
        // 1. Dispatch notification to the specific user instance
        $user->notify(new CrudActivityNotification($title, $message, $url));
    }

    /**
     * Send a notification to multiple users.
     *
     * @param mixed $users
     * @param string $title
     * @param string $message
     * @param string|null $url
     * @return void
     */
    public function sendToUsers($users, string $title, string $message, ?string $url = null)
    {
        // 1. Use the Notification facade to send to a collection of users
        Notification::send($users, new CrudActivityNotification($title, $message, $url));
    }

    /**
     * Send a generic CRUD activity notification.
     */
    public function notifyCrud(
        CrudAction $action,
        Model $model,
        $users,
        ?string $customTitle = null,
        ?string $customMessage = null
    ) {
        // 1. Determine model metadata for naming and routing
        $modelName = class_basename($model);
        $modelSlug = strtolower($modelName);

        // 2. Prepare notification content with fallbacks to auto-generated strings
        $title = $customTitle ?? $this->generateTitle($action, $model);
        $message = $customMessage ?? $this->generateMessage($action, $model);
        $url = "/{$modelSlug}/{$model->id}";

        // 3. Dispatch to the provided users
        $this->sendToUsers($users, $title, $message, $url);
    }

    public function notifyToRoles(array $roles, CrudAction $action, Model $model, $includeManager = true)
    {
        // 1. Fetch all users associated with the specified roles
        $users = User::role($roles)->get();

        // 2. Optionally include the direct manager of the employee related to the model
        if ($includeManager && isset($model->employee) && $model->employee->manager_id) {
            $managerUser = User::whereHas('employee', function ($q) use ($model) {
                $q->where('id', $model->employee->manager_id);
            })->first();

            if ($managerUser) {
                $users->push($managerUser);
            }
        }

        // 3. Trigger the CRUD notification for the unique set of users
        $this->notifyCrud($action, $model, $users->unique('id'));
    }

    /**
     * Generate a default title based on the CRUD action and model name.
     */
    protected function generateTitle(CrudAction $action, Model $model): string
    {
        $modelName = class_basename($model);
        return ucfirst($action->value) . " {$modelName}";
    }

    /**
     * Generate a default message based on the CRUD action and model context.
     */
    protected function generateMessage(CrudAction $action, Model $model): string
    {
        $modelName = class_basename($model);

        // 1. If the model has an employee relation, provide a detailed identity-based message
        if (method_exists($model, 'employee') && $model->employee?->user) {
            $user = $model->employee->user;
            $nik = $model->employee->nik ?? '-';
            return match($action) {
                CrudAction::CREATED => "Employee {$user->name} (NIK: {$nik}) has been created.",
                CrudAction::UPDATED => "Employee {$user->name} (NIK: {$nik}) data has been updated.",
                CrudAction::DELETED => "Employee {$user->name} (NIK: {$nik}) has been deleted.",
            };
        }

        // 2. Fallback to a simple generic message
        return "{$modelName} has been {$action->value}";
    }
}
