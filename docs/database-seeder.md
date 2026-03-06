# Database Seeder

## Purpose

The database seeders populate the application with the initial data required for it to function correctly. This includes system roles, permissions, default user accounts, organizational structure, work schedules, leave types, and sample employee records.

Seeders are used during development setup and when resetting the database to a known state.

---

## Running the Seeder

Run all seeders with:

```bash
php artisan db:seed
```

To reset the database and re-seed from scratch:

```bash
php artisan migrate:fresh --seed
```

---

## Seeder Execution Order

The `DatabaseSeeder` runs seeders in the following order:

| # | Seeder | Description |
|---|---|---|
| 1 | `PermissionSeeder` | Creates all roles and permissions, assigns them to roles |
| 2 | `UserSeeder` | Creates default system user accounts |
| 3 | `HolidaySeeder` | Populates public holidays |
| 4 | `SettingSeeder` | Inserts default application settings |
| 5 | `DivisionSeeder` | Creates company divisions |
| 6 | `PositionAllowanceSeeder` | Creates position-linked allowance templates |
| 7 | `AllowanceSeeder` | Creates allowance definitions |
| 8 | `EmployeeSeeder` | Creates employee profiles linked to users |
| 9 | `AttendanceYearSeeder` | Seeds a year's worth of attendance records |
| 10 | `BiometricUserSeeder` | Links users to biometric device records |
| 11 | `WorkModeSeeder` | Inserts work mode options |
| 12 | `WorkScheduleSeeder` | Creates work schedule templates |
| 13 | `EmployeeWorkScheduleSeeder` | Assigns schedules to employees |
| 14 | `ShiftTemplateSeeder` | Creates shift templates |
| 15 | `EmployeeShiftSeeder` | Assigns shifts to employees |
| 16 | `LeaveTypeSeeder` | Creates available leave types |
| 17 | `EmployeeLeaveBalanceSeeder` | Associates leave balances per employee |
| 18 | `LeaveSeeder` | Seeds sample leave requests |
| 19 | `EmployeeLeaveSeeder` | Seeds employee-leave associations |
| 20 | `EarlyLeaveSeeder` | Seeds sample early leave requests |
| 21 | `AttendanceRequestSeeder` | Seeds attendance correction requests |
| 22 | `OvertimeSeeder` | Seeds overtime request records |
| 23 | `PayrollSeeder` | Seeds sample payroll records |

---

## Default Roles

The `PermissionSeeder` creates the following roles:

| Role | System Reserved | Description |
|---|---|---|
| `admin` | Yes | Full access — all permissions synced automatically |
| `owner` | Yes | Company owner — read access across all modules |
| `director` | No | Operational leadership — view and approve |
| `hr` | No | Human Resources — manage employees, leaves, schedules |
| `finance` | No | Finance team — manage payroll, allowances |
| `manager` | No | Division manager — team management and approvals |
| `employee` | No | Regular staff — view own data and submit requests |

---

## Default User Accounts

The `UserSeeder` creates the following accounts, all with the password `password`:

| Name | Email | Role |
|---|---|---|
| Admin System | `admin@app.com` | `admin` |
| Bapak Owner | `owner@app.com` | `owner` |
| Ibu Direktur | `director@app.com` | `director` |
| Project HR | `hr@app.com` | `hr` |
| Bagian Keuangan | `finance@app.com` | `finance` |
| Project Manager | `manager@app.com` | `manager` |
| Operations Manager | `manager2@app.com` | `manager` |
| Nikola Tesla | `employee@app.com` | `employee` |
| *(15 random users)* | *(faker emails)* | `employee` |

> **Warning:** Default credentials are intended for development only. Change all passwords before deploying to a production environment.

---

## Default Admin Credentials

```
Email:    admin@app.com
Password: password
```

---

## Permission System

The `PermissionSeeder` defines permissions following the `module.action` naming pattern, e.g.:

```
user.index     user.create    user.edit
leave.approve  payroll.pay    attendance.export
```

All permissions are scoped to the `api` guard. The `admin` role is synced to receive **all** permissions automatically at the end of the seeder.

---

## Customizing Seeder Data

To adjust the number of random test employees generated, edit `UserSeeder.php`:

```php
$totalTesting = 15; // Change this number according to your testing needs
```
