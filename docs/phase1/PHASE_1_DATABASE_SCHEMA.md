# MEAL MASTER – Phase 1 Database Schema

## Domains and tables

### Organization
- `organizations` — tenant root (ulid, code unique, type, status, profile fields, soft deletes)
- `organization_settings` — key/value JSON settings per organization
- `organization_user` — membership (status, is_default, joined_at)
- `organization_sectors` — selected sectors
- `departments`, `offices`, `facilities` — structure (org-scoped, soft deletes)

### Geography
- `geographic_units` — hierarchical (`parent_id`, `level`, `type`), org-scoped

### Sector
- `sectors` — platform catalog (Education, Health, Agriculture, WASH, Other)

### Identity
- `users` — ulid, phone, status, last_login_at, soft deletes
- Sanctum `personal_access_tokens`

### Access control (Spatie teams)
- `roles` — org-scoped via `organization_id` (+ `display_name`, `is_system`)
- `permissions`
- `role_has_permissions`
- `model_has_roles` — **acts as user_organization_roles**
- `model_has_permissions`

### Reporting
- `reporting_calendars` — frequency, allow_overlapping_periods
- `reporting_periods` — starts_on/ends_on, status

### Audit
- `audit_logs` — immutable (`created_at` only); no secrets
- `login_logs` — login_success / login_failed / logout

## Isolation strategy

- Tenant FK on all org-owned tables
- Unique codes scoped per organization where applicable
- Indexes on `(organization_id, …)` for list/filter queries
- Soft deletes for recoverable master data; audit/login logs are append-only

## Normalization notes

- Settings are EAV/JSON for flexible org config without schema churn
- Sectors are normalized globally; org selection is a pivot
- Roles are not global: each organization receives seeded system roles
