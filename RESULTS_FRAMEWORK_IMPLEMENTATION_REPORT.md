# Results Framework Implementation Report

## Delivered
- Hierarchy: impact → outcome → output → activity (plus process).
- Assumptions/risks on result nodes; indicator linkage via existing Indicator library FK.
- Workflow: submit, return, approve, activate, archive; new draft version (`parent_framework_id`).
- Result soft-delete with child cascade.
- Angular: list + detail hierarchy/workflow; live nav `/app/programme/results-frameworks`.

## Tests
`tests/Feature/Api/ResultsFrameworkTest.php` + CrossModulePhase2 — create/list, hierarchy/link, approve, return/version.
