# Organization RBAC Matrix

Permissions are Spatie permissions scoped by organization (`setPermissionsTeamId`).

## Structure permissions

| Module | view | create | edit | manage |
|---|---|---|---|---|
| organization | ✓ | ✓ | ✓ | ✓ |
| departments | ✓ | ✓ | ✓ | ✓ |
| offices | ✓ | ✓ | ✓ | ✓ |
| facilities | ✓ | ✓ | ✓ | ✓ |
| geography | ✓ | ✓ | ✓ | ✓ |
| sectors | ✓ | — | — | manage |
| teams | ✓ | ✓ | ✓ | ✓ |
| users | ✓ | ✓ | ✓ | ✓ |
| roles | ✓ | ✓ | ✓ | ✓ |
| settings | ✓ | — | — | manage |
| reporting_calendars | ✓ | ✓ | ✓ | ✓ (+ review/approve) |

## Role summaries (org bootstrap)

| Role | Org structure access |
|---|---|
| system_administrator | Full structure + users/roles/settings/teams (no Phase 2 programme approve) |
| project_manager | Structure view + programme create/edit/submit |
| me_officer | Structure view + reporting calendar manage + MEAL artefact authority |
| field_officer | Limited structure view |
| verification_supervisor | Structure view + review permissions |
| donor_officer | Organization + reporting + programme view only |

Facility types use `facilities.view` / `facilities.manage`.  
Geographic types use `geography.view` / `geography.manage`.

Donor officers do not receive broad personal beneficiary data permissions in this module.
