# Performance & Reporting API Documentation

Base: `/api/v1` (Sanctum + organization context)

## Reporting Calendar

| Method | Path | Notes |
|---|---|---|
| GET | `/reporting-calendars/compliance` | Deadlines & completion |
| * | `/reporting-calendars` | Existing CRUD |
| POST | `/reporting-periods/{id}/open` | **Preserved** |
| POST | `/reporting-periods/{id}/close` | Optional `closure_notes` |

## Performance

| Method | Path |
|---|---|
| GET | `/performance/dashboard` |
| GET | `/performance/indicator-results` |

## Reports

| Method | Path |
|---|---|
| GET/POST | `/reports` |
| GET/PUT | `/reports/{id}` |
| POST | `/reports/{id}/submit\|return\|approve\|publish` |
| GET | `/reports/{id}/export/pdf` |
| GET | `/reports/{id}/export/excel` |
