# MEAL MASTER – Phase 1 RBAC Matrix

Permission format: `{module}.{action}`  
Actions: `view`, `create`, `edit`, `submit`, `review`, `approve`, `publish`, `manage`

Modules seeded in Phase 1: `organization`, `users`, `roles`, `departments`, `offices`, `facilities`, `geography`, `sectors`, `reporting_calendars`, `audit`, `settings`

## Primary roles (per organization)

| Role key | Display name |
| --- | --- |
| `system_administrator` | System Administrator |
| `project_manager` | Project Manager |
| `me_officer` | M&E Officer |
| `field_officer` | Field Officer |
| `verification_supervisor` | Field/Data Verification Supervisor |
| `donor_officer` | Donor Officer |

## Default Phase 1 grants

| Capability | Admin | Project Manager | M&E Officer | Field Officer | Supervisor | Donor |
| --- | --- | --- | --- | --- | --- | --- |
| Organization | view+manage | view | view | — | view* | view |
| Users / roles | manage | — | — | — | — | — |
| Departments/offices/facilities/geography/sectors | view+manage | view | view | limited view | view | — |
| Reporting calendars | view+manage | view | view+manage+approve | view | view+review | view |
| Settings | manage | — | — | — | — | — |
| Audit | view+manage | — | — | — | — | — |

\* Supervisor receives structure view permissions for verification context.

## Principles (from product concept)

- Permissions are organization-scoped (Spatie teams).
- Role assignment does **not** imply future project/location access (reserved for later phases).
- System roles (`is_system=true`) cannot be deleted or have permissions rewritten via API.
- Backend policies enforce authorization; UI hiding is never sufficient.

Source: Product Concept Appendix A (Phase 1 subset).
