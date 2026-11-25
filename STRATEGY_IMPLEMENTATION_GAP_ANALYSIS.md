# Strategy Implementation Gap Analysis

## Existing (reuse — do not recreate)

### Tables
- `strategic_plans`, `strategic_themes`, `strategic_goals`, `strategic_objectives`, `key_result_areas`
- Pivots: `strategic_goal_department`, `strategic_objective_department`, `strategic_objective_user`
- `annual_work_plans`, `awp_priorities`, `awp_activities`, `awp_milestones`
- `approval_histories` + `WorkflowService`
- Org structure: organizations, departments, users, teams, geographic_units
- Indicator library (`indicators`) with optional `strategic_objective_id`

### APIs (`/api/v1`)
- Strategic plans: CRUD + submit/approve/activate/archive (nested themes→goals→objectives→KRAs)
- AWPs: CRUD + submit/approve (nested priorities→activities→milestones)
- Permissions: `strategic_plans.*`, `annual_work_plans.*` (including unused `.review`)

### Frontend
- No strategy feature pages; nav items `coming_soon`
- Org management pages provide the UX pattern to mirror

## Missing (additive)

| Capability | Gap |
|---|---|
| Plan owner / responsible department / context / core values / priorities entity | Columns/tables incomplete vs product |
| Supporting documents | No polymorphic documents |
| Review/return workflow | Enum/action unused for SP & AWP |
| Duplicate plan | Missing |
| Approval history GET | Missing |
| Strategic outcomes | No first-class table |
| High-level strategic indicators | No plan-scoped entity |
| Standalone goals/objectives/KRA CRUD | Nested sync only |
| Hierarchy endpoint | Missing dedicated tree API |
| AWP activate/archive/return/version | Incomplete |
| Activity budgets (lines) | Only `budget_estimate` |
| AWP-level milestones | Currently activity-scoped only |
| Angular Strategy UI | Entirely missing |

## Status (2026-11-25)

Additive schema, workflow (return/activate), documents, outcomes/HLIs, budget lines, goals APIs, Angular strategy pages, and documentation are implemented. Full suite: **92 PHPUnit passed**; Angular production build succeeded.

## Implementation plan (executed)

1. Additive migration for plan enrichment, priorities, documents, outcomes, HLIs, budget lines, AWP milestone lift where needed.
2. Extend WorkflowService usage with `return` for SP/AWP; wire `.review` permission.
3. Expand services/controllers/resources; keep nested create for BC.
4. Build Angular `features/strategy/*` pages; flip nav to live.
5. Module-gated tests + documentation.

## Conflicts / risks

- Do not break nested StrategicPlan/AWP create payloads used by existing tests.
- Versioning currently changes plan `code`; keep BC unless uniqueness altered carefully.
- Themes remain as internal grouping; product “Goals” map to `strategic_goals`.
