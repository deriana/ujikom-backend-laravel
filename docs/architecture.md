# System Architecture

## High-Level Architecture

```
┌───────────────────────────────────────────────────────────┐
│                        Frontend App                        │
│              (React / Vue / Flutter / etc.)                │
└────────────────────────────┬──────────────────────────────┘
                             │ HTTPS / JSON API
                             ▼
┌───────────────────────────────────────────────────────────┐
│              Laravel 12 API Backend                       │
│  ┌──────────────┐  ┌────────────────┐  ┌───────────────┐  │
│  │   Routes     │  │  Middleware    │  │  Controllers  │  │
│  │  (api.php)   │→ │ (Auth, RBAC,   │→ │  (Api/*)      │  │
│  │              │  │  Throttle)     │  │               │  │
│  └──────────────┘  └────────────────┘  └──────┬────────┘  │
│                                               │           │
│  ┌────────────────────────────────────────────▼────────┐  │
│  │                   Service Layer                     │  │
│  │              (app/Services/*)                       │  │
│  └────────────────────────────────────────────┬────────┘  │
│                                               │           │
│  ┌────────────────────────────────────────────▼────────┐  │
│  │                   Eloquent ORM                      │  │
│  │              (app/Models/*)                         │  │
│  └────────────────────────────────────────────┬────────┘  │
└───────────────────────────────────────────────┼───────────┘
                                                │
                             ┌──────────────────▼──────────────────┐
                             │          MySQL Database             │
                             └─────────────────────────────────────┘
```

---

## Frontend and Backend Interaction

The system uses a **headless architecture**:

- The frontend is a completely independent application (not served by Laravel).
- All communication is done over **HTTP/HTTPS** using **JSON**.
- The frontend authenticates using a **bearer token** received after login.
- Laravel serves no views or HTML pages — only a JSON API.

---

## API Communication Flow

```
Client Request
    │
    ├─ No Auth Required (public)
    │       └─ POST /api/auth/login
    │       └─ POST /api/auth/forgot-password
    │       └─ POST /api/attendance/bulk-send
    │
    └─ Auth Required (auth:sanctum)
            └─ Bearer Token → Middleware validates token
                    └─ RBAC check (Spatie Permission)
                            └─ Controller → Service → Model → DB
                                    └─ JSON Response
```

**Rate limiting** is applied globally via the `throttle:api` middleware to prevent abuse.

---

## Application Layers

### Routes (`routes/api.php`)
Defines all API endpoints grouped by feature module. Public routes are outside the `auth:sanctum` middleware group.

### Middleware
| Middleware | Purpose |
|---|---|
| `auth:sanctum` | Validates the bearer token on protected routes |
| `throttle:api` | Rate-limits all API requests |

### Controllers (`app/Http/Controllers/Api/`)
Each feature module has its own dedicated controller. Controllers handle request input and delegate business logic to services or models.

### Services (`app/Services/`)
Business logic layer. Keeps controllers thin by moving complex operations (payroll computation, attendance processing, etc.) into service classes.

### Models (`app/Models/`)
Eloquent models representing database tables. Relationships, casts, scopes, and soft-deletes are defined at this layer.

### Policies (`app/Policies/`)
Authorization policies for fine-grained access control on model-level operations.

---

## Database Layer

- **Database**: MySQL (configurable)
- **ORM**: Laravel Eloquent
- **Migrations**: Located in `database/migrations/`
- **Soft Deletes**: Used widely across core models (Users, Employees, Divisions, Positions, etc.) to allow data recovery and history tracing

Key relational structure:

```
User (1) ──── (1) Employee
Employee (N) ──── (1) Position
Position (N) ──── (1) Team
Team (N) ──── (1) Division
Employee (1) ──── (N) EmployeeWorkSchedule → WorkSchedule
Employee (1) ──── (N) Attendance
Employee (1) ──── (N) Leave / EarlyLeave / Overtime
Employee (1) ──── (N) Payroll
```

---

## Service Layer & Repository Pattern

This project uses a **Service Layer** pattern:

- Controllers receive HTTP requests and return HTTP responses.
- Services encapsulate business rules (e.g. attendance syncing, payroll finalization).
- Models handle data persistence via Eloquent.

There is no formal Repository layer — Eloquent models are used directly within services.

---

## Key Third-Party Packages

| Package | Role |
|---|---|
| `laravel/sanctum` | API token authentication |
| `spatie/laravel-permission` | Role & permission management (RBAC) |
| `spatie/laravel-medialibrary` | File/media attachment handling |
| `maatwebsite/excel` | Excel export functionality |
| `barryvdh/laravel-dompdf` | PDF generation (payslips) |
