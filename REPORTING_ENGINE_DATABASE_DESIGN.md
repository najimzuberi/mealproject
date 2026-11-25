# Reporting Engine — Database Design

## Tables

### reports
Organization-scoped narrative reports with type, workflow status, period/project links, summaries, AI-assisted flag, frozen `snapshot` JSON.

### report_indicator_results
Point-in-time indicator rows captured at generation (baseline/target/actual/achievement/variance/status).

### reporting_reminders
Scheduled reminder records tied to periods.

## Workflow

`draft` → `under_review` → `returned` | `approved` → `published`
