# MEAL MASTER — Phase 2 Database Schema

Migrations: `2026_11_25_000019` … `000026` (after Phase 1 `000018`).

## Shared

### approval_histories
Polymorphic workflow history: `organization_id`, `approvable_type/id`, `action`, `from_status`, `to_status`, `user_id`, `comment`, `created_at`.

## Strategic planning

- `strategic_plans` — ulid, code, title, dates, status, version, parent_plan_id, approved meta  
  Unique: `(organization_id, code, version)`
- `strategic_themes`, `strategic_goals`, `strategic_objectives`, `key_result_areas`
- Pivots: `strategic_goal_department`, `strategic_objective_department`, `strategic_objective_user`

## Annual work plans

- `annual_work_plans` — links `strategic_plan_id`, planning_year, status, version  
  Unique: `(organization_id, code, version)`
- `awp_priorities` — requires `strategic_objective_id`
- `awp_activities`, `awp_milestones` (quarter Q1–Q4)

## Programmes & projects

- `programmes`
- `projects` — links strategic plan/objective, AWP, sector, department, manager, donor/budget, dates/status
- `project_user`, `project_geographic_unit`
- `project_activities`, `project_milestones`, `project_assumptions`, `project_risks`

## Results frameworks

- `results_frameworks` — project_id, theory_of_change, status, version
- `results` — parent_id, level (impact|outcome|output|process|activity)
- `result_assumptions`, `result_risks`

## Indicators

- `indicators` — full measurement + IndiKit-style external reference fields only  
  Unique: `(organization_id, code, version)`
- `indicator_baselines`, `indicator_targets`, `indicator_disaggregations`

## Monitoring plans

- `monitoring_plans` — project_id, status lifecycle including returned/superseded  
  Unique: `(organization_id, code, version)`
- `monitoring_plan_items` — one row per indicator with collection/verification metadata  
  Unique: `(monitoring_plan_id, indicator_id)`

## Reused Phase 1 tables

`organizations`, `users`, `departments`, `offices`, `facilities`, `geographic_units`, `sectors`, Spatie permission tables.