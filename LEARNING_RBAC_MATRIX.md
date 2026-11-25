# Learning RBAC Matrix

| Permission | me_officer | project_manager | donor_officer |
|---|---|---|---|
| evaluations.* | full | view | — |
| feedback.view/create/edit | ✓ | view/create/edit | — |
| feedback.sensitive/manage | ✓ | — | — |
| lessons.* | full | view/create | — |
| knowledge.view/download | ✓ | ✓ | — |
| knowledge.create/edit/manage | ✓ | — | — |

Safeguarding access requires `feedback.sensitive` or `feedback.manage`.
