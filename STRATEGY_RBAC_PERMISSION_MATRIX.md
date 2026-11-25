# Strategy RBAC Permission Matrix

Permissions are organization-scoped via Spatie teams (`organization_id`). Policies call `sameOrganization` + permission abilities — not hardcoded role names alone.

## Permissions

### Strategic Plans
`view`, `create`, `edit`, `submit`, `review`, `approve`, `manage`

### Annual Work Plans
`view`, `create`, `edit`, `submit`, `review`, `approve`, `manage`

## Default role grants (OrganizationBootstrapService)

| Role | Strategic Plans | AWP |
|---|---|---|
| system_administrator | full set | full set |
| project_manager | create, edit, submit, manage (+ structure view) | create, edit, submit, manage |
| me_officer | review, approve (+ view) | review, approve |
| field_officer | view (via structure/programme view packs) | view |
| verification_supervisor | view | view |
| donor_officer | view (approved visibility via view permission) | view |

## Policy notes

- Activate/archive allowed for `manage` **or** `approve`.
- Return allowed for `review` **or** `approve`.
- Under-review plans cannot be silently edited; approved/active edits create a new draft version (plans).
