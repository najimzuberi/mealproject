# MEAL Modules Regression Report

**Date:** 2026-11-25

## Backend tests executed

```
php artisan test --compact \
  tests/Feature/Api/IndicatorTest.php \
  tests/Feature/Api/MonitoringPlanTest.php \
  tests/Feature/Api/SubmissionWorkflowTest.php \
  tests/Feature/Api/DqaTest.php \
  tests/Feature/Api/PermissionTest.php
```

**Result:** all passed (Indicator 5, MonitoringPlan 4, Submission 5, DQA 1, Permission 4).

## Coverage highlights

- Indicator return + resubmit; approved version branching
- MP item requires approved indicator; activation creates assignments
- Submission blocked on non-active plans; full DRAFT→LOCKED path; return requires comment; org isolation
- DQA auto-flags → resolve → complete

## Frontend

`npx ng build --configuration=production` — **passed** (MEAL pages live under `/app/meal/*`).

## Remaining / out of scope

- Offline data collection (not implemented; not claimed complete)
- AI suggestion endpoints (draft-only helpers not shipped in this phase)
