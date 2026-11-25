# Organization Database Schema

## Core tables (Phase 1 + enhancement migration `2026_11_25_080357`)

### organizations
Tenant root. Unique `code`. Soft deletes.

Key columns: `name`, `code`, `registration_number`, `type`, `description`, `email`, `phone`, `website`, `logo_path`, `country`, `headquarters_region`, `physical_address`, `postal_address`, `address`, `timezone`, `default_language`, `default_currency`, `status`.

### organization_settings
EAV preferences: unique `(organization_id, key)`, JSON `value`.

### organization_user
Membership pivot: `status`, `is_default`, `joined_at`.

### departments
Unique `(organization_id, code)`. Hierarchy via `parent_id`. Optional `head_user_id`, `email`, `phone`.

### offices
Optional `department_id`, `parent_id`, `geographic_unit_id`, `manager_user_id`. Contact/location fields. Unique org code (harden migration).

### facility_types
Org-configurable facility type catalogue. Unique `(organization_id, code)`.

### facilities
Optional `office_id`, `facility_type_id`, `geographic_unit_id`. Coordinates `latitude`/`longitude`. Free-text `type` retained for BC.

### geographic_types
Org-configurable geographic level types (`Country`, `Region`, …). `default_level`.

### geographic_units
Generic hierarchy: `parent_id`, computed `level`, string `type`, optional `geographic_type_id`, coordinates. Unique `(organization_id, code)`.

### sectors / organization_sectors
Shared catalogue + org assignment pivot (`is_primary`, `status`).

### teams / team_user
Org teams with optional `department_id`, `office_id`, `leader_user_id`. Members via `team_user`.

### reporting_calendars / reporting_periods
Frequencies + period open/close lifecycle (Phase 1).

### Spatie permission tables
Roles scoped by `organization_id` (teams feature).

## Integrity rules

- All org-owned FKs cascade or null on delete as defined.
- Hierarchy circular parents rejected in services (departments, geography).
- Soft delete preferred for referenced structure entities.
