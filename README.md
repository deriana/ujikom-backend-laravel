# HRIS Backend API

## Overview

HRIS Backend API is a **headless Human Resource Information System (HRIS)** built with Laravel 12. It exposes a RESTful JSON API consumed by a separate frontend application. The system covers the full employee lifecycle — from onboarding and daily attendance tracking through payroll generation and leave management — and is designed to be role-based, modular, and automation-friendly via scheduled commands.

---

## Features

- **Authentication & Account Activation** – Sanctum-based token authentication, email verification flow, password reset, and biometric descriptor storage.
- **Role & Permission Management** – Granular, module-level access control powered by Spatie Laravel Permission.
- **Employee Management** – Full CRUD for users/employees including soft-delete, restore, force-delete, and employment termination.
- **Organizational Structure** – Divisions, Teams, and Positions with allowance assignments.
- **Attendance** – Bulk and single attendance submission (e.g., from a fingerprint device), geo-fencing configuration, attendance request corrections, and daily auto-absent scheduling.
- **Work Schedules & Shifts** – Flexible shift templates and per-employee work schedule assignment.
- **Leave Management** – Multi-type leave requests with file attachments, manager approval workflow, and automatic annual leave balance resets.
- **Early Leave** – Separate early-leave request flow with its own approval chain.
- **Overtime** – Overtime request and approval with duration tracking.
- **Payroll** – Automated monthly payroll draft generation including base salary, allowances, overtime pay, late/early-leave deductions, and simplified PPh 21 tax calculation. HR can finalize or void payslips, and download PDF pay slips.
- **Notifications** – In-app notification system with read/unread status management.
- **Holidays** – Holiday management with automatic annual refresh.
- **Settings** – Configurable attendance rules (work hours, geo-fencing radius, etc.) and general company settings.
- **Data Export** – Attendance data export to Excel (XLSX).
- **Dashboards** – Separate admin and employee dashboard summary endpoints.
- **Telescope** – Laravel Telescope integration for debug-mode request/job inspection.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.2+ |
| Framework | Laravel 12 |
| Authentication | Laravel Sanctum 4 |
| Authorization | Spatie Laravel Permission |
| Media / File Storage | Spatie Laravel Media Library 11 |
| PDF Generation | barryvdh/laravel-dompdf 3 |
| Excel Export | Maatwebsite Excel 3 (PhpSpreadsheet) |
| Database | MySQL (default) |
| Queue | Database queue driver (default) |
| Cache | Database cache driver (default) |
| Scheduler | Laravel Scheduler (via `php artisan schedule:run`) |
| Mail | SMTP / Log (configurable via `.env`) |
| Dev Tools | Laravel Telescope, Laravel Debugbar, Laravel Pail, Laravel Pint, Laravel Sail |
| Testing | PHPUnit 11 |
| Build Tool | Vite (for any compiled frontend assets) |

---

## Project Structure

```
├── app/
│   ├── Console/
│   │   └── Commands/           # Artisan scheduled commands
│   │       ├── GenerateMonthlyPayroll.php
│   │       ├── MarkAbsentEmployees.php
│   │       ├── RefreshHolidays.php
│   │       └── ResetLeaveBalances.php
│   ├── Enums/                  # PHP 8.1+ backed enums (roles, statuses, etc.)
│   ├── Exceptions/             # Custom exception handling
│   ├── Exports/
│   │   └── AttendancesExport.php   # Excel export definition
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/            # All API controllers (one per resource)
│   │   │   └── Auth/           # Authentication controllers
│   │   ├── Requests/           # Form request validation classes
│   │   └── Resources/          # API resource transformers (JSON output)
│   ├── Jobs/
│   │   └── GeneratePayrollSlipJob.php  # Queued PDF payslip generation
│   ├── Mail/
│   │   ├── VerifyEmail.php
│   │   └── PasswordResetMail.php
│   ├── Models/                 # Eloquent models (29 models)
│   ├── Notifications/
│   │   └── CrudActivityNotification.php
│   ├── Policies/               # Authorization policies
│   ├── Providers/              # Service providers
│   ├── Services/               # Business logic layer (one service per domain)
│   └── Traits/                 # Reusable model/controller traits
├── database/
│   ├── factories/              # Model factories for testing/seeding
│   ├── migrations/             # 37 migration files
│   └── seeders/                # Database seeders
├── routes/
│   ├── api.php                 # All REST API routes
│   ├── console.php             # Scheduled command definitions
│   └── web.php                 # Web routes (minimal, API-first project)
├── resources/                  # Blade views / mail templates
├── storage/                    # Logs, compiled views, uploaded files
├── tests/                      # Feature and unit tests
├── .env.example                # Environment variable template
├── composer.json               # PHP dependencies & scripts
└── vite.config.js              # Vite build configuration
```

