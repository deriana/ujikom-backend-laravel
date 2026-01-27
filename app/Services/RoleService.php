<?php

namespace App\Services;

use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Exception;

class RoleService
{
    public function index()
    {
        try {
            return Role::with('permissions')->get();
        } catch (Exception $e) {
            throw new Exception("Failed to fetch roles: " . $e->getMessage());
        }
    }

    public function store(array $data)
    {
        try {
            return DB::transaction(function () use ($data) {
                $role = Role::create([
                    'name' => $data['name'],
                    'guard_name' => 'api',
                    'system_reserve' => false
                ]);

                if (!empty($data['permissions'])) {
                    $role->syncPermissions($data['permissions']);
                }

                return $role;
            });
        } catch (Exception $e) {
            throw new Exception("Failed to create role: " . $e->getMessage());
        }
    }

    public function update(Role $role, array $data)
    {
        try {
            if ($role->system_reserve && $role->name !== $data['name']) {
                throw new Exception("This Role Cant Be Change");
            }

            return DB::transaction(function () use ($role, $data) {
                $role->update([
                    'name' => $data['name']
                ]);

                if (isset($data['permissions'])) {
                    $role->syncPermissions($data['permissions']);
                }

                return $role->fresh('permissions');
            });
        } catch (Exception $e) {
            throw new Exception("Failed to update role: " . $e->getMessage());
        }
    }

    public function delete(Role $role)
    {
        DB::beginTransaction();

        try {
            if ($role->system_reserve) {
                throw new Exception("This Role Cant Be Deleted");
            }

            if ($role->users()->exists()) {
                throw new Exception("This Role Cant Be Deleted Because Have A Users With This Role");
            }

            return $role->delete();
        } catch (Exception $e) {
            throw new Exception("Failed to delete role: " . $e->getMessage());
        }
    }
}
