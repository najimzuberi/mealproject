# Reporting Calendar — Design

## Preserve

- `POST /api/v1/reporting-periods/{id}/open`
- `POST /api/v1/reporting-periods/{id}/close`
- Existing CRUD, overlap validation, org isolation, Angular org page

## Extensions

- Period metadata: `code`, `submission_deadline`, `opened_at`, `closed_at`, `closure_notes`, `responsible_officer_id`, `reminder_days_before`
- `GET /reporting-calendars/compliance` — open/overdue periods, assignment completion, upcoming deadlines
- `reporting_reminders` table for configurable reminder records

## Rules

Open/close remain manage-authorized; close may accept optional `closure_notes`; audit trail retained.
