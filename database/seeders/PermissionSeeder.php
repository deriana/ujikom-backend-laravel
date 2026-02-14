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
                    'show' => 'user.show',
                    'create' => 'user.create',
                    'edit' => 'user.edit',
                    'destroy' => 'user.destroy',
                    'forceDelete' => 'user.forceDelete',
                    'restore' => 'user.restore',
                ],
                'roles' => [
                    UserRole::ADMIN->value => ['index', 'show', 'create', 'edit', 'destroy', 'forceDelete', 'restore'],
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
                    'show' => 'division.show',
                    'create' => 'division.create',
                    'edit' => 'division.edit',
                    'destroy' => 'division.destroy',
                    'restore' => 'division.restore',
                    'forceDelete' => 'division.forceDelete',
                ],
                'roles' => [
                    UserRole::ADMIN->value => ['index', 'show', 'create', 'edit', 'destroy', 'forceDelete', 'restore'],
                ],
            ],

            'positions' => [
                'actions' => [
                    'index' => 'position.index',
                    'show' => 'position.show',
                    'create' => 'position.create',
                    'edit' => 'position.edit',
                    'destroy' => 'position.destroy',
                    'restore' => 'position.restore',
                    'forceDelete' => 'position.forceDelete',
                ],
                'roles' => [
                    UserRole::ADMIN->value => ['index', 'show', 'create', 'edit', 'destroy', 'forceDelete', 'restore'],
                ],
            ],

            'allowances' => [
                'actions' => [
                    'index' => 'allowance.index',
                    'show' => 'allowance.show',
                    'create' => 'allowance.create',
                    'edit' => 'allowance.edit',
                    'destroy' => 'allowance.destroy',
                    'restore' => 'allowance.restore',
                    'forceDelete' => 'allowance.forceDelete',
                ],
                'roles' => [
                    UserRole::ADMIN->value => ['index', 'show', 'create', 'edit', 'destroy', 'forceDelete', 'restore'],
                ],
            ],

            'holidays' => [
                'actions' => [
                    'index' => 'holiday.index',
                    'create' => 'holiday.create',
                    'edit' => 'holiday.edit',
                    'destroy' => 'holiday.destroy',
                ],
                'roles' => [
                    UserRole::ADMIN->value => ['index', 'create', 'edit', 'destroy', 'forceDelete', 'restore'],
                    UserRole::HR->value => ['index', 'create', 'edit', 'destroy', 'forceDelete', 'restore'],
                ],
            ],

            'attendances' => [
                'actions' => [
                    'index' => 'attendance.index',
                    'show' => 'attendance.show',
                ],
                'roles' => [
                    UserRole::ADMIN->value => ['index', 'show'],
                    UserRole::HR->value => ['index', 'show'],
                    UserRole::MANAGER->value => ['index'],
                    UserRole::EMPLOYEE->value => ['index'],
                ],

            ],

            'work_schedules' => [
                'actions' => [
                    'index' => 'work-schedule.index',
                    'show' => 'work-schedule.show',
                    'create' => 'work-schedule.create',
                    'create' => 'work-schedule.create',
                    'show' => 'work-schedule.show',
                    'edit' => 'work-schedule.edit',
                    'destroy' => 'work-schedule.destroy',
                    'restore' => 'work-schedule.restore',
                    'forceDelete' => 'work-schedule.forceDelete',
                ],
                'roles' => [
                    UserRole::ADMIN->value => ['index', 'create', 'show', 'edit', 'destroy', 'restore', 'forceDelete'],
                    UserRole::HR->value => ['index', 'create', 'show', 'edit', 'destroy', 'restore', 'forceDelete'],
                    UserRole::MANAGER->value => ['index', 'show'],
                    UserRole::EMPLOYEE->value => [],
                ],
            ],

            'employee_work_schedules' => [
                'actions' => [
                    'index' => 'employee-work-schedule.index',
                    'show' => 'employee-work-schedule.show',
                    'create' => 'employee-work-schedule.create',
                    'edit' => 'employee-work-schedule.edit',
                    'destroy' => 'employee-work-schedule.destroy',
                ],
                'roles' => [
                    UserRole::ADMIN->value => ['index', 'create', 'show', 'edit', 'destroy'],
                    UserRole::HR->value => ['index', 'create', 'show', 'edit'],
                    UserRole::MANAGER->value => ['index', 'show'],
                    UserRole::EMPLOYEE->value => [],
                ],
            ],

            'shift_templates' => [
                'actions' => [
                    'index' => 'shift-template.index',
                    'show' => 'shift-template.show',
                    'create' => 'shift-template.create',
                    'edit' => 'shift-template.edit',
                    'destroy' => 'shift-template.destroy',
                    'restore' => 'shift-template.restore',
                    'forceDelete' => 'shift-template.forceDelete',
                ],
                'roles' => [
                    UserRole::ADMIN->value => ['index', 'show', 'create', 'edit', 'destroy', 'forceDelete', 'restore'],
                    UserRole::HR->value => ['index', 'show', 'create', 'edit', 'destroy'],
                ],
            ],

            'employee_shifts' => [
                'actions' => [
                    'index' => 'employee-shift.index',
                    'show' => 'employee-shift.show',
                    'create' => 'employee-shift.create',
                    'edit' => 'employee-shift.edit',
                    'destroy' => 'employee-shift.destroy',
                ],
                'roles' => [
                    UserRole::ADMIN->value => ['index', 'show', 'create', 'edit', 'destroy'],
                    UserRole::HR->value => ['index', 'show', 'create', 'edit', 'destroy'],
                    UserRole::MANAGER->value => ['index', 'show', 'create', 'edit', 'destroy'],
                ],
            ],

            'leave_types' => [
                'actions' => [
                    'index' => 'leave-type.index',
                    'create' => 'leave-type.create',
                    'edit' => 'leave-type.edit',
                    'destroy' => 'leave-type.destroy',
                ],
                'roles' => [
                    UserRole::ADMIN->value => ['index', 'show', 'create', 'edit', 'destroy'],
                    UserRole::HR->value => ['index', 'show', 'create', 'edit', 'destroy'],
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
                        'module_name' => $moduleName,
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
