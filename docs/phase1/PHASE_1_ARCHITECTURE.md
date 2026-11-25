# MEAL MASTER – Phase 1 Architecture

## Purpose

Phase 1 establishes a secure, multi-organization foundation for MEAL MASTER: identity, organization structure, geography, sectors, reporting calendars, RBAC, audit, and a versioned REST API.

## Stack

| Layer | Choice |
| --- | --- |
| Backend | Laravel 13 (PHP 8.4) |
| Database | MySQL (`mealmasterDB`) |
| Auth (web) | Laravel Breeze (Blade) |
| Auth (API) | Laravel Sanctum |
| RBAC | Spatie Laravel Permission (teams = organizations) |
| Architecture | Modular monolith under `app/Modules` |
| Frontend foundation | Blade + Tailwind shared `x-mm.*` components (Material-inspired) |

> **Note on Breezy:** Filament Breezy was requested but is not compatible without Filament. Laravel Breeze is installed for authentication/profile scaffolding. Spatie Permission handles RBAC.

## Module map

```
app/
  Modules/
    Organization/     # orgs, settings, departments, offices, facilities, users
    AccessControl/    # roles, permissions (Spatie teams)
    Geography/        # hierarchical geographic_units
    Sector/           # global sectors + organization_sectors
    ReportingCalendar/# calendars + periods
    Audit/            # audit_logs, login_logs
    Auth/             # API login/logout/me
  Shared/             # ApiResponse, OrganizationContext, traits, route bindings
  Enums/
```

## Multi-tenancy

1. Every business record that belongs to a tenant carries `organization_id`.
2. API requests set context via `X-Organization-Id` (validated membership).
3. `setPermissionsTeamId($organizationId)` scopes Spatie roles/permissions.
4. Route-model binding aborts `404` when a record is outside the active organization.
5. Organization A cannot read/update Organization B data without explicit membership + permission.

## Request flow (API)

`Client → Sanctum auth → SetOrganizationContext → EnsureOrganizationIsActive → Controller → FormRequest → Policy → Service → Model → ApiResponse`

## Future-ready boundaries

Phase 1 intentionally omits Strategic Plans, Projects, Indicators, Monitoring Plans, Field Collection, AI, Dashboards, and Donor Portal. Organization, geography, roles, calendars, and audit are designed so those modules can attach without restructuring tenancy or RBAC.
