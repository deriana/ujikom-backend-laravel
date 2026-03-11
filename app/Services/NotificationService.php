<?php

namespace App\Services;

use App\Enums\CrudAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;
use App\Notifications\CrudActivityNotification;
/**
 * Class NotificationService
 *
 * Layanan untuk menangani pengiriman notifikasi kepada pengguna, baik secara individu,
 * berdasarkan peran, maupun notifikasi otomatis untuk aktivitas CRUD.
 */
class NotificationService
{
    /**
     * Mengirim notifikasi ke satu pengguna spesifik.
     *
     * @param User $user Objek pengguna penerima.
     * @param string $title Judul notifikasi.
     * @param string $message Isi pesan notifikasi.
     * @param string|null $url URL tautan opsional.
     * @return void
     */
    public function sendToUser(User $user, string $title, string $message, ?string $url = null)
    {
        // 1. Dispatch notification to the specific user instance
        $user->notify(new CrudActivityNotification($title, $message, $url));
    }

    /**
     * Mengirim notifikasi ke banyak pengguna sekaligus.
     *
     * @param mixed $users Koleksi atau array objek pengguna.
     * @param string $title Judul notifikasi.
     * @param string $message Isi pesan notifikasi.
     * @param string|null $url URL tautan opsional.
     * @return void
     */
    public function sendToUsers($users, string $title, string $message, ?string $url = null)
    {
        // 1. Use the Notification facade to send to a collection of users
        Notification::send($users, new CrudActivityNotification($title, $message, $url));
    }

    /**
     * Mengirim notifikasi aktivitas CRUD generik.
     *
     * @param CrudAction $action Jenis aksi CRUD (Created, Updated, Deleted).
     * @param Model $model Objek model yang terlibat.
     * @param mixed $users Daftar pengguna penerima.
     * @param string|null $customTitle Judul kustom (opsional).
     * @param string|null $customMessage Pesan kustom (opsional).
     * @return void
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

    /**
     * Mengirim notifikasi kepada pengguna berdasarkan peran (role) tertentu.
     *
     * @param array $roles Daftar nama peran.
     * @param CrudAction $action Jenis aksi CRUD.
     * @param Model $model Objek model terkait.
     * @param bool $includeManager Apakah harus menyertakan manajer langsung dari karyawan terkait.
     * @return void
     */
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
     * Menghasilkan judul default berdasarkan aksi CRUD dan nama model.
     *
     * @param CrudAction $action Jenis aksi.
     * @param Model $model Objek model.
     * @return string Judul yang dihasilkan.
     */
    protected function generateTitle(CrudAction $action, Model $model): string
    {
        $modelName = class_basename($model);
        return ucfirst($action->value) . " {$modelName}";
    }

    /**
     * Menghasilkan pesan default berdasarkan aksi CRUD dan konteks model.
     *
     * @param CrudAction $action Jenis aksi.
     * @param Model $model Objek model.
     * @return string Pesan yang dihasilkan.
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