**Key design pattern:** Each domain area has a dedicated **Service class** (`app/Services/`) that encapsulates all business logic. Controllers are kept thin — they delegate to services and return API Resources.

---

## Installation

### Prerequisites

- PHP **8.2+** with extensions: `pdo_mysql`, `mbstring`, `xml`, `gd`, `fileinfo`, `zip`
- Composer
- Node.js & npm
- MySQL (or any Laravel-supported database)

### Steps

1. **Clone the repository**

   ```bash
   git clone <repository-url>
   cd <project-directory>
   ```

2. **Install PHP dependencies**

   ```bash
   composer install
   ```

3. **Copy the environment file**

   ```bash
   cp .env.example .env
   ```

4. **Generate the application key**

   ```bash
   php artisan key:generate
   ```

5. **Configure your database** – Edit `.env` and set `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`.

6. **Run migrations**

   ```bash
   php artisan migrate
   ```

7. **Seed the database** *(optional)*

   ```bash
   php artisan db:seed
   ```

8. **Install Node dependencies and build assets**

   ```bash
   npm install && npm run build
   ```

> **One-command setup (shortcut):** The `composer.json` includes a `setup` script that automates steps 2–8:
> ```bash
> composer setup
> ```

---

## Environment Variables

Copy `.env.example` to `.env` and fill in the values below.

| Variable | Description | Default |
|---|---|---|
| `APP_NAME` | Application name shown in emails | `Laravel` |
| `APP_ENV` | Environment (`local`, `production`) | `local` |
| `APP_KEY` | Encryption key (auto-generated) | *(empty)* |
| `APP_DEBUG` | Enable debug mode | `true` |
| `APP_URL` | Base URL of this backend | `http://localhost` |
| `DB_CONNECTION` | Database driver | `mysql` |
| `DB_HOST` | Database host | `127.0.0.1` |
| `DB_PORT` | Database port | `3306` |
| `DB_DATABASE` | Database name | `laravel` |
| `DB_USERNAME` | Database user | `root` |
| `DB_PASSWORD` | Database password | *(empty)* |
| `QUEUE_CONNECTION` | Queue driver (`database`, `redis`, `sync`) | `database` |
| `CACHE_STORE` | Cache driver | `database` |
| `SESSION_DRIVER` | Session driver | `database` |
| `MAIL_MAILER` | Mail transport (`smtp`, `log`, `mailgun`) | `log` |
| `MAIL_HOST` | SMTP host | `127.0.0.1` |
| `MAIL_PORT` | SMTP port | `2525` |
| `MAIL_USERNAME` | SMTP username | *(empty)* |
| `MAIL_PASSWORD` | SMTP password | *(empty)* |
| `MAIL_FROM_ADDRESS` | Sender email address | `hello@example.com` |
| `FILESYSTEM_DISK` | Default storage disk (`local`, `s3`) | `local` |
| `AWS_*` | AWS S3 credentials (if using S3 storage) | *(empty)* |

---

## Running the Application

### Development (all-in-one)

The `composer dev` script starts the HTTP server, queue listener, and Vite dev server concurrently:

```bash
composer dev
```

### Manual startup

```bash
# Start the HTTP server
php artisan serve

# In a separate terminal – start the queue worker
php artisan queue:listen --tries=1

# In a separate terminal – start the scheduler (runs every minute)
php artisan schedule:work
```

