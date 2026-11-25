# Performance Regression Test Report

**Date:** 2026-11-25

```
php artisan test --compact \
  tests/Feature/Api/ReportingCalendarTest.php \
  tests/Feature/Api/ReportingComplianceTest.php \
  tests/Feature/Api/PerformanceDashboardTest.php \
  tests/Feature/Api/ReportEngineTest.php \
  tests/Feature/Api/PermissionTest.php
```

**Result:** 14 passed.

## Coverage

- Existing calendar create/overlap/open/close regression
- Compliance + open/close timestamps/notes
- Dashboard published-only actuals + org isolation
- Report generate → publish + PDF/CSV export + return comment
- Permission suite still green

## Notes

- Excel export is CSV (`text/csv`) for Excel without new Composer packages
- PDF is a minimal generated PDF without DomPDF (no dependency change)
