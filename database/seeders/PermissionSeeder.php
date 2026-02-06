<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Module;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
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

            'positions' => [
                'actions' => [
                    'index' => 'position.index',
                    'create' => 'position.create',
                    'edit' => 'position.edit',
                    'destroy' => 'position.destroy',
                    'restore' => 'position.restore',
                    'forceDelete' => 'position.forceDelete',
                ],
                'roles' => [
                    UserRole::ADMIN->value => ['index', 'create', 'edit', 'destroy', 'forceDelete', 'restore'],
                ],
            ],

            'allowances' => [
                'actions' => [
                    'index' => 'allowance.index',
                    'create' => 'allowance.create',
                    'edit' => 'allowance.edit',
                    'destroy' => 'allowance.destroy',
                    'restore' => 'allowance.restore',
                    'forceDelete' => 'allowance.forceDelete',
                ],
                'roles' => [
                    UserRole::ADMIN->value => ['index', 'create', 'edit', 'destroy', 'forceDelete', 'restore'],
                ],
            ],
        ];

        DB::transaction(function () use ($modules) {

            // 1️⃣ Buat roles dari enum
            foreach (UserRole::cases() as $roleEnum) {
                Role::firstOrCreate(
                    [
                        'name' => $roleEnum->value,
                        'guard_name' => 'api',
                    ],
                    [
                        'system_reserve' => $roleEnum === UserRole::ADMIN,
                    ]
                );
            }

            // 2️⃣ Buat modules + permissions + assign ke role
            foreach ($modules as $moduleName => $config) {

                Module::updateOrCreate(
                    ['name' => $moduleName],
                    [] // jangan simpan actions, permission sudah punya tabel sendiri
                );

                foreach ($config['actions'] as $actionKey => $permissionName) {

                    // 🔥 WAJIB pakai guard_name
                    $permission = Permission::firstOrCreate([
                        'name' => $permissionName,
                        'guard_name' => 'api',
                        'module_name' => $moduleName
                    ]);

                    // Assign permission ke role sesuai config
                    if (! empty($config['roles'])) {
                        foreach ($config['roles'] as $roleName => $allowedActions) {

                            if (in_array($actionKey, $allowedActions)) {

                                // 🔥 WAJIB sebutkan guard
                                $role = Role::findByName($roleName, 'api');

                                if (! $role->hasPermissionTo($permission)) {
                                    $role->givePermissionTo($permission);
                                }
                            }
                        }
                    }
                }
            }

            // 3️⃣ Pastikan admin selalu punya semua permission
            $admin = Role::findByName(UserRole::ADMIN->value, 'api');
            $admin->syncPermissions(Permission::where('guard_name', 'api')->get());

        });
    }
}
