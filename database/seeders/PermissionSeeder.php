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
                    'index' => 'user.index',
                    'create' => 'user.create',
                    'edit' => 'user.edit',
                    'destroy' => 'user.destroy',
                    'forceDelete' => 'user.forceDelete',
                    'restore' => 'user.restore',
                ],
                'roles' => [
                    UserRole::ADMIN->value => ['index', 'create', 'edit', 'destroy', 'forceDelete', 'restore'],
                ],
            ],
            'roles' => [
                'actions' => [
                    'index' => 'role.index',
                    'create' => 'role.create',
                    'edit' => 'role.edit',
                    'destroy' => 'role.destroy',
                ],
                'roles' => [
                    UserRole::ADMIN->value => ['index', 'create', 'edit', 'destroy'],
                ],
            ],
            'pages' => [
                'actions' => [
                    'index' => 'page.index',
                    'create' => 'page.create',
                    'edit' => 'page.edit',
                    'destroy' => 'page.destroy',
                ],
                'roles' => [
                    UserRole::ADMIN->value => ['index', 'create', 'edit', 'destroy'],
                ],
            ],
            'settings' => [
                'actions' => [
                    'index' => 'setting.index',
                    'edit' => 'setting.edit',
                ],
                'roles' => [
                    UserRole::ADMIN->value => ['index', 'edit'],
                ],
                'divisions' => [
                    'actions' => [
                        'index' => 'division.index',
                        'create' => 'division.create',
                        'edit' => 'division.edit',
                        'destroy' => 'division.destroy',
                        'restore' => 'division.restore',
                        'forceDelete' => 'division.forceDelete',
                    ],
                    'roles' => [
                        UserRole::ADMIN->value => ['index', 'create', 'edit', 'destroy', 'forceDelete', 'restore'],
                    ],
                ],
            ],
        ];

        // 3. Buat Semua Role dari Enum jika belum ada
        foreach (UserRole::cases() as $roleEnum) {
            Role::firstOrCreate(
                [
                    'name' => $roleEnum->value,
                    'guard_name' => 'api', // Tambahkan di kriteria pencarian
                ],
                [
                    'name' => $roleEnum->value,
                    'guard_name' => 'api', // Tambahkan di data yang dibuat
                    'system_reserve' => in_array($roleEnum, [UserRole::ADMIN]),
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

        $admin = Role::findByName(UserRole::ADMIN->value);
        $admin->givePermissionTo(Permission::all());
    }
}
