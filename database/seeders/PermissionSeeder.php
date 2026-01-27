<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Module;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Reset cache Spatie
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $modules = [
            'users' => [
                'actions' => [
                    'index'   => 'user.index',
                    'create'  => 'user.create',
                    'edit'    => 'user.edit',
                    'destroy' => 'user.destroy',
                    'forceDelete' => 'user.forceDelete',
                    'restore' => 'user.restore',
                ],
                'roles' => [
                    UserRole::SUPER_ADMIN->value => ['index', 'create', 'edit', 'destroy', 'forceDelete', 'restore'],
                    UserRole::ADMIN->value       => ['index', 'create', 'edit', 'destroy', 'forceDelete', 'restore'],
                    UserRole::HR->value          => ['index', 'create', 'edit'],
                ]
            ],
            'roles' => [
                'actions' => [
                    'index'   => 'role.index',
                    'create'  => 'role.create',
                    'edit'    => 'role.edit',
                    'destroy' => 'role.destroy'
                ],
                'roles' => [
                    UserRole::SUPER_ADMIN->value => ['index', 'create', 'edit', 'destroy'],
                    UserRole::ADMIN->value       => ['index', 'create', 'edit'],
                ],
            ],
            'pages' => [
                'actions' => [
                    'index'   => 'page.index',
                    'create'  => 'page.create',
                    'edit'    => 'page.edit',
                    'destroy' => 'page.destroy',
                ],
                'roles' => [
                    UserRole::SUPER_ADMIN->value => ['index', 'create', 'edit', 'destroy'],
                    UserRole::ADMIN->value       => ['index', 'create', 'edit', 'destroy'],
                ]
            ],
            'settings' => [
                'actions' => [
                    'index' => 'setting.index',
                    'edit'  => 'setting.edit',
                ],
                'roles' => [
                    UserRole::SUPER_ADMIN->value => ['index', 'edit'],
                    UserRole::ADMIN->value       => ['index', 'edit'],
                ],
            ],
        ];

        // 3. Buat Semua Role dari Enum jika belum ada
        foreach (UserRole::cases() as $roleEnum) {
            Role::firstOrCreate(
                ['name' => $roleEnum->value],
                [
                    'name' => $roleEnum->value,
                    'system_reserve' => in_array($roleEnum, [UserRole::SUPER_ADMIN, UserRole::ADMIN])
                ]
            );
        }

        // 4. Proses Module, Permission, dan Assign ke Role
        foreach ($modules as $moduleName => $config) {
            // Simpan ke tabel module kamu
            Module::updateOrCreate(
                ['name' => $moduleName],
                ['actions' => $config['actions']]
            );

            foreach ($config['actions'] as $actionKey => $permissionName) {
                // Buat permission
                $permission = Permission::firstOrCreate(['name' => $permissionName]);

                // Assign permission ke role yang didefinisikan di config
                if (isset($config['roles'])) {
                    foreach ($config['roles'] as $roleName => $allowedActions) {
                        if (in_array($actionKey, $allowedActions)) {
                            $role = Role::findByName($roleName);
                            $role->givePermissionTo($permission);
                        }
                    }
                }
            }
        }

        // 5. Khusus Super Admin: Berikan SEMUA permission (Safety Net)
        $superAdmin = Role::findByName(UserRole::SUPER_ADMIN->value);
        $superAdmin->givePermissionTo(Permission::all());
    }
}
