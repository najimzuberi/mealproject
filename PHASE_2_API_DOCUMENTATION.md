# MEAL MASTER — Phase 2 API Documentation

Base: `/api/v1`  
Auth: `Authorization: Bearer {token}`  
Tenant: `X-Organization-Id: {organization_id}`  
Response envelope: `{ success, message, data, meta, errors }`

## Strategic Plans

| Method | Path | Notes |
|--------|------|-------|
| GET | `/strategic-plans` | List/filter |
| POST | `/strategic-plans` | Nested themes/goals/objectives/kra |
| GET/PUT/DELETE | `/strategic-plans/{id}` | Delete draft only |
| POST | `/strategic-plans/{id}/submit` | draft → under_review |
| POST | `/strategic-plans/{id}/approve` | under_review → approved |
| POST | `/strategic-plans/{id}/activate` | approved → active |
| POST | `/strategic-plans/{id}/archive` | archive |

Updating an approved/active plan creates a new draft version.

## Annual Work Plans

| Method | Path |
|--------|------|
| GET/POST | `/annual-work-plans` |
| GET/PUT | `/annual-work-plans/{id}` |
| POST | `/annual-work-plans/{id}/submit` |
| POST | `/annual-work-plans/{id}/approve` |

Requires approved/active strategic plan; priorities must cite objectives on that plan. Milestones use `Q1`–`Q4`.

## Programmes & Projects

| Method | Path |
|--------|------|
| CRUD | `/programmes` |
| CRUD | `/projects` |
| POST | `/projects/{id}/activate` |
| POST | `/projects/{id}/archive` |
| PUT | `/projects/{id}/team` |
| PUT | `/projects/{id}/geographic-units` |

## Results Frameworks

| Method | Path |
|--------|------|
| CRUD | `/results-frameworks` |
| GET | `/results-frameworks/{id}/hierarchy` | Tree for UI diagrams (`data[]` roots) |
| POST | `/results-frameworks/{id}/results` |
| POST | `/results-frameworks/{id}/submit\|approve\|activate` |
| PUT | `/results/{id}` |
| POST | `/results/{id}/link-indicator` |

## Indicators

| Method | Path |
|--------|------|
| CRUD | `/indicators` |
| POST | `/indicators/{id}/submit\|approve\|retire` |
| GET | `/indicators/{id}/versions` |

Supports baselines, targets, disaggregations, and external source reference fields (no IndiKit catalogue copy).

## Monitoring Plans

| Method | Path |
|--------|------|
| CRUD | `/monitoring-plans` |
| POST | `/monitoring-plans/{id}/submit\|return\|approve\|activate\|archive` |
| GET | `/monitoring-plans/{id}/versions` |

Items require **approved** indicators.