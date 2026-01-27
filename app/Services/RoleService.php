<?php

namespace App\Services;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Exception;
use Symfony\Component\HttpFoundation\Request;

class RoleService
{
    public function index()
    {
        return Role::with('permissions')->get();
    }

    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => 'api',
                'system_reserve' => false
            ]);

            if (isset($data['permissions'])) {
                $role->syncPermissions($data['permissions']);
            }

            return $role;
        });
    }

    public function update(Role $role, array $data)
    {
        if ($role->system_reserve && $role->name !== $data['name']) {
            throw new Exception("This Role Cant Be Change");
        }

        return DB::transaction(function () use ($role, $data) {
            $role->update(['name' => $data['name']]);

            if (isset($data['permissions'])) {
                $role->syncPermissions($data['permissions']);
            }

            return $role;
        });
    }

    public function delete(Role $role)
    {
        if ($role->system_reserve) {
            throw new Exception("This Role Cant Be Deleted");
        }

        if ($role->users()->exists()) {
            throw new Exception("This Role Cant Be Deleted Because Have A Users With This Role");
        }

        return $role->delete();
    }
}
