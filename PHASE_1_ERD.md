# MEAL MASTER – Phase 1 ERD

```mermaid
erDiagram
    ORGANIZATIONS ||--o{ ORGANIZATION_SETTINGS : has
    ORGANIZATIONS ||--o{ ORGANIZATION_USER : membership
    USERS ||--o{ ORGANIZATION_USER : membership
    ORGANIZATIONS ||--o{ ORGANIZATION_SECTORS : selects
    SECTORS ||--o{ ORGANIZATION_SECTORS : selected_by
    ORGANIZATIONS ||--o{ DEPARTMENTS : has
    ORGANIZATIONS ||--o{ OFFICES : has
    DEPARTMENTS ||--o{ OFFICES : optional
    ORGANIZATIONS ||--o{ FACILITIES : has
    OFFICES ||--o{ FACILITIES : optional
    ORGANIZATIONS ||--o{ GEOGRAPHIC_UNITS : has
    GEOGRAPHIC_UNITS ||--o{ GEOGRAPHIC_UNITS : parent_of
    ORGANIZATIONS ||--o{ REPORTING_CALENDARS : has
    REPORTING_CALENDARS ||--o{ REPORTING_PERIODS : contains
    ORGANIZATIONS ||--o{ ROLES : scopes
    ROLES ||--o{ ROLE_HAS_PERMISSIONS : grants
    PERMISSIONS ||--o{ ROLE_HAS_PERMISSIONS : granted_by
    USERS ||--o{ MODEL_HAS_ROLES : assigned
    ROLES ||--o{ MODEL_HAS_ROLES : assigned_to
    ORGANIZATIONS ||--o{ AUDIT_LOGS : records
    USERS ||--o{ AUDIT_LOGS : actor
    USERS ||--o{ LOGIN_LOGS : events

    ORGANIZATIONS {
        bigint id PK
        ulid ulid UK
        string code UK
        string type
        string status
    }
    USERS {
        bigint id PK
        ulid ulid UK
        string email UK
        string status
    }
    GEOGRAPHIC_UNITS {
        bigint id PK
        bigint organization_id FK
        bigint parent_id FK
        int level
        string type
    }
    ROLES {
        bigint id PK
        bigint organization_id FK
        string name
        bool is_system
    }
    REPORTING_PERIODS {
        bigint id PK
        bigint reporting_calendar_id FK
        date starts_on
        date ends_on
        string status
    }
```

## Relationship rules

1. `geographic_units.parent_id` must reference the same `organization_id`.
2. Spatie `model_has_roles.organization_id` binds role assignment to a tenant.
3. `reporting_periods.organization_id` denormalizes calendar ownership for isolation queries.
4. Audit logs may have null organization for platform-level events.
