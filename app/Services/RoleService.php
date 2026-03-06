<?php

namespace App\Services;

use App\Models\Module;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use DomainException;
use Spatie\Permission\Models\Permission;

class RoleService
{
    /**
     * Get all roles with their associated permissions.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index()
    {
        // 1. Retrieve all roles with eager loaded permissions
        return Role::with('permissions')->get();
    }

    /**
     * Create a new role and assign permissions.
     *
     * @param array $data
     * @return Role
     */
    public function store(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            // 1. Create the role record with API guard
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => 'api',
                'system_reserve' => false,
            ]);

            // 2. Sync permissions if provided in the request
            if (!empty($data['permissions'])) {
                $role->syncPermissions($data['permissions']);
            }

            return $role->load('permissions');
        });
    }

    /**
     * Show details of a specific role.
     */
    public function show(Role $role)
    {
        // 1. Load permissions relationship for the role
        return $role->load('permissions');
    }

    /**
     * Update role name and permissions.
     */
    public function update(Role $role, array $data): Role
    {
        // 1. Prevent modification of system reserved roles
        if($role->system_reserve) {
            throw new DomainException("This role cannot be updated.");
        }

        return DB::transaction(function () use ($role, $data) {
            // 2. Update the role name
            $role->update([
                'name' => $data['name'] ?? $role->name,
            ]);

            if (isset($data['permissions'])) {
                $role->syncPermissions($data['permissions']);
            }

            return $role->fresh('permissions');
        });
    }

    /**
     * Delete a role if it is not a system reserve and has no users.
     */
    public function delete(Role $role): bool
    {
        // 1. Security check for system reserved roles
        if ($role->system_reserve) {
            throw new DomainException("This role cannot be deleted.");
        }

        // 2. Prevent deletion if the role is still assigned to users
        if ($role->users()->exists()) {
            throw new DomainException("This role cannot be deleted because it has users.");
        }

        // 3. Perform the deletion
        return DB::transaction(fn() => $role->delete());
    }

    /**
     * Get all permissions grouped by modules.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function permission()
    {
        // 1. Retrieve modules with their nested permissions
        return Module::with('permissions')->get();
    }
}
