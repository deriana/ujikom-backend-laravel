# Local Development Setup

## System Requirements

| Requirement | Version |
|---|---|
| PHP | >= 8.2 |
| Composer | >= 2.x |
| Node.js | >= 18.x |
| npm | >= 9.x |
| MySQL | >= 8.0 |

---

## Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
cd <project-folder>
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install Node Dependencies

```bash
npm install
```

---

## Environment Setup

### 4. Copy the Environment File

```bash
cp .env.example .env
```

### 5. Generate Application Key

```bash
php artisan key:generate
```

### 6. Configure the `.env` File

Open `.env` and update the following values to match your local environment:

```env
APP_NAME=HRIS
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

MAIL_MAILER=smtp
MAIL_HOST=your_mail_host
MAIL_PORT=587
MAIL_USERNAME=your_mail_user
MAIL_PASSWORD=your_mail_password
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

---

## Database Setup

### 7. Run Migrations

```bash
php artisan migrate
```

### 8. Seed the Database

```bash
php artisan db:seed
```

This will populate the database with default roles, permissions, users, divisions, positions, employees, work schedules, leave types, and sample data.

> See [`database-seeder.md`](./database-seeder.md) for details about default seeded accounts and data.

---

## Running the Application

### 9. Start the Development Server

In separate terminals, run:

```bash
php artisan serve
```

```bash
npm run dev
```

Alternatively, use the built-in Composer script to start all services concurrently:

```bash
composer run dev
```

This starts:
- `php artisan serve` — Laravel API server (default: `http://localhost:8000`)
- `php artisan queue:listen` — Queue worker for background jobs
- `npm run dev` — Vite asset bundler

---

## Queue Worker

This project uses a database-backed queue. Run the worker manually with:

```bash
php artisan queue:listen --tries=1
```

---

## Useful Artisan Commands

| Command | Description |
|---|---|
| `php artisan migrate:fresh --seed` | Re-run all migrations and seeders from scratch |
| `php artisan config:clear` | Clear cached configuration |
| `php artisan cache:clear` | Clear application cache |
| `php artisan route:list` | List all registered routes |
| `php artisan test` | Run the test suite |