The API will be available at `http://localhost:8000/api`.

---

## Queue / Worker

This application uses **Laravel Queues** for background processing. The default queue driver is `database`.

### What runs on the queue?

| Job | Trigger | Description |
|---|---|---|
| `GeneratePayrollSlipJob` | Payroll finalization | Generates a PDF payslip for an employee and stores it via Media Library |

### Starting the queue worker

```bash
# Development (restarts on code changes)
php artisan queue:listen --tries=1

# Production (persistent, restart required after deploys)
php artisan queue:work --tries=3 --timeout=60
```

For production, it is recommended to manage the worker with **Supervisor**.

---

## Scheduler

The Laravel Scheduler must be run via a cron job that fires every minute:

```cron
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

### Scheduled Commands

| Command | Schedule | Description |
|---|---|---|
| `attendance:mark-absent` | Daily at **17:10** | Inserts `absent` records for employees with no clock-in on a workday |
| `holidays:refresh` | **Jan 1st, 00:00** (yearly) | Refreshes public holiday data for the new year |
| `leave:reset-balances` | **Jan 1st, 00:00** (yearly) | Resets annual leave balances for all employees |
| `payroll:generate` | **26th of each month, 00:00** | Auto-generates monthly payroll drafts for all active employees |

---

## API Documentation

> All API routes are prefixed with `/api`. Protected routes require a `Bearer` token obtained from `/api/auth/login`.

### Authentication (`/api/auth`)

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `POST` | `/auth/register` | No | Register a new user |
| `POST` | `/auth/login` | No | Log in and receive a Sanctum token |
| `GET` | `/auth/me` | Yes | Get the authenticated user's full profile |
| `POST` | `/auth/logout` | Yes | Revoke the current token |
| `GET` | `/auth/check-token` | No | Validate an account activation token |
| `POST` | `/auth/resend-verification` | No | Resend the email verification link |
| `POST` | `/auth/finalize-activation` | No | Set password and activate a new account |
| `GET` | `/auth/reset-password/check` | No | Validate a password reset token |
| `POST` | `/auth/forgot-password` | No | Send a password reset email |
| `POST` | `/auth/reset-password` | No | Reset password using a token |

### Users & Employees (`/api/users`)

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `GET` | `/users` | Yes | List all users (paginated) |
| `POST` | `/users` | Yes | Create a new user/employee |
| `GET` | `/users/{uuid}` | Yes | Get a specific user |
| `PUT` | `/users/{uuid}` | Yes | Update a user |
| `DELETE` | `/users/{uuid}` | Yes | Soft-delete a user |
| `POST` | `/users/restore/{uuid}` | Yes | Restore a soft-deleted user |
| `DELETE` | `/users/force-delete/{uuid}` | Yes | Permanently delete a user |
| `GET` | `/users/trashed` | Yes | List soft-deleted users |
| `PUT` | `/users/terminate-employment/{uuid}` | Yes | Terminate an employee |
| `PUT` | `/users/change-password/{uuid}` | Yes | Change a user's password |
| `PUT` | `/users/status/{uuid}` | Yes | Toggle user active/inactive status |
| `POST` | `/users/upload-profile-photo/{uuid}` | Yes | Upload a profile photo |
| `GET` | `/users/managers` | Yes | List users with manager roles |
| `GET` | `/users/employees-lite` | Yes | Lightweight employee list for dropdowns |
| `GET` | `/users/profile` | Yes | Get the authenticated user's profile |
| `PUT` | `/users/update-biometric` | Yes | Update biometric face descriptors |

### Attendance

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `POST` | `/attendance/bulk-send` | No | Submit multiple attendance records (e.g., from device) |
| `POST` | `/attendance/single-send` | No | Submit a single attendance record |
| `GET` | `/attendances` | Yes | List attendance records |
| `GET` | `/attendances/{id}` | Yes | Get a specific attendance record |
| `GET` | `/attendances/today` | Yes | Get today's attendance status |
| `GET` | `/attendances/export` | Yes | Export attendance data to Excel |

### Attendance Request (Correction)

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `GET` | `/attendance_request` | Yes | List attendance requests |
| `POST` | `/attendance_request` | Yes | Create an attendance correction request |
| `GET` | `/attendance_request/{id}` | Yes | Get a specific request |
| `PUT` | `/attendance_request/{id}` | Yes | Update a request |
| `DELETE` | `/attendance_request/{id}` | Yes | Delete a request |
| `PUT` | `/attendance_request/{uuid}/approve` | Yes | Approve or reject a request |

### Leaves

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `GET` | `/leaves` | Yes | List all leave requests |
| `POST` | `/leaves` | Yes | Submit a leave request (supports file attachment) |
| `GET` | `/leaves/{id}` | Yes | Get a specific leave request |
| `POST` | `/leaves/{id}` | Yes | Update a leave request |
| `DELETE` | `/leaves/{id}` | Yes | Cancel/delete a leave request |
| `PUT` | `/leaves/approvals/{uuid}/approve` | Yes | Approve or reject a leave request |
| `GET` | `/leaves/my-leaves` | Yes | List the authenticated employee's leaves |
| `GET` | `/leaves/download-attachment/{filename}` | No | Download a leave attachment |

### Early Leaves

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `GET` | `/early_leaves` | Yes | List all early-leave requests |
| `POST` | `/early_leaves` | Yes | Submit an early-leave request |
| `GET` | `/early_leaves/{id}` | Yes | Get a specific early-leave request |
| `POST` | `/early_leaves/{id}` | Yes | Update an early-leave request |
| `DELETE` | `/early_leaves/{id}` | Yes | Delete an early-leave request |
| `PUT` | `/early_leaves/approvals/{uuid}/approve` | Yes | Approve or reject an early-leave request |

### Overtime

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `GET` | `/overtime` | Yes | List overtime requests |
| `POST` | `/overtime` | Yes | Submit an overtime request |
| `GET` | `/overtime/{id}` | Yes | Get a specific overtime request |
| `PUT` | `/overtime/{id}` | Yes | Update an overtime request |
| `DELETE` | `/overtime/{id}` | Yes | Delete an overtime request |
| `PUT` | `/overtime/{uuid}/approve` | Yes | Approve or reject overtime |

### Payroll

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `GET` | `/payrolls` | Yes | List payroll records |
| `GET` | `/payrolls/{id}` | Yes | Get a payroll detail |
| `PUT` | `/payrolls/{id}` | Yes | Update a payroll record |
| `PUT` | `/payrolls/{uuid}/finalize` | Yes | Finalize a payroll (triggers PDF job) |
| `PUT` | `/payrolls/{uuid}/void` | Yes | Void a finalized payroll |
| `GET` | `/payrolls/{uuid}/download` | Yes | Download the PDF payslip |

### Notifications

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `GET` | `/notifications` | Yes | Get all notifications |
| `GET` | `/notifications/unread` | Yes | Get unread notifications |
| `PATCH` | `/notifications/{id}/read` | Yes | Mark a notification as read |
| `PATCH` | `/notifications/mark-all-read` | Yes | Mark all notifications as read |
| `DELETE` | `/notifications/{id}` | Yes | Delete a notification |
| `DELETE` | `/notifications/delete-all` | Yes | Delete all notifications |

### Other Resources (all authenticated)

| Resource | Base Path | Notes |
|---|---|---|
| Roles & Permissions | `/roles`, `/permissions/modules` | Module-level permission listing |
| Divisions | `/divisions` | Soft-delete + restore |
| Positions | `/positions` | Linked to allowances |
| Allowances | `/allowances` | Fixed or percentage-based |
| Work Schedules | `/work_schedules` | Soft-delete + restore |
| Employee Work Schedules | `/employee_work_schedules` | Per-employee schedule assignment |
| Shift Templates | `/shift_templates` | Soft-delete + restore |
| Employee Shifts | `/employee_shift` | Per-employee shift assignment |
| Leave Types | `/leave_types` | Configurable leave categories |
| Holidays | `/holidays` | Full CRUD |
| Settings | `/settings/get`, `/settings/attendance`, `/settings/geo_fencing`, `/settings/general` | System configuration |
| Dashboard | `/dashboard/admin`, `/dashboard/employee` | Role-specific summaries |

### Approval Inbox

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `GET` | `/approvals/leaves` | Yes | Pending leave approvals for the current approver |
| `GET` | `/approvals/early_leaves` | Yes | Pending early-leave approvals |
| `GET` | `/approvals/attendance_request` | Yes | Pending attendance correction approvals |
| `GET` | `/approvals/overtime` | Yes | Pending overtime approvals |

---

## Database

### Engine

MySQL (default). The `DB_CONNECTION` variable in `.env` can be changed to any driver supported by Laravel (PostgreSQL, SQLite, etc.).

### Migrations

Run all migrations with:

```bash
php artisan migrate
```

Roll back the last batch:

```bash
php artisan migrate:rollback
```

There are **37 migration files** covering the following tables:

| Table | Purpose |
|---|---|
| `users` | Core authentication and employee linking |
| `employees` | HR profile data (salary, join date, biometrics, etc.) |
| `divisions`, `teams` | Organizational hierarchy |
| `positions`, `allowances`, `position_allowances` | Job positions with linked allowances |
| `work_modes` | Work mode definitions (WFH, WFO, etc.) |
| `attendances`, `attendance_logs`, `attendance_requests` | Time and attendance tracking |
| `biometric_users` | Face descriptor storage for biometric login |
| `holidays` | Company holidays |
| `work_schedules`, `employee_work_schedules` | Flexible schedule assignment |
| `shift_templates`, `employee_shifts` | Shift management |
| `leave_types`, `leaves`, `leave_approvals`, `employee_leaves`, `employee_leave_balances` | Leave lifecycle |
| `early_leaves` | Early departure requests |
| `overtimes` | Overtime requests |
| `payrolls` | Payroll records with full salary breakdown |
| `notifications` | In-app notification storage |
| `verification_tokens`, `password_resets` | Account activation and password reset tokens |
| `personal_access_tokens` | Sanctum API tokens |
| `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` | Spatie Permission tables |
| `media` | Spatie Media Library file metadata |
| `modules` | Application module registry for permission UI |
| `settings` | Key-value system configuration |
| `jobs`, `failed_jobs`, `job_batches` | Laravel queue tables |
| `cache`, `cache_locks` | Database cache |
| `sessions` | Database sessions |
| `telescope_entries`, `telescope_entries_tags`, `telescope_monitoring` | Laravel Telescope debug data |

---

## Development Workflow

```bash
# 1. Start all dev services (server, queue, vite)
composer dev

