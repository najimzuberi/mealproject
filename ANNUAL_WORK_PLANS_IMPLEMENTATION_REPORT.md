# Annual Work Plans Implementation Report

## Delivered

- AWP linked to approved/active Strategic Plan + planning year.
- Nested annual priorities → activities → activity milestones.
- AWP-level plan milestones (`awp_milestones_plan`).
- Activity enrichment: team, geography, outcome, quarter, funding, expected output.
- `activity_objectives` many-to-many.
- Budget lines with `bcmath` totals; activity `budget_estimate` recalculated from lines.
- Workflow: submit (requires priorities), return, approve, activate, archive.
- Endpoints: budget-lines sync, budget-summary.
- Angular AWP list + detail with workflow actions.

## Tests

`tests/Feature/Api/AnnualWorkPlanTest.php` — create nested AWP, submit, approve, org isolation, return/activate + budget line totals (`1500.00`).

## Financial rules

- Amounts stored as `decimal(18,2)`.
- Summation via `bcadd`.
- Currency mismatch against AWP currency rejected when AWP currency is set.
- Manual `total_budget` is overwritten by budget-line sync.
