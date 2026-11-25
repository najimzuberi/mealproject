# Performance & Reporting — Gap Analysis

**Date:** 2026-11-25

## Preserve
- `POST /api/v1/reporting-periods/{id}/open` and `/close`
- Full ReportingCalendar API/UI/tests
- Overview `GET /dashboard/overview`
- Chart.js / ng2-charts
- Published/approved submissions as official performance source

## Gaps
| Module | Gap |
|---|---|
| Reporting Calendar | Compliance/overdue APIs; reminders; Performance-nav dedicated screens beyond org page |
| Performance Dashboard | Aggregation API + Angular page (nav `coming_soon`) |
| Reports | Entire module: generate, workflow, PDF/Excel, permissions |

## Build order
1. Calendar compliance + UI polish (preserve open/close)
2. Performance aggregation service + dashboard UI
3. Reports engine + exports
4. Regression + docs