# 2. Run code style fixer (Laravel Pint)
./vendor/bin/pint

# 3. Run the test suite
composer test
# or
php artisan test

# 4. Inspect the app via Telescope (debug mode only)
# Visit: http://localhost:8000/telescope

# 5. Tail logs in real time (Laravel Pail)
php artisan pail

# 6. Open Tinker for REPL exploration
php artisan tinker
```

### Branching

Not clearly defined in the repository.

---

## Deployment

1. **Set environment variables** – Ensure `APP_ENV=production`, `APP_DEBUG=false`, and all credentials are set correctly on your server.

2. **Install dependencies (no dev)**

   ```bash
   composer install --optimize-autoloader --no-dev
   ```

3. **Build frontend assets**

   ```bash
   npm ci && npm run build
   ```

4. **Run migrations**

   ```bash
   php artisan migrate --force
   ```

5. **Optimize the application**

   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan event:cache
   ```

6. **Configure Supervisor** to keep the queue worker running:

   ```ini
   [program:hris-worker]
   command=php /path/to/artisan queue:work --tries=3 --timeout=60 --sleep=3
   autostart=true
   autorestart=true
   user=www-data
   redirect_stderr=true
   ```

7. **Configure a cron job** for the Laravel Scheduler:

   ```cron
   * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
   ```

8. **Storage link** – If using local disk for public files:

   ```bash
   php artisan storage:link
   ```

---

## License

This project is licensed under the [MIT License](https://opensource.org/licenses/MIT).
