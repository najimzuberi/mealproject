# Strategy API Documentation

Base: `/api/v1`  
Auth: Sanctum Bearer + `X-Organization-Id`

## Strategic Plans

| Method | Path | Permission |
|---|---|---|
| GET | `/strategic-plans` | `strategic_plans.view` |
| POST | `/strategic-plans` | `strategic_plans.create` |
| GET | `/strategic-plans/{id}` | `strategic_plans.view` |
| PUT | `/strategic-plans/{id}` | `strategic_plans.edit` |
| DELETE | `/strategic-plans/{id}` | `strategic_plans.manage` |
| POST | `/strategic-plans/{id}/submit` | `strategic_plans.submit` |
| POST | `/strategic-plans/{id}/return` | `strategic_plans.review` |
| POST | `/strategic-plans/{id}/approve` | `strategic_plans.approve` |
| POST | `/strategic-plans/{id}/activate` | manage/approve |
| POST | `/strategic-plans/{id}/archive` | manage/approve |
| POST | `/strategic-plans/{id}/duplicate` | create+view |
| GET | `/strategic-plans/{id}/versions` | view |
| GET | `/strategic-plans/{id}/hierarchy` | view |
| POST | `/strategic-plans/{id}/documents` | edit |
| DELETE | `/strategic-plans/{id}/documents/{document}` | edit |

## Goals & Objectives

| Method | Path |
|---|---|
| GET/POST | `/strategic-plans/{id}/goals` |
| DELETE | `/strategic-plans/{id}/goals/{goal}` |
| GET/PUT | `/goals/{goal}` |
| POST | `/goals/{goal}/objectives` |
| GET/PUT | `/objectives/{objective}` |

## Annual Work Plans

| Method | Path |
|---|---|
| GET/POST | `/annual-work-plans` |
| GET/PUT/DELETE | `/annual-work-plans/{id}` |
| POST | `/annual-work-plans/{id}/submit` |
| POST | `/annual-work-plans/{id}/return` |
| POST | `/annual-work-plans/{id}/approve` |
| POST | `/annual-work-plans/{id}/activate` |
| POST | `/annual-work-plans/{id}/archive` |
| PUT | `/annual-work-plans/{id}/budget-lines` |
| GET | `/annual-work-plans/{id}/budget-summary` |

Responses use `ApiResponse` (`success`, `message`, `data`, `meta`, `errors`).
