# MEAL MASTER – Phase 1 API Documentation

Base path: `/api/v1`  
Auth: `Authorization: Bearer {sanctum_token}`  
Tenant header (required for org-scoped routes): `X-Organization-Id: {id}`

## Response envelope

```json
{
  "success": true,
  "message": "optional",
  "data": {},
  "meta": null,
  "errors": null
}
```

Unauthorized: `401` / `403`. Cross-tenant resource binding: `404`. Validation failures: `422` (Laravel validation JSON).

## Auth

| Method | Path | Auth | Notes |
| --- | --- | --- | --- |
| POST | `/auth/login` | none | throttle 5/min; body: `email`, `password`, optional `device_name`, `organization_id` |
| POST | `/auth/logout` | Sanctum | revokes current token; writes login log |
| GET | `/auth/me` | Sanctum | current user + organizations |

## Organizations & sectors

| Method | Path | Auth / Org | Permission |
| --- | --- | --- | --- |
| GET | `/organizations` | Sanctum | caller memberships |
| POST | `/organizations` | Sanctum | register; body includes `name`, `code`, `type`; bootstraps 6 roles |
| GET | `/platform/organizations` | Sanctum | system administrator in any org |
| GET | `/organizations/{id}` | Sanctum + org | `organization.view` |
| PUT | `/organizations/{id}` | Sanctum + org | `organization.edit` or `organization.manage` |
| POST | `/organizations/{id}/activate` | Sanctum + org context | `organization.manage` |
| POST | `/organizations/{id}/deactivate` | Sanctum + org context | `organization.manage` |
| POST | `/organizations/{id}/logo` | Sanctum + org | multipart `logo` image max 2MB |
| PUT | `/organizations/{id}/sectors` | Sanctum + org | `sector_ids[]` |
| PUT | `/organization/settings` | Sanctum + org | `settings.manage`; body `settings[{key,value}]` |
| GET | `/sectors` | Sanctum | global sector catalog |

## Users

| Method | Path | Permission |
| --- | --- | --- |
| GET `/users` | list org members | `users.view` |
| POST `/users` | create + attach | `users.create` / manage; `name`, `email`, `password` |
| GET/PUT/DELETE `/users/{id}` | membership-scoped | view / edit / manage |
| POST `/users/{id}/activate` | | `users.manage` |
| POST `/users/{id}/deactivate` | | `users.manage` |
| POST `/users/{id}/reset-password` | `password`, `password_confirmation` | `users.manage` |
| PUT `/users/{id}/roles` | `role_ids[]` | `users.manage` |

## Roles & permissions

| Method | Path | Permission |
| --- | --- | --- |
| GET `/permissions` | permission catalog | `roles.view` |
| GET/POST `/roles` | list / create org role | view / create |
| GET/DELETE `/roles/{id}` | system roles protected from delete | |
| PUT `/roles/{id}/permissions` | sync names; system roles blocked | `roles.manage` |

## Structure & geography

CRUD under `/departments`, `/offices`, `/facilities`, `/geographic-units`.

Permissions: `{module}.view|create|edit|manage`.  
Geography `parent_id` must belong to the same organization (`422` if not).

## Reporting calendars

| Method | Path | Permission |
| --- | --- | --- |
| GET/POST | `/reporting-calendars` | view / create |
| GET/PUT | `/reporting-calendars/{id}` | view / edit |
| GET/POST | `/reporting-calendars/{id}/periods` | view / manage |
| POST | `/reporting-periods/{id}/open` | manage |
| POST | `/reporting-periods/{id}/close` | manage |

Period rules:
- `ends_on >= starts_on`
- duplicate `name` within a calendar rejected
- overlaps rejected unless `allow_overlapping_periods`
- statuses: `draft`, `open`, `closed`, `locked`
- open/close audited

## Audit

| Method | Path | Permission |
| --- | --- | --- |
| GET `/audit-logs` | filters: `module`, `action` | `audit.view` |
| GET `/audit-logs/{id}` | org-scoped binding | `audit.view` |
| GET `/login-logs` | | `audit.view` |
