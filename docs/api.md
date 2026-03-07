# API Documentation

## Base URL

```
http://localhost:8000/api
```

## Authentication

All protected endpoints require a bearer token in the request header:

```
Authorization: Bearer <token>
Content-Type: application/json
Accept: application/json
```

---

## Modules

- [Authentication](#authentication)
- [Users](#users)
- [Roles](#roles)
- [Divisions & Positions](#divisions--positions)
- [Allowances](#allowances)
- [Employees & Schedules](#employees--schedules)
- [Attendance](#attendance)
- [Leaves](#leaves)
- [Early Leave](#early-leave)
- [Attendance Requests](#attendance-requests)
- [Overtime](#overtime)
- [Payroll](#payroll)
- [Approvals](#approvals)
- [Notifications](#notifications)
- [Settings](#settings)
- [Dashboard](#dashboard)

---

## Authentication

| Endpoint | Method | URL | Description | Auth Required |
|---|---|---|---|---|
| Login | POST | `/auth/login` | Authenticate and receive a bearer token | No |
| Register | POST | `/auth/register` | Register a new user | No |
| Logout | POST | `/auth/logout` | Revoke current token | Yes |
| Get Current User | GET | `/auth/me` | Get authenticated user profile | Yes |
| Check Activation Token | GET | `/auth/check-token` | Validate account activation token | No |
| Resend Verification | POST | `/auth/resend-verification` | Resend the activation email | No |
| Finalize Activation | POST | `/auth/finalize-activation` | Set password and activate account | No |
| Check Reset Token | GET | `/auth/reset-password/check` | Validate a password reset token | No |
| Send Reset Link | POST | `/auth/forgot-password` | Send password reset email | No |
| Reset Password | POST | `/auth/reset-password` | Submit new password | No |

### Login — Request Example

```json
POST /api/auth/login
{
  "email": "admin@app.com",
  "password": "password"
}
```

### Login — Response Example

```json
{
  "data": {
    "token": "1|xyz...",
    "user": {
      "uuid": "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
      "name": "Admin System",
      "email": "admin@app.com",
      "roles": ["admin"]
    }
  }
}
```

---

## Users

| Endpoint | Method | URL | Description | Auth Required |
|---|---|---|---|---|
| List Users | GET | `/users` | Paginated list of users | Yes |
| Get User | GET | `/users/{uuid}` | Get a specific user | Yes |
| Create User | POST | `/users` | Create a new user | Yes |
| Update User | PUT | `/users/{uuid}` | Update user details | Yes |
| Delete User | DELETE | `/users/{uuid}` | Soft delete a user | Yes |
| List Trashed | GET | `/users/trashed` | Get soft-deleted users | Yes |
| Restore User | POST | `/users/restore/{uuid}` | Restore a soft-deleted user | Yes |
| Force Delete | DELETE | `/users/force-delete/{uuid}` | Permanently delete a user | Yes |
| Terminate Employment | PUT | `/users/terminate-employment/{uuid}` | Mark employee as terminated | Yes |
| Change Password | PUT | `/users/change-password/{uuid}` | Change a user's password | Yes |
| Upload Profile Photo | POST | `/users/upload-profile-photo/{uuid}` | Upload profile picture | Yes |
| Get Managers | GET | `/users/managers` | List users who are managers | Yes |
| Get Employees Lite | GET | `/users/employees-lite` | Lightweight list of employees | Yes |
| Get My Profile | GET | `/users/profile` | Get own profile | Yes |
| Update Biometric | PUT | `/users/update-biometric` | Update biometric descriptor data | Yes |

---

## Roles

| Endpoint | Method | URL | Description | Auth Required |
|---|---|---|---|---|
| List Roles | GET | `/roles` | List all roles | Yes |
| Create Role | POST | `/roles` | Create a new role | Yes |
| Update Role | PUT | `/roles/{id}` | Update role permissions | Yes |
| Delete Role | DELETE | `/roles/{id}` | Delete a role | Yes |
| List Permission Modules | GET | `/permissions/modules` | Get all modules and permissions | Yes |

---

## Divisions & Positions

### Divisions

| Endpoint | Method | URL | Description | Auth Required |
|---|---|---|---|---|
| List Divisions | GET | `/divisions` | List all divisions | Yes |
| Get Division | GET | `/divisions/{uuid}` | Get a specific division | Yes |
| Create Division | POST | `/divisions` | Create a division | Yes |
| Update Division | PUT | `/divisions/{uuid}` | Update a division | Yes |
| Delete Division | DELETE | `/divisions/{uuid}` | Soft delete a division | Yes |
| Restore Division | POST | `/divisions/restore/{uuid}` | Restore a deleted division | Yes |
| Force Delete | DELETE | `/divisions/force-delete/{uuid}` | Permanently delete | Yes |
| List Trashed | GET | `/divisions/trashed` | Get soft-deleted divisions | Yes |

### Positions

| Endpoint | Method | URL | Description | Auth Required |
|---|---|---|---|---|
| List Positions | GET | `/positions` | List all positions | Yes |
| Get Position | GET | `/positions/{uuid}` | Get a specific position | Yes |
| Create Position | POST | `/positions` | Create a position | Yes |
| Update Position | PUT | `/positions/{uuid}` | Update a position | Yes |
| Delete Position | DELETE | `/positions/{uuid}` | Soft delete a position | Yes |
| Restore Position | POST | `/positions/restore/{uuid}` | Restore a deleted position | Yes |
| Force Delete | DELETE | `/positions/force-delete/{uuid}` | Permanently delete | Yes |
| List Trashed | GET | `/positions/trashed` | Get soft-deleted positions | Yes |

---

## Allowances

| Endpoint | Method | URL | Description | Auth Required |
|---|---|---|---|---|
| List Allowances | GET | `/allowances` | List all allowances | Yes |
| Get Allowance | GET | `/allowances/{uuid}` | Get a specific allowance | Yes |
| Create Allowance | POST | `/allowances` | Create an allowance | Yes |
| Update Allowance | PUT | `/allowances/{uuid}` | Update an allowance | Yes |
| Delete Allowance | DELETE | `/allowances/{uuid}` | Soft delete | Yes |
| Restore Allowance | POST | `/allowances/restore/{uuid}` | Restore | Yes |
| Force Delete | DELETE | `/allowances/force-delete/{uuid}` | Permanently delete | Yes |
| List Trashed | GET | `/allowances/trashed` | Soft-deleted allowances | Yes |

---

## Employees & Schedules

### Work Schedules

| Endpoint | Method | URL | Description | Auth Required |
|---|---|---|---|---|
| List | GET | `/work_schedules` | List all work schedules | Yes |
| Create | POST | `/work_schedules` | Create a work schedule | Yes |
| Update | PUT | `/work_schedules/{uuid}` | Update | Yes |
| Delete | DELETE | `/work_schedules/{uuid}` | Soft delete | Yes |
| List Trashed | GET | `/work_schedules/trashed` | Deleted schedules | Yes |
| Restore | POST | `/work_schedules/restore/{uuid}` | Restore | Yes |
| Force Delete | DELETE | `/work_schedules/force-delete/{uuid}` | Permanently delete | Yes |

### Employee Work Schedules

| Endpoint | Method | URL | Description | Auth Required |
|---|---|---|---|---|
| List | GET | `/employee_work_schedules` | List employee-schedule assignments | Yes |
| Create | POST | `/employee_work_schedules` | Assign schedule to employee | Yes |
| Update | PUT | `/employee_work_schedules/{id}` | Update assignment | Yes |
| Delete | DELETE | `/employee_work_schedules/{id}` | Remove assignment | Yes |

### Shift Templates

| Endpoint | Method | URL | Description | Auth Required |
|---|---|---|---|---|
| List | GET | `/shift_templates` | List all shift templates | Yes |
| Create | POST | `/shift_templates` | Create a shift template | Yes |
| Update | PUT | `/shift_templates/{uuid}` | Update | Yes |
| Delete | DELETE | `/shift_templates/{uuid}` | Soft delete | Yes |
| List Trashed | GET | `/shift_templates/trashed` | Deleted templates | Yes |
| Restore | POST | `/shift_templates/restore/{uuid}` | Restore | Yes |
| Force Delete | DELETE | `/shift_templates/force-delete/{uuid}` | Permanently delete | Yes |

### Employee Shifts

| Endpoint | Method | URL | Description | Auth Required |
|---|---|---|---|---|
| List | GET | `/employee_shift` | List employee shifts | Yes |
| Create | POST | `/employee_shift` | Assign shift to employee | Yes |
| Update | PUT | `/employee_shift/{id}` | Update | Yes |
| Delete | DELETE | `/employee_shift/{id}` | Delete shift assignment | Yes |

---

## Attendance

| Endpoint | Method | URL | Description | Auth Required |
|---|---|---|---|---|
| List Attendance Records | GET | `/attendances` | Paginated attendance list | Yes |
| Get Record | GET | `/attendances/{id}` | Get a single attendance record | Yes |
| Export Attendance | GET | `/attendances/export` | Export attendance to Excel | Yes |
| Today's Status | GET | `/attendances/today` | Check current user's attendance status today | Yes |
| Bulk Attendance Sync | POST | `/attendance/bulk-send` | Sync multiple records from biometric device | No |
| Single Attendance Sync | POST | `/attendance/single-send` | Sync a single record | No |

### Bulk Send — Request Example

```json
POST /api/attendance/bulk-send
{
  "records": [
    {
      "employee_id": "EMP-001",
      "timestamp": "2025-03-05 08:00:00",
      "type": "check_in"
    }
  ]
}
```

---

## Leaves

| Endpoint | Method | URL | Description | Auth Required |
|---|---|---|---|---|
| List Leaves | GET | `/leaves` | List all leave requests | Yes |
| My Leaves | GET | `/leaves/my-leaves` | List own leave requests | Yes |
| Create Leave | POST | `/leaves` | Submit a leave request | Yes |
| Get Leave | GET | `/leaves/{leave}` | View a specific leave | Yes |
| Update Leave | POST | `/leaves/{leave}` | Update a leave request | Yes |
| Delete Leave | DELETE | `/leaves/{leave}` | Cancel a leave request | Yes |
| Approve Leave | PUT | `/leaves/approvals/{approval:uuid}/approve` | Approve or reject a leave | Yes |
| Download Attachment | GET | `/leaves/download-attachment/{filename}` | Download attached file | No |

### Leave Types

| Endpoint | Method | URL | Description | Auth Required |
|---|---|---|---|---|
| List | GET | `/leave_types` | List all leave types | Yes |
| Create | POST | `/leave_types` | Create a leave type | Yes |
| Update | PUT | `/leave_types/{id}` | Update a leave type | Yes |
| Delete | DELETE | `/leave_types/{id}` | Delete a leave type | Yes |

---

## Early Leave

| Endpoint | Method | URL | Description | Auth Required |
|---|---|---|---|---|
| List Early Leaves | GET | `/early_leaves` | List early leave requests | Yes |
| Create | POST | `/early_leaves` | Submit an early leave request | Yes |
| Get | GET | `/early_leaves/{early_leave}` | View a specific entry | Yes |
| Update | POST | `/early_leaves/{early_leave}` | Update request | Yes |
| Delete | DELETE | `/early_leaves/{early_leave}` | Cancel request | Yes |
| Approve | PUT | `/early_leaves/approvals/{early_leave:uuid}/approve` | Approve / reject | Yes |
| Download Attachment | GET | `/early_leaves/download-attachment/{filename}` | Download file | No |

---

## Attendance Requests

| Endpoint | Method | URL | Description | Auth Required |
|---|---|---|---|---|
| List | GET | `/attendance_request` | List attendance correction requests | Yes |
| Create | POST | `/attendance_request` | Submit a correction request | Yes |
| Get | GET | `/attendance_request/{id}` | View a specific request | Yes |
| Update | PUT | `/attendance_request/{id}` | Update request | Yes |
| Delete | DELETE | `/attendance_request/{id}` | Delete request | Yes |
| Approve | PUT | `/attendance_request/{attendance_request:uuid}/approve` | Approve / reject | Yes |

---

## Overtime

| Endpoint | Method | URL | Description | Auth Required |
|---|---|---|---|---|
| List | GET | `/overtime` | List overtime requests | Yes |
| Create | POST | `/overtime` | Submit an overtime request | Yes |
| Get | GET | `/overtime/{id}` | View a specific request | Yes |
| Update | PUT | `/overtime/{id}` | Update request | Yes |
| Delete | DELETE | `/overtime/{id}` | Delete request | Yes |
| Approve | PUT | `/overtime/{overtime:uuid}/approve` | Approve / reject | Yes |

---

## Payroll

| Endpoint | Method | URL | Description | Auth Required |
|---|---|---|---|---|
| List Payrolls | GET | `/payrolls` | List all payroll records | Yes |
| Create Payroll | POST | `/payrolls` | Generate a new payroll with bulk methode record | Yes |
| Get Payroll | GET | `/payrolls/{payroll}` | View a payroll record | Yes |
| Update Payroll | PUT | `/payrolls/{payroll}` | Update payroll data | Yes |
| Finalize Payroll | PUT | `/payrolls/{payroll:uuid}/finalize` | Lock and finalize payroll | Yes |
| Bulk Finalize | POST | `/payrolls/bulk-finalize` | Generate bulk finalized payroll records | Yes |
| Void Payroll | PUT | `/payrolls/{payroll:uuid}/void` | Void a finalized payroll | Yes |
| Download Payslip | GET | `/payrolls/{payroll:uuid}/download` | Download PDF payslip | Yes |

---

## Approvals

Aggregated approval queue endpoints.

| Endpoint | Method | URL | Description | Auth Required |
|---|---|---|---|---|
| Leave Approvals | GET | `/approvals/leaves` | Pending leave approvals | Yes |
| Early Leave Approvals | GET | `/approvals/early_leaves` | Pending early leave approvals | Yes |
| Attendance Req. Approvals | GET | `/approvals/attendance_request` | Pending attendance corrections | Yes |
| Overtime Approvals | GET | `/approvals/overtime` | Pending overtime approvals | Yes |

---

## Notifications

| Endpoint | Method | URL | Description | Auth Required |
|---|---|---|---|---|
| Get All | GET | `/notifications` | List all notifications | Yes |
| Get Unread | GET | `/notifications/unread` | List unread notifications | Yes |
| Mark as Read | PATCH | `/notifications/{id}/read` | Mark one notification as read | Yes |
| Mark All as Read | PATCH | `/notifications/mark-all-read` | Mark all as read | Yes |
| Delete One | DELETE | `/notifications/{id}` | Delete a notification | Yes |
| Delete All | DELETE | `/notifications/delete-all` | Delete all notifications | Yes |

---

## Settings

| Endpoint | Method | URL | Description | Auth Required |
|---|---|---|---|---|
| Get All Settings | GET | `/settings/get` | Get all settings | Yes |
| Get General Settings | GET | `/settings/get/general` | Get general settings (public) | No |
| Update Attendance Settings | POST | `/settings/attendance` | Update attendance rules | Yes |
| Update Geo-Fencing Settings | POST | `/settings/geo_fencing` | Update geo-fencing config | Yes |
| Update General Settings | POST | `/settings/general` | Update general settings | Yes |

---

## Dashboard

| Endpoint | Method | URL | Description | Auth Required |
|---|---|---|---|---|
| Admin Dashboard | GET | `/dashboard/admin` | Summary stats for admin users | Yes |
| Employee Dashboard | GET | `/dashboard/employee` | Summary stats for employees | Yes |

---

## Holidays

| Endpoint | Method | URL | Description | Auth Required |
|---|---|---|---|---|
| List | GET | `/holidays` | List all public holidays | Yes |
| Create | POST | `/holidays` | Add a holiday | Yes |
| Update | PUT | `/holidays/{id}` | Update a holiday | Yes |
| Delete | DELETE | `/holidays/{id}` | Delete a holiday | Yes |
