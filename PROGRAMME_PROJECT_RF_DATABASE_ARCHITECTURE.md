# Programme / Project / RF Database Architecture

## Additive migration
`2026_11_25_102057_enhance_programme_project_rf_schema.php`

## ERD (logical)

```
organizations
  └── programmes ──┬── programme_user (users)
                   ├── programme_geographic_unit
                   ├── documents (morph)
                   ├── stakeholders (morph)
                   └── projects ──┬── project_user
                                  ├── project_geographic_unit
                                  ├── project_activities → project_milestones
                                  ├── project_assumptions / project_risks
                                  ├── documents / stakeholders (morph)
                                  └── results_frameworks ── results (tree)
                                                           ├── result_assumptions / result_risks
                                                           └── indicators.result_id
```

## Programme enrichments
objectives, strategic_alignment, sector_id, department_id, programme_manager_id, strategic_plan_id, starts_on/ends_on, budget_envelope, currency, donors, partners, target_groups, thematic_area, created_by

## Project enrichments
background, problem_statement, target_groups, currency, submitted_at, approved_at, approved_by + HasApprovalHistory

## New
- `programme_user`, `programme_geographic_unit`
- `stakeholders` (morph)
- RF: submitted_at, approved_at, approved_by, created_by

## Status enums
- **ProjectStatus:** draft, under_review, returned, approved, active, on_hold, completed, archived
- **RF WorkflowStatus:** draft, under_review, returned, approved, active, superseded, archived
