# Dashboard API Integration

## Authentication

| Angular action | Endpoint | Notes |
|----------------|----------|-------|
| Login | `POST /api/v1/auth/login` | Stores Sanctum bearer token |
| Session | `GET /api/v1/auth/me` | Sends `Authorization` + optional `X-Organization-Id` |
| Logout | `POST /api/v1/auth/logout` | Deletes current token |

Organization switching updates `X-Organization-Id` client-side and reloads `/auth/me`. Membership is verified server-side before roles/permissions are returned.

## Dashboard aggregate

`GET /api/v1/dashboard/overview` (requires `auth:sanctum` + organization context)

Returns:

- KPI counts from projects, strategic plans, indicators, monitoring plans, pending under-review artefacts
- Project status distribution
- Reporting periods (if any)
- Pending approval tasks (when user can approve)
- Recent audit activities (when user can `audit.view`)
- Explicit unavailable payloads for Phase 3 performance widgets

## Supporting service endpoints (ready for feature pages)

- `/projects`, `/indicators`, `/monitoring-plans`, `/reporting-calendars`, `/organizations`, `/users`

## Empty / unavailable policy

No fabricated production statistics. Missing Phase 3 measures render `EmptyState` / unavailable messaging from API flags.
