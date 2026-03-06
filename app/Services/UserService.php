<?php

namespace App\Services;

use App\Enums\EmploymentState;
use App\Enums\UserRole;
use App\Mail\VerifyEmail;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserService
{
    /**
     * Get a list of users with role-based filtering.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index()
    {
        // 1. Identify the current user and their employee profile
        $user = Auth::user();
        $currentUserEmployee = $user->employee;

        // 2. Initialize query with necessary relationships
        $query = User::with([
            'employee.position',
            'employee.team.division',
            'employee.manager.user',
            'roles',
        ])
            ->where('id', '!=', $user->id)
            ->latest();

        // 3. Apply role-based filtering
        if ($user->hasAnyRole([
            UserRole::ADMIN->value,
            UserRole::DIRECTOR->value,
            UserRole::OWNER->value,
            UserRole::HR->value,
            UserRole::FINANCE->value
        ])) {
            // High-level roles can see all data
        } elseif ($user->hasRole(UserRole::MANAGER->value)) {
            if ($currentUserEmployee) {
                // Managers see their direct subordinates
                $query->whereHas('employee', function ($q) use ($currentUserEmployee) {
                    $q->where('manager_id', $currentUserEmployee->id);
                });
            } else {
                return response()->json([], 200);
            }
        } elseif ($user->hasRole(UserRole::EMPLOYEE->value)) {
            $query->where('id', $user->id);
        } else {
            return response()->json([], 200);
        }

        return $query->get();
    }

    /**
     * Store a new user and their employee profile.
     *
     * @param array $data
     * @param int $creatorId
     * @return User
     */
    public function store(array $data, int $creatorId): User
    {
        return DB::transaction(function () use ($data, $creatorId) {

            // 1. Create the user record with a random password
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make(Str::random(32)),
                'is_active' => $data['is_active'],
            ]);

            // 2. Assign the specified role or default to EMPLOYEE
            $roleName = $data['role'] ?? UserRole::EMPLOYEE->value;
            $role = Role::where('name', $roleName)->firstOrFail();
            $user->assignRole($role);

            // 3. Find associated team and position
            $team = Team::where('uuid', $data['team_uuid'])->firstOrFail();
            $position = Position::where('uuid', $data['position_uuid'])->firstOrFail();

            // 4. Resolve manager ID from NIK if provided
            $managerId = null;
            if (! empty($data['manager_nik'])) {
                $managerId = Employee::where('nik', $data['manager_nik'])->value('id');
            }

            // 5. Create the employee profile record
            $employee = Employee::create([
                'user_id' => $user->id,
                'team_id' => $team->id,
                'position_id' => $position->id,
                'manager_id' => $managerId,
                'employee_status' => $data['employee_status'],
                'contract_start' => $data['contract_start'] ?? null,
                'contract_end' => $data['contract_end'] ?? null,
                'base_salary' => $data['base_salary'] ?? 0,
                'phone' => $data['phone'] ?? null,
                'gender' => $data['gender'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'address' => $data['address'] ?? null,
                'join_date' => $data['join_date'] ?? now(),
                'resign_date' => $data['resign_date'] ?? null,
                'created_by_id' => $creatorId,
            ]);

            // 6. Set custom notification data
            $employee->customNotification = [
                'title' => 'Employee Created',
                'message' => "Employee {$employee->user->name} (NIK: {$employee->nik}) has been successfully added to the system.",
                'url' => "/users/{$employee->user->uuid}/show",
            ];

            // 7. Generate email verification token
            $verificationService = app(EmailVerificationService::class);
            $token = $verificationService->generateToken($user);

            // 8. Send verification email
            Mail::to($user->email)->send(new VerifyEmail($user, $token));

            return $user->load([
                'roles',
                'employee.position',
                'employee.team.division',
                'employee.manager.user',
            ]);
        });
    }

    /**
     * Show details of a specific user.
     *
     * @param User $user
     */
    public function show(User $user)
    {
        // 1. Load nested relationships for detailed view
        return $user->load([
            'employee.position.allowances',
            'employee.team.division',
            'employee.manager.user',
            'employee.biometrics',
            'roles',
        ]);
    }

    /**
     * Update an existing user and their employee profile.
     *
     * @param User $user
     * @param array $data
     * @param int $updaterId
     * @return User
     */
    public function update(User $user, array $data, int $updaterId): User
    {
        return DB::transaction(function () use ($user, $data, $updaterId) {

            // 1. Prevent modification of system reserved users
            if ($user->system_reserve) {
                throw new Exception('Cannot update a system reserve user');
            }

            // 2. Update user basic information
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'is_active' => $data['is_active'],
                // password optional saat update
                'password' => isset($data['password'])
                    ? Hash::make($data['password'])
                    : $user->password,
            ]);

            // 3. Sync roles if provided
            if (! empty($data['role'])) {
                $role = Role::where('name', $data['role'])->firstOrFail();
                $user->syncRoles([$role->name]);
            }

            // 4. Ensure employee profile exists
            $employee = $user->employee;

            if (! $employee) {
                throw new \Exception('Employee data not found for this user');
            }

            // 5. Resolve team and position IDs
            $teamId = $employee->team_id;
            if (! empty($data['team_uuid'])) {
                $teamId = Team::where('uuid', $data['team_uuid'])->value('id') ?? $teamId;
            }

            $positionId = $employee->position_id;
            if (! empty($data['position_uuid'])) {
                $positionId = Position::where('uuid', $data['position_uuid'])->value('id') ?? $positionId;
            }

            // 6. Resolve manager ID from NIK
            $managerId = $employee->manager_id;
            if (array_key_exists('manager_nik', $data)) {
                $managerId = $data['manager_nik']
                    ? Employee::where('nik', $data['manager_nik'])->value('id')
                    : null; // bisa remove manager
            }

            // 7. Update employee profile record
            $employee->update([
                'team_id' => $teamId,
                'position_id' => $positionId,
                'manager_id' => $managerId,
                'employee_status' => $data['employee_status'],
                'contract_start' => $data['contract_start'] ?? $employee->contract_start,
                'contract_end' => $data['contract_end'] ?? $employee->contract_end,
                'base_salary' => $data['base_salary'] ?? $employee->base_salary,
                'phone' => $data['phone'] ?? $employee->phone,
                'gender' => $data['gender'] ?? $employee->gender,
                'date_of_birth' => $data['date_of_birth'] ?? $employee->date_of_birth,
                'address' => $data['address'] ?? $employee->address,
                'join_date' => $data['join_date'] ?? $employee->join_date,
                'resign_date' => $data['resign_date'] ?? $employee->resign_date,
                'updated_by_id' => $updaterId,
            ]);

            // 8. Set custom notification data
            $employee->customNotification = [
                'title' => 'Employee Updated',
                'message' => "Employee {$employee->user->name} (NIK: {$employee->nik}) has been successfully updated.",
                'url' => "/users/{$employee->user->uuid}/show",
            ];

            return $user->load([
                'roles',
                'employee.position',
                'employee.team.division',
                'employee.manager.user',
            ]);
        });
    }

    /**
     * Soft delete a user and their employee profile.
     *
     * @param User $user
     * @return bool
     */
    public function delete(User $user): bool
    {
        // 1. Security and state validation
        if ($user->system_reserve) {
            throw new Exception('Cannot delete a system reserve user');
        }

        if ($user->trashed()) {
            throw new Exception('User is already deleted');
        }

        // 2. Set custom notification data before deletion
        $user->employee->customNotification = [
            'title' => 'Employee Deleted',
            'message' => "Employee {$user->employee->user->name} (NIK: {$user->employee->nik}) has been successfully deleted.",
            'url' => "/users/{$user->uuid}/show",
        ];

        return DB::transaction(function () use ($user) {
            // 3. Perform soft delete on both employee and user
            $user->employee()->delete();
            $user->delete();

            return true;
        });
    }

    /**
     * Restore a soft-deleted user and their employee profile.
     *
     * @param string $uuid
     * @return User
     */
    public function restore(string $uuid): User
    {
        return DB::transaction(function () use ($uuid) {
            // 1. Find the trashed user
            $user = User::withTrashed()->whereUuid($uuid)->firstOrFail();

            if (! $user->trashed()) {
                throw new Exception('User is not deleted');
            }

            // 2. Restore the user and their employee profile
            $user->restore();
            $user->employee()->onlyTrashed()->restore();

            // 3. Set custom notification data
            $user->employee->customNotification = [
                'title' => 'Employee Restored',
                'message' => "Employee {$user->employee->user->name} (NIK: {$user->employee->nik}) has been successfully restored.",
                'url' => "/users/{$user->uuid}/show",
            ];

            return $user->load([
                'roles',
                'employee.position',
                'employee.team.division',
                'employee.manager.user',
            ]);
        });
    }

    /**
     * Permanently delete a user and their employee profile.
     *
     * @param string $uuid
     * @return bool
     */
    public function forceDelete(string $uuid): bool
    {
        return DB::transaction(function () use ($uuid) {

            // 1. Find the user including trashed ones
            $user = User::withTrashed()->whereUuid($uuid)->firstOrFail();

            // 2. Set custom notification data
            $user->employee->customNotification = [
                'title' => 'Permanently Deleted Employee',
                'message' => "Employee {$user->employee->user->name} (NIK: {$user->employee->nik}) has been permanently deleted.",
            ];

            // 3. Force delete both records
            $user->employee()->withTrashed()->forceDelete();
            $user->forceDelete();

            return true;
        });
    }

    /**
     * Terminate an employee's employment.
     *
     * @param string $uuid
     * @param string $state
     * @param string|null $date
     * @param int $adminId
     * @return User
     */
    public function terminateEmployment(string $uuid, string $state, ?string $date, int $adminId): User
    {
        return DB::transaction(function () use ($uuid, $state, $date, $adminId) {

            // 1. Find user and validate employee record
            $user = User::whereUuid($uuid)->firstOrFail();
            $employee = $user->employee;

            if (! $employee) {
                throw new Exception('Employee record not found');
            }

            if ($employee->employment_state !== 'active') {
                throw new Exception('Employment already ended');
            }

            // 2. Update employee termination details
            $employee->update([
                'employment_state' => $state,
                'termination_date' => $date ?? now(),
                'updated_by_id' => $adminId,
            ]);

            // 3. Deactivate the user account
            $user->update([
                'is_active' => false,
            ]);

            return $user->load([
                'roles',
                'employee.position',
                'employee.team.division',
                'employee.manager.user',
            ]);
        });
    }

    /**
     * Change the authenticated user's password.
     *
     * @param User $user
     * @param string $currentPassword
     * @param string $newPassword
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        DB::transaction(function () use ($user, $currentPassword, $newPassword) {
            // 1. Validate current password
            if (! Hash::check($currentPassword, $user->password)) {
                throw new Exception('The current password you entered is incorrect.');
            }

            // 2. Update password
            $user->update([
                'password' => Hash::make($newPassword),
            ]);

            // 3. Revoke all existing tokens
            $user->tokens()->delete();
        });
    }

    /**
     * Change a user's password by an administrator.
     *
     * @param string $uuid
     * @param string $newPassword
     */
    public function adminChangePassword(string $uuid, string $newPassword): void
    {
        DB::transaction(function () use ($uuid, $newPassword) {

            // 1. Find user and lock for update
            $user = User::whereUuid($uuid)->lockForUpdate()->firstOrFail();

            // 2. Update password
            $user->update([
                'password' => Hash::make($newPassword),
            ]);

            // 3. Revoke all existing tokens
            $user->tokens()->delete();
        });
    }

    /**
     * Toggle a user's active status.
     *
     * @param string $uuid
     * @param bool $isActive
     * @param int $adminId
     * @return User
     */
    public function status(string $uuid, bool $isActive, int $adminId): User
    {
        return DB::transaction(function () use ($uuid, $isActive, $adminId) {

            // 1. Find user and validate reactivation eligibility
            $user = User::whereUuid($uuid)->firstOrFail();
            $employee = $user->employee;

            if ($employee && $employee->employment_state !== 'active' && $isActive) {
                throw new Exception('Cannot reactivate a terminated employee');
            }

            // 2. Update user active status
            $user->update([
                'is_active' => $isActive,
            ]);

            // 3. Update employee record metadata
            if ($employee) {
                $employee->update([
                    'updated_by_id' => $adminId,
                ]);
            }

            return $user->load([
                'roles',
                'employee.position',
                'employee.team.division',
                'employee.manager.user',
            ]);
        });
    }

    /**
     * Get all soft-deleted users.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTrashed()
    {
        // 1. Retrieve only trashed users with related data
        return User::onlyTrashed()
            ->with(
                'employee.position',
                'employee.team.division',
                'employee.manager.user',
                'roles',
            )
            ->latest()
            ->get();
    }

    /**
     * Upload and set a profile photo for a user.
     *
     * @param User $user
     * @param mixed $photoFile
     * @param string $uuid
     * @return User
     */
    public function uploadProfilePhoto(User $user, $photoFile, $uuid): User
    {
        // 1. Find user and validate employee profile
        $user = User::whereUuid($uuid)->firstOrFail();
        $employee = $user->employee;

        if (! $employee) {
            throw new Exception('Employee record not found for this user');
        }

        // 2. Clear existing photo and add new one to media collection
        $employee->clearMediaCollection('profile_photo');

        if ($photoFile) {
            $employee->addMedia($photoFile)
                ->toMediaCollection('profile_photo');
        }

        return $user->load([
            'roles',
            'employee.position',
            'employee.team.division',
        ]);
    }

    /**
     * Get a list of users with management roles.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getManagers()
    {
        // 1. Retrieve users with Director or Manager roles
        return User::with(['employee.position', 'roles'])
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', [
                    \App\Enums\UserRole::DIRECTOR->value,
                    \App\Enums\UserRole::MANAGER->value,
                ]);
            })
            ->get();
    }

    /**
     * Get a simplified list of employees.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getEmployeesLite()
    {
        // 1. Retrieve non-system employees with basic user info
        return Employee::whereHas('user', function ($query) {
            $query->where('system_reserve', false);
        })->with('user')->get();
    }

    /**
     * Update biometric descriptors for a user.
     *
     * @param User $user
     * @param array $descriptors
     */
    public function updateBiometricDescriptors(User $user, array $descriptors): void
    {
        DB::transaction(function () use ($user, $descriptors) {
            // 1. Find employee profile
            $employee = $user->employee;

            if (! $employee) {
                throw new Exception('Employee record not found for this user');
            }

            // 2. Replace existing biometric data with new descriptors
            $employee->biometrics()->delete();

            foreach ($descriptors as $descriptor) {
                $employee->biometrics()->create([
                    'descriptor' => $descriptor,
                ]);
            }
        });
    }
}
