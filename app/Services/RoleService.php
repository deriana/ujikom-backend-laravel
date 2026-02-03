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
     * Get all roles with permissions
     */
    public function index()
    {
        return Role::with('permissions')->get();
    }

    /**
     * Create new role and assign permissions
     */
    public function store(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => 'api',
                'system_reserve' => false,
            ]);

            if (!empty($data['permissions'])) {
                $role->syncPermissions($data['permissions']);
            }

            return $role;
        });
    }

    public function show(Role $role)
    {
        return $role->load('permissions');
    }

    /**
     * Update role name and permissions
     */
    public function update(Role $role, array $data): Role
    {
        if ($role->system_reserve && $role->name !== ($data['name'] ?? $role->name)) {
            throw new DomainException("This role cannot be changed.");
        }

        return DB::transaction(function () use ($role, $data) {
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
     * Delete role if allowed
     */
    public function delete(Role $role): bool
    {
        if ($role->system_reserve) {
            throw new DomainException("This role cannot be deleted.");
        }

        if ($role->users()->exists()) {
            throw new DomainException("This role cannot be deleted because it has users.");
        }

        return DB::transaction(fn() => $role->delete());
    }

    public function permission()
    {
        return Module::with('permissions')->get();
    }
}
