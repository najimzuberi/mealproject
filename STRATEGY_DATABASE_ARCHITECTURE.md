# Strategy Database Architecture

Additive schema delivered by `2026_11_25_085619_enhance_strategy_management_schema.php` on top of Phase 2 tables (`000019`–`000026`).

## Core tables (existing + enriched)

| Table | Notes |
|---|---|
| `strategic_plans` | Added `strategic_context`, `core_values` (JSON), `owner_user_id`, `department_id`, `submitted_at` |
| `strategic_themes` | Unchanged |
| `strategic_goals` | Added `category`, `priority`, `owner_user_id`, `start_date`, `end_date`, `status` |
| `strategic_objectives` | Added `priority`, `start_date`, `end_date`, `status` |
| `key_result_areas` | Added `department_id`, `priority`, `status` |
| `annual_work_plans` | Added `owner_user_id`, `department_id`, `start_date`, `end_date`, `currency`, `total_budget`, `submitted_at` |
| `awp_priorities` | Added `code`, `department_id`, `priority_level`, `status` |
| `awp_activities` | Added `annual_work_plan_id`, `team_id`, `geographic_unit_id`, `strategic_outcome_id`, `code`, `quarter`, `funding_source`, `expected_output` |
| `awp_milestones` | Activity-scoped milestones (existing) |

## New tables

| Table | Purpose |
|---|---|
| `strategic_plan_priorities` | Plan-level strategic priorities |
| `documents` | Polymorphic attachments (`documentable`) |
| `strategic_outcomes` | Expected outcomes under objectives |
| `strategic_indicators` | High-level strategic indicators (optional `indicator_id` link to library) |
| `activity_objectives` | Many-to-many activity ↔ objective (`activity_obj_unique`) |
| `awp_budget_lines` | Decimal(18,2) budget lines per activity |
| `awp_milestones_plan` | AWP-level quarterly milestones |

## Isolation & integrity

- All org-owned tables carry `organization_id` FK.
- Soft deletes where appropriate.
- Monetary values use `decimal(18,2)`; indicator values use `decimal(18,4)`.
- Totals recalculated with `bcadd` in `AnnualWorkPlanService`.

## Status enum (`StrategicPlanStatus`)

`draft` → `under_review` → `returned` → `draft` / `under_review` → `approved` → `active` → `completed` → `archived`
