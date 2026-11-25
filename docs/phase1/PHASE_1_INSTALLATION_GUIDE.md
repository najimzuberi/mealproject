# MEAL MASTER – Phase 1 Installation Guide

## Prerequisites

- PHP 8.4+
- Composer 2.x
- MySQL 8+
- Node.js 20+ (for Vite assets)

## Install

```bash
cd /home/najim-zuberi/Mealmaster
composer install
cp .env.example .env   # if needed
php artisan key:generate
```

Configure `.env`:

```env
APP_NAME="Meal Master"
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mealmasterDB
DB_USERNAME=root
DB_PASSWORD=your_password
```

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS mealmasterDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
php artisan serve
```

## Packages installed

- `spatie/laravel-permission` (teams enabled → `organization_id`)
- `laravel/sanctum`
- `laravel/breeze` (Blade auth UI)
- `laravel/boost` (dev)

## Development administrator

| Field | Value |
| --- | --- |
| Email | `admin@mealmaster.local` |
| Password | `ChangeMeNow!123` |
| Organization code | `mealmaster-dev` |

**Change these credentials before any shared/staging/production use.**

## API smoke test

```bash
curl -X POST http://127.0.0.1:8000/api/v1/auth/login \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"email":"admin@mealmaster.local","password":"ChangeMeNow!123"}'
```

Use returned token + `X-Organization-Id` for subsequent calls.

## Tests

```bash
php artisan test --compact
```
