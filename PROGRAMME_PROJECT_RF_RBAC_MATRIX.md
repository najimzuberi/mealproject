# Programme / Project / RF RBAC Matrix

| Permission | Used by |
|---|---|
| `projects.view|create|edit|submit|approve|manage` | Projects + Programmes (ProgrammePolicy piggybacks projects.*) |
| `results_frameworks.view|create|edit|submit|review|approve|manage` | RF |

Default roles (bootstrap): Project Manager create/edit/submit/manage projects; ME Officer approve + full RF; viewers via view packs.
