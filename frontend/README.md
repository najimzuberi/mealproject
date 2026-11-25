# MEAL MASTER Angular Frontend

Angular 22 + Angular Material application shell and dashboard integrated with the Laravel Sanctum API.

## Run locally

Terminal 1 (Laravel API):

```bash
php artisan serve
```

Terminal 2 (Angular):

```bash
cd frontend
npm start
```

Open http://localhost:4200

Dev credentials (after `php artisan db:seed --class=DevAdminSeeder`):

- Email: `admin@mealmaster.local`
- Password: `ChangeMeNow!123`

The Blade login at http://localhost:8000/login remains available and unchanged.
