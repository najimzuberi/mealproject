# RBAC Permission Matrix — MEAL Modules

| Permission | me_officer | field_officer | verification_supervisor | donor_officer |
|---|---|---|---|---|
| indicators.* | full | — | view | — |
| monitoring_plans.* | full | — | view/review | — |
| submissions.view | ✓ | ✓ | ✓ | ✓ |
| submissions.create/edit/submit | ✓ | ✓ | — | — |
| submissions.verify | ✓ | — | ✓ | — |
| submissions.validate/approve/manage | ✓ | — | — | — |
| dqa.view | ✓ | — | ✓ | ✓ |
| dqa.create/edit | ✓ | — | ✓ | — |
| dqa.manage | ✓ | — | — | — |

Administrators receive `Permissions::all()`.
