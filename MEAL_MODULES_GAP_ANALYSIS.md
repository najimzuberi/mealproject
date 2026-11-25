# MEAL Modules Gap Analysis

**Date:** 2026-11-25

## Existing (reuse)

| Module | Status |
|---|---|
| Indicator Library | API CRUD + submit/approve/retire + versions; nested baselines/targets/disaggregations |
| Monitoring Plans | API CRUD + submit/return/approve/activate/archive + versions; items require approved indicators |
| Reporting calendars/periods | Live — bind collection schedules |
| WorkflowService + approval_histories | Reuse |
| Documents morph | Attach evidence to submissions/DQA |

## Missing

| Area | Gap |
|---|---|
| Indicator return workflow | No returned status / endpoint |
| Indicator/MP Angular UI | coming_soon stubs only |
| Assignments / schedules from active MP | Not generated |
| Submissions / forms / import | No tables/APIs |
| DQA assessments / issues | No tables/APIs |
| Collection/DQA permissions | Missing (nav reuses monitoring_plans.view) |

## Build order
1. Indicator enhancements + Angular  
2. Monitoring Plan Angular + assignment generation on activate  
3. Data Collection schema/API/UI  
4. DQA schema/API/UI  
5. Regression + docs  
