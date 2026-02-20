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
                    UserRole::DIRECTOR->value => ['index', 'show'],
                    UserRole::OWNER->value => ['index', 'show'],
                    UserRole::MANAGER->value => ['index', 'show'],
                    UserRole::HR->value => ['index', 'show', 'create', 'edit', 'destroy'],
                    UserRole::FINANCE->value => ['index', 'show'],
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
                    UserRole::OWNER->value => ['index'],
                    UserRole::DIRECTOR->value => ['index'],
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
                    UserRole::DIRECTOR->value => ['index', 'show'],
                    UserRole::OWNER->value => ['index', 'show'],
                    UserRole::MANAGER->value => ['index', 'show'],
                    UserRole::HR->value => ['index', 'show', 'create', 'edit', 'destroy'],
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
                    UserRole::DIRECTOR->value => ['index', 'show'],
                    UserRole::OWNER->value => ['index', 'show'],
                    UserRole::MANAGER->value => ['index', 'show'],
                    UserRole::HR->value => ['index', 'show', 'create', 'edit', 'destroy'],
                    UserRole::FINANCE->value => ['index', 'show', 'create', 'edit', 'destroy'],
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
                    UserRole::DIRECTOR->value => ['index', 'show'],
                    UserRole::OWNER->value => ['index', 'show'],
                    UserRole::MANAGER->value => ['index', 'show'],
                    UserRole::HR->value => ['index', 'show'],
                    UserRole::FINANCE->value => ['index', 'show', 'create', 'edit', 'destroy'],
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
                    UserRole::HR->value => ['index', 'create', 'edit', 'destroy'],
                    UserRole::DIRECTOR->value => ['index'],
                    UserRole::OWNER->value => ['index'],
                    UserRole::MANAGER->value => ['index'],
                    UserRole::FINANCE->value => ['index'],
                    UserRole::EMPLOYEE->value => ['index'],
                ],
            ],

            'attendances' => [
                'actions' => [
                    'index' => 'attendance.index',
                    'show' => 'attendance.show',
                    'export' => 'attendance.export', // Wajib ada untuk rekap
                    'sync' => 'attendance.sync',     // Tambahan: Untuk tarik data dari mesin/log jika ada delay
                ],
                'roles' => [
                    UserRole::ADMIN->value => ['index', 'show', 'sync'],
                    UserRole::HR->value => ['index', 'show', 'export', 'sync'],
                    UserRole::MANAGER->value => ['index', 'show'],
                    UserRole::EMPLOYEE->value => ['index', 'show'],
                    UserRole::DIRECTOR->value => ['index', 'show', 'export'],
                    UserRole::OWNER->value => ['index', 'show', 'export'],
                    UserRole::FINANCE->value => ['index', 'show', 'export'],
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
                    UserRole::HR->value => ['index', 'create', 'show', 'edit', 'destroy'],
                    UserRole::MANAGER->value => ['index', 'show'],
                    UserRole::DIRECTOR->value => ['index', 'show'],
                    UserRole::OWNER->value => ['index', 'show'],
                ],
            ],

            'employee_work_schedules' => [
                'actions' => [
                    'index' => 'employee-work-schedule.index',
                    'show' => 'employee-work-schedule.show',
                    'create' => 'employee-work-schedule.create',
                    'edit' => 'employee-work-schedule.edit',
                    'destroy' => 'employee-work-schedule.destroy',
                    'restore' => 'employee-work-schedule.restore',
                    'forceDelete' => 'employee-work-schedule.forceDelete',
                    'export' => 'employee-work-schedule.export',
                ],
                'roles' => [
                    UserRole::ADMIN->value => ['index', 'create', 'show', 'edit', 'destroy', 'restore', 'forceDelete'],
                    UserRole::HR->value => ['index', 'create', 'show', 'edit', 'destroy', 'export'],
                    UserRole::MANAGER->value => ['index', 'show', 'create', 'edit', 'destroy'],
                    UserRole::DIRECTOR->value => ['index', 'show', 'export'],
                    UserRole::OWNER->value => ['index', 'show'],
                    UserRole::FINANCE->value => ['index', 'show', 'export'],
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
                    UserRole::ADMIN->value => ['index', 'create', 'show', 'edit', 'destroy', 'restore', 'forceDelete'],
                    UserRole::HR->value => ['index', 'create', 'show', 'edit', 'destroy'],
                    UserRole::MANAGER->value => ['index', 'show'],
                    UserRole::DIRECTOR->value => ['index', 'show'],
                    UserRole::OWNER->value => ['index', 'show'],
                ],
            ],

            'employee_shifts' => [
                'actions' => [
                    'index' => 'employee-shift.index',
                    'show' => 'employee-shift.show',
                    'create' => 'employee-shift.create',
                    'edit' => 'employee-shift.edit',
                    'destroy' => 'employee-shift.destroy',
                    'restore' => 'employee-shift.restore',
                    'forceDelete' => 'employee-shift.forceDelete',
                    'export' => 'employee-shift.export',
                ],
                'roles' => [
                    UserRole::ADMIN->value => ['index', 'create', 'show', 'edit', 'destroy', 'restore', 'forceDelete'],
                    UserRole::HR->value => ['index', 'create', 'show', 'edit', 'destroy', 'export'],
                    UserRole::MANAGER->value => ['index', 'show', 'create', 'edit', 'destroy'],
                    UserRole::DIRECTOR->value => ['index', 'show', 'export'],
                    UserRole::OWNER->value => ['index', 'show'],
                    UserRole::FINANCE->value => ['index', 'show', 'export'],
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
                    UserRole::MANAGER->value => ['index', 'show'],
                    UserRole::DIRECTOR->value => ['index', 'show'],
                    UserRole::OWNER->value => ['index', 'show'],
                ],
            ],

            'leaves' => [
                'actions' => [
                    'index' => 'leave.index',
                    'show' => 'leave.show',
                    'create' => 'leave.create',
                    'edit' => 'leave.edit',
                    'destroy' => 'leave.destroy',
                    'approve' => 'leave.approve',
                    'export' => 'leave.export',
                ],
                'roles' => [
                    UserRole::ADMIN->value => ['index', 'show', 'create', 'edit', 'destroy', 'approve', 'export'],
                    UserRole::HR->value => ['index', 'show', 'create', 'edit', 'destroy', 'approve', 'export'],
                    UserRole::MANAGER->value => ['index', 'show', 'create', 'edit', 'destroy', 'approve'],
                    UserRole::EMPLOYEE->value => ['index', 'show', 'create', 'edit', 'destroy'],
                    UserRole::DIRECTOR->value => ['index', 'show', 'create', 'edit', 'destroy', 'approve', 'export'],
                    UserRole::OWNER->value => ['index', 'show', 'export'],
                    UserRole::FINANCE->value => ['index', 'show', 'export'],
                ],
            ],

            'early_leaves' => [
                'actions' => [
                    'index' => 'early-leave.index',
                    'show' => 'early-leave.show',
                    'create' => 'early-leave.create',
                    'edit' => 'early-leave.edit',
                    'destroy' => 'early-leave.destroy',
                    'approve' => 'early-leave.approve',
                    'export' => 'early-leave.export',
                ],
                'roles' => [
                    UserRole::ADMIN->value => ['index', 'show', 'create', 'edit', 'destroy', 'approve', 'export'],
                    UserRole::HR->value => ['index', 'show', 'create', 'edit', 'destroy', 'export'],
                    UserRole::MANAGER->value => ['index', 'show', 'create', 'edit', 'destroy', 'approve'],
                    UserRole::EMPLOYEE->value => ['index', 'show', 'create', 'edit', 'destroy'],
                    UserRole::DIRECTOR->value => ['index', 'show', 'approve', 'export'],
                    UserRole::OWNER->value => ['index', 'show', 'export'],
                    UserRole::FINANCE->value => ['index', 'show', 'export'],
                ],
            ],

            'attendance_requests' => [
                'actions' => [
                    'index' => 'attendance-request.index',
                    'show' => 'attendance-request.show',
                    'create' => 'attendance-request.create',
                    'edit' => 'attendance-request.edit',
                    'destroy' => 'attendance-request.destroy',
                    'approve' => 'attendance-request.approve',
                    'export' => 'attendance-request.export',
                ],
                'roles' => [
                    UserRole::ADMIN->value => ['index', 'show', 'edit', 'destroy', 'approve', 'export'],
                    UserRole::HR->value => ['index', 'show', 'create', 'edit', 'destroy', 'approve', 'export'],
                    UserRole::MANAGER->value => ['index', 'show', 'create', 'edit', 'destroy', 'approve'],
                    UserRole::EMPLOYEE->value => ['index', 'show', 'create', 'edit', 'destroy'],
                    UserRole::DIRECTOR->value => ['index', 'show', 'approve', 'export'],
                    UserRole::OWNER->value => ['index', 'show', 'export'],
                    UserRole::FINANCE->value => ['index', 'show', 'create', 'edit', 'destroy', 'export'],
                ],
            ],

            'overtimes' => [
                'actions' => [
                    'index' => 'overtime.index',
                    'show' => 'overtime.show',
                    'create' => 'overtime.create',
                    'edit' => 'overtime.edit',
                    'destroy' => 'overtime.destroy',
                    'approve' => 'overtime.approve',
                    'export' => 'overtime.export',
                ],
                'roles' => [
                    UserRole::ADMIN->value => ['index', 'show', 'create', 'edit', 'destroy', 'approve'],
                    UserRole::HR->value => ['index', 'show', 'create', 'edit', 'destroy', 'approve'],
                    UserRole::MANAGER->value => ['index', 'show', 'create', 'edit', 'destroy', 'approve'],
                    UserRole::EMPLOYEE->value => ['index', 'show', 'create', 'edit', 'destroy'],
                    UserRole::DIRECTOR->value => ['index', 'show', 'create', 'edit', 'destroy', 'approve'],
                    UserRole::OWNER->value => ['index', 'show'],
                    UserRole::FINANCE->value => ['index', 'show', 'export'],
                ],
            ],

            'payrolls' => [
                'actions' => [
                    'index' => 'payroll.index',
                    'show' => 'payroll.show',
                    'create' => 'payroll.create',
                    'edit' => 'payroll.edit',
                    'destroy' => 'payroll.destroy',
                    'export' => 'payroll.export',
                    'pay' => 'payroll.pay',
                ],
                'roles' => [
                    UserRole::ADMIN->value => ['index', 'show'],
                    UserRole::FINANCE->value => ['index', 'show', 'create', 'edit', 'destroy', 'export', 'pay'],
                    UserRole::DIRECTOR->value => ['index', 'show'],
                    UserRole::OWNER->value => ['index', 'show', 'export'],
                    UserRole::EMPLOYEE->value => ['show'],
                ],
            ],

            'dashboards' => [
                'actions' => [
                    'admin' => 'dashboard.admin',
                    'employee' => 'dashboard.employee',
                ],
                'roles' => [
                    UserRole::ADMIN->value => ['admin'],
                    UserRole::OWNER->value => ['admin'],
                    UserRole::DIRECTOR->value => ['admin', 'employee'],
                    UserRole::FINANCE->value => ['admin', 'employee'],
                    UserRole::HR->value => ['admin', 'employee'],
                    UserRole::MANAGER->value => ['admin', 'employee'],
                    UserRole::EMPLOYEE->value => ['employee'],
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
                        'system_reserve' => in_array($roleEnum, [UserRole::ADMIN, UserRole::OWNER]),
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
