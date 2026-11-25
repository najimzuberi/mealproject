# Organization API Documentation

Base: `/api/v1`  
Auth: `Authorization: Bearer {token}`  
Tenant: `X-Organization-Id: {organization_id}` (required for org-scoped routes)

Envelope: `{ success, message, data, meta, errors }`  
Paginated lists: `data.items` + `data.pagination`

## Profile & settings

| Method | Path | Permission |
|---|---|---|
| GET | `/organizations` | membership |
| GET | `/organizations/{id}` | `organization.view` |
| PUT | `/organizations/{id}` | `organization.edit` |
| POST | `/organizations/{id}/logo` | `organization.edit` |
| PUT | `/organizations/{id}/sectors` | `organization.edit` |
| GET | `/organization/settings` | `organization.view` |
| PUT | `/organization/settings` | `organization.manage` |

## Structure CRUD

| Resource | Path | Permissions |
|---|---|---|
| Departments | `/departments` | `departments.*` |
| Offices | `/offices` | `offices.*` |
| Facilities | `/facilities` | `facilities.*` |
| Facility types | `/facility-types` | `facilities.view` / `facilities.manage` |
| Geographic units | `/geographic-units` | `geography.*` |
| Geographic types | `/geographic-types` | `geography.view` / `geography.manage` |
| Teams | `/teams` | `teams.*` |
| Team members | `PUT /teams/{id}/members` | `teams.edit` |

Query filters (where implemented): `search`, `status`, `page`.

## Users & RBAC

| Method | Path |
|---|---|
| CRUD | `/users` |
| POST | `/users/{id}/activate\|deactivate\|reset-password` |
| PUT | `/users/{id}/roles` |
| GET | `/roles`, `/permissions` |

## Sectors

| Method | Path |
|---|---|
| GET | `/sectors` (catalogue) |
| PUT | `/organizations/{id}/sectors` |

## Reporting calendars

| Method | Path |
|---|---|
| index/store/show/update | `/reporting-calendars` |
| GET/POST periods | `/reporting-calendars/{id}/periods` |
| POST open/close | `/reporting-periods/{id}/open\|close` |

## Notes

- Organization code remains unique globally.
- Hierarchy circular parents return **422**.
- Cross-org route bindings return **404**.
- `logo_url` is derived from `logo_path` on public disk.
