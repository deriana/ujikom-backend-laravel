<?php

namespace App\Services;

use App\Enums\EmploymentState;
use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function index()
    {
        $user = Auth::user();

        $query = User::with([
            'employee.position',
            'employee.team.division',
            'employee.manager.user',
            'roles',
        ])
            ->where('id', '!=', $user->id)
            ->latest();

        if ($user->hasAnyRole([UserRole::ADMIN->value, UserRole::DIRECTOR->value, UserRole::OWNER->value, UserRole::HR->value, UserRole::FINANCE->value])) {
        } elseif ($user->hasRole('MANAGER')) {
            $query->whereHas('employee', function ($q) use ($user) {
                $q->where('manager_id', $user->id);
            });
        } elseif ($user->hasRole('EMPLOYEE')) {
            $query->where('id', $user->id);
        } else {
            return response()->json([], 200);
        }

        return $query->get();
    }

    public function store(array $data, int $creatorId): User
    {
        return DB::transaction(function () use ($data, $creatorId) {

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'is_active' => $data['is_active'],
            ]);

            $roleName = $data['role'] ?? UserRole::EMPLOYEE->value;

            $role = Role::where('name', $roleName)->firstOrFail();
            $user->assignRole($role);

            $team = Team::where('uuid', $data['team_uuid'])->firstOrFail();
            $position = Position::where('uuid', $data['position_uuid'])->firstOrFail();

            $managerId = null;
            if (! empty($data['manager_nik'])) {
                $managerId = Employee::where('nik', $data['manager_nik'])->value('id');
            }

            Employee::create([
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

            return $user->load([
                'roles',
                'employee.position',
                'employee.team.division',
                'employee.manager.user',
            ]);
        });
    }

    public function show(User $user)
    {
        return $user->load([
            'employee.position',
            'employee.team.division',
            'employee.manager.user',
            'roles',
        ]);
    }

    public function update(User $user, array $data, int $updaterId): User
    {
        return DB::transaction(function () use ($user, $data, $updaterId) {

            if ($user->system_reserve) {
                throw new Exception('Cannot update a system reserve user');
            }

            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'is_active' => $data['is_active'],
                // password optional saat update
                'password' => isset($data['password'])
                    ? Hash::make($data['password'])
                    : $user->password,
            ]);

            if (! empty($data['role'])) {
                $role = Role::where('name', $data['role'])->firstOrFail();
                $user->syncRoles([$role->name]);
            }

            $employee = $user->employee;

            if (! $employee) {
                throw new \Exception('Employee data not found for this user');
            }

            $teamId = $employee->team_id;
            if (! empty($data['team_uuid'])) {
                $teamId = Team::where('uuid', $data['team_uuid'])->value('id') ?? $teamId;
            }

            $positionId = $employee->position_id;
            if (! empty($data['position_uuid'])) {
                $positionId = Position::where('uuid', $data['position_uuid'])->value('id') ?? $positionId;
            }

            $managerId = $employee->manager_id;
            if (array_key_exists('manager_nik', $data)) {
                $managerId = $data['manager_nik']
                    ? Employee::where('nik', $data['manager_nik'])->value('id')
                    : null; // bisa remove manager
            }

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

            return $user->load([
                'roles',
                'employee.position',
                'employee.team.division',
                'employee.manager.user',
            ]);
        });
    }

    public function delete(User $user): bool
    {
        if ($user->system_reserve) {
            throw new Exception('Cannot delete a system reserve user');
        }

        if ($user->trashed()) {
            throw new Exception('Cannot delete a user');
        }

        return DB::transaction(function () use ($user) {
            $user->employee()->delete();
            $user->delete();

            return true;
        });

    }

    public function restore(string $uuid): User
    {
        return DB::transaction(function () use ($uuid) {
            $user = User::withTrashed()->whereUuid($uuid)->firstOrFail();

            if (! $user->trashed()) {
                throw new Exception('User is not deleted');
            }

            $user->restore();
            $user->employee()->onlyTrashed()->restore();

            return $user->load([
                'roles',
                'employee.position',
                'employee.team.division',
                'employee.manager.user',
            ]);
        });
    }

    public function forceDelete(string $uuid): bool
    {
        return DB::transaction(function () use ($uuid) {

            $user = User::withTrashed()->whereUuid($uuid)->firstOrFail();

            $user->employee()->withTrashed()->forceDelete();
            $user->forceDelete();

            return true;
        });
    }

    public function terminateEmployment(string $uuid, EmploymentState $state, ?string $date, int $adminId): User
    {
        return DB::transaction(function () use ($uuid, $state, $date, $adminId) {

            $user = User::whereUuid($uuid)->firstOrFail();
            $employee = $user->employee;

            if (! $employee) {
                throw new Exception('Employee record not found');
            }

            if ($employee->employment_state !== 'active') {
                throw new Exception('Employment already ended');
            }

            $employee->update([
                'employment_state' => $state->value,
                'termination_date' => $date ?? now(),
                'updated_by_id' => $adminId,
            ]);

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

    public function adminChangePassword(string $uuid, string $newPassword): void
    {
        DB::transaction(function () use ($uuid, $newPassword) {

            $user = User::whereUuid($uuid)->lockForUpdate()->firstOrFail();

            $user->update([
                'password' => Hash::make($newPassword),
            ]);

            $user->tokens()->delete();
        });
    }

    public function status(string $uuid, bool $isActive, int $adminId): User
    {
        return DB::transaction(function () use ($uuid, $isActive, $adminId) {

            $user = User::whereUuid($uuid)->firstOrFail();
            $employee = $user->employee;

            if ($employee && $employee->employment_state !== 'active' && $isActive) {
                throw new Exception('Cannot reactivate a terminated employee');
            }

            $user->update([
                'is_active' => $isActive,
            ]);

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

    public function getTrashed()
    {
        return User::onlyTrashed()
            ->with('employee.position',
                'employee.team.division',
                'employee.manager.user',
                'roles', )
            ->latest()
            ->get();
    }

    public function uploadProfilePhoto(User $user, $photoFile, $uuid): User
    {
        $user = User::whereUuid($uuid)->firstOrFail();

        $employee = $user->employee;

        if (! $employee) {
            throw new Exception('Employee record not found for this user');
        }

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

    public function getManagers()
    {
        return User::with(['employee.position', 'roles'])
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', [
                    \App\Enums\UserRole::DIRECTOR->value,
                    \App\Enums\UserRole::MANAGER->value,
                ]);
            })
            ->get();
    }

    public function getEmployeesLite()
    {
        return Employee::whereHas('user', function ($query) {
            $query->where('system_reserve', false);
        })->with('user')->get();
    }
}
