# Organization Module Architecture

## Overview

Organization Management is the multi-tenant configuration foundation for MEAL MASTER. It reuses Phase 1 Laravel APIs and Spatie RBAC (organization-scoped teams) and adds a full Angular Material management UI under `/app/organization/*`.

## Tenancy model

- Every organization-owned row carries `organization_id`.
- API requests require Sanctum Bearer token + `X-Organization-Id`.
- Middleware `organization.context` / `organization.active` resolve and enforce membership.
- Policies authorize module permissions (`departments.view`, etc.) within the active organization.
- Cross-organization IDs return 404/403 — never leak foreign tenants.

## Domain modules

| Domain | Backend module | Primary tables |
|---|---|---|
| Profile & settings | `Organization` | `organizations`, `organization_settings` |
| Departments | `Organization` | `departments` (+ parent hierarchy) |
| Offices | `Organization` | `offices` |
| Facilities | `Organization` | `facilities`, `facility_types` |
| Geography | `Geography` | `geographic_units`, `geographic_types` |
| Sectors | `Sector` | `sectors`, `organization_sectors` |
| Users & teams | `Organization` + `AccessControl` | `users`, `organization_user`, `teams`, `team_user`, Spatie roles |
| Reporting calendars | `ReportingCalendar` | `reporting_calendars`, `reporting_periods` |

## Frontend structure

```
features/organization/
  organization-profile/
  departments/
  offices/
  facilities/
  geography/
  sectors/
  users-teams/
  reporting-calendars/
  organization-settings/
core/services/*organization*.ts
core/models/organization.model.ts
```

Routes live under authenticated `app` shell with `authGuard`. Nav items use `availability: 'live'` and Spatie permission keys.

## Design principles

1. Organization-private-by-default.
2. Reuse Phase 1 endpoints; extend backward-compatibly.
3. Hierarchical geography is generic (no fixed region/district tables).
4. Shared sector catalogue + org assignment pivot.
5. Soft-deactivate preferred over hard delete when references exist.
6. Audit significant mutations via Phase 1 `AuditService`.

## Out of scope (this module)

- Strategic planning / results frameworks / field data collection (Phase 2–3).
- Global platform admin beyond existing `platform/organizations`.
- Redesigning sidebar chrome or login.
