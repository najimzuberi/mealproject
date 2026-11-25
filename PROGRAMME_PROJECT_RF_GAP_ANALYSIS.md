# Programme / Project / Results Framework — Gap Analysis

**Date:** 2026-11-25  
**Verdict:** Phase 2 backend APIs exist under `app/Modules/Project` and `app/Modules/ResultsFramework`. Angular UIs are `coming_soon`. Build additive schema + workflow gaps + Angular features; do not recreate tables.

## Existing (reuse)

| Area | Assets |
|---|---|
| Tables | `programmes`, `projects`, `project_user`, `project_geographic_unit`, `project_activities`, `project_milestones`, `project_assumptions`, `project_risks`, `results_frameworks`, `results`, `result_assumptions`, `result_risks`, `indicators`, morph `documents` |
| APIs | Programme CRUD; Project CRUD + activate/archive + team/geo sync; RF CRUD (no destroy) + hierarchy + results + submit/approve/activate + link-indicator |
| Workflow | `WorkflowService` on RF; Project uses activate/archive only |
| Permissions | `projects.*`, `results_frameworks.*` (ProgrammePolicy piggybacks `projects.*`) |
| Links | Project → programme, strategic_plan, strategic_objective, AWP, sector, department, manager |

## Gaps (additive)

| Gap | Plan |
|---|---|
| Thin programmes schema | Enrich: dates, manager, dept, sector, budget envelope, currency, donors, target groups, TOC text, status enum |
| Programme docs / geo / teams | Morph documents; programme_geographic_unit; programme_user |
| Stakeholders | New `stakeholders` morph or project/programme scoped table |
| Project submit/approve | Align with RBAC: draft→under_review→approved→active (+ return) |
| Project delete policy | Add `delete` to ProjectPolicy |
| Project currency | Add currency column |
| RF return/archive/versions | Mirror Strategic Plan pattern |
| Result delete | Soft-delete endpoint |
| Angular UI | Full feature modules + live nav |
| `programmes.*` permissions | Introduce optional set OR keep piggyback — introduce dedicated permissions for clarity |

## Conflicts to avoid

1. Do not replace `indicators.result_id` with a new pivot without migrating Indicator/Monitoring Plan.
2. Reuse morph `documents`, do not create a second documents store.
3. Keep ProjectStatus separate from WorkflowStatus unless explicitly unified.
4. Preserve org isolation via OrganizationContext + policies.
