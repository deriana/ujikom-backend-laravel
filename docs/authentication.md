# Authentication

## Authentication Method

This API uses **Laravel Sanctum** for token-based authentication. Upon successful login, the server issues a **bearer token** that must be included in the `Authorization` header of all protected requests.

---

## Login Flow

```
1. Client sends POST /api/auth/login with email + password
2. Server validates credentials
3. Server returns a bearer token
4. Client stores the token and attaches it to subsequent requests
5. On logout, the server revokes the token
```

---

## Endpoints

### Login

**`POST /api/auth/login`**

#### Request

```json
{
  "email": "admin@app.com",
  "password": "password"
}
```

#### Response

```json
{
  "data": {
    "token": "1|abcdefghijklmnopqrstuvwxyz1234567890...",
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

### Logout

**`POST /api/auth/logout`**

> Requires authentication.

Revokes the current bearer token.

#### Response

```json
{
  "message": "Logged out successfully."
}
```

---

### Get Authenticated User

**`GET /api/auth/me`**

> Requires authentication.

Returns the currently authenticated user along with their roles, employee profile, position, division, and manager.

#### Response

```json
{
  "data": {
    "uuid": "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
    "name": "Admin System",
    "email": "admin@app.com",
    "roles": ["admin"],
    "employee": {
      "position": { "name": "System Administrator" },
      "team": {
        "division": { "name": "Technology" }
      },
      "manager": {
        "user": { "name": "Project Manager" }
      }
    }
  }
}
```

---

## Authorization Header

All protected API requests must include the bearer token in the `Authorization` header:

```
Authorization: Bearer <your-token-here>
```

Example:

```bash
curl -X GET http://localhost:8000/api/auth/me \
  -H "Accept: application/json" \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz..."
```

---

## Account Activation Flow

New users created by an Admin must activate their account via a token-based verification flow:

| Step | Endpoint | Description |
|---|---|---|
| 1 | `GET /api/auth/check-token?token=xxx` | Validate the activation token |
| 2 | `POST /api/auth/finalize-activation` | Set password and activate account |

---

## Password Reset Flow

| Step | Endpoint | Description |
|---|---|---|
| 1 | `POST /api/auth/forgot-password` | Send password reset link via email |
| 2 | `GET /api/auth/reset-password/check?token=xxx` | Validate the reset token |
| 3 | `POST /api/auth/reset-password` | Submit new password |

---

## Roles & Permissions

The system uses **Spatie Laravel Permission** for RBAC. Every role has a predefined set of permissions per module.

### Available Roles

| Role | Description |
|---|---|
| `admin` | Full system administrator |
| `owner` | Company owner — read access across all modules |
| `director` | Top operational leadership — view and approve |
| `hr` | Human resources — manage employees, leaves, schedules |
| `finance` | Finance team — manage payroll and allowances |
| `manager` | Division/team manager — manage their team's data and approvals |
| `employee` | Regular staff — view own data and submit requests |

### Permission Format

Permissions follow the `module.action` naming convention:

```
user.index, user.create, user.edit
leave.create, leave.approve
payroll.pay, payroll.export
```

> The **Admin** role always has all permissions synced automatically by the seeder.
