# Performance RBAC Matrix

| Permission | me_officer | project_manager | verification_supervisor | donor_officer | field_officer |
|---|---|---|---|---|---|
| reporting_calendars.view/manage | view+manage | view | view/review | view | view |
| performance.view | ✓ | ✓ | ✓ | ✓ | — |
| reports.view | ✓ | ✓ | ✓ | ✓ | — |
| reports.create/edit/submit | ✓ | ✓ | — | — | — |
| reports.approve/manage | ✓ | — | — | — | — |
| reports.export | ✓ | ✓ | — | ✓ | — |

Gate `viewPerformance` also allows `indicators.view`.
