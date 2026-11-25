# Data Collection — Design

## ERD (logical)

```
monitoring_plans (active)
  └── monitoring_plan_items
        └── reporting_assignments ── reporting_periods
              └── submissions
                    ├── submission_values
                    └── import_jobs (bulk)
```

## Workflow

`draft` → `submitted` → `returned` | `verified` → `validated` → `approved` → `published` → `locked`

## Rules

- Submissions only against **active** monitoring plans
- Quality flags (duplicate, outlier, formula, late) annotate for review — never auto-reject
- Locked rows are not editable via update API
