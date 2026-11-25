# MEAL Modules API Documentation

Base: `/api/v1` — requires Sanctum + `X-Organization-Id`.

## Indicators

| Method | Path | Notes |
|---|---|---|
| GET/POST | `/indicators` | list/create |
| GET/PUT | `/indicators/{id}` | show/update (approved → new version) |
| POST | `/indicators/{id}/submit` | draft/returned → under_review |
| POST | `/indicators/{id}/return` | comment required |
| POST | `/indicators/{id}/approve` | |
| POST | `/indicators/{id}/retire` | |
| GET | `/indicators/{id}/versions` | |

## Monitoring Plans

| Method | Path | Notes |
|---|---|---|
| GET/POST | `/monitoring-plans` | |
| GET/PUT | `/monitoring-plans/{id}` | |
| POST | `.../submit\|return\|approve\|activate\|archive` | activate generates assignments |
| GET | `.../versions` | |

## Data Collection

| Method | Path |
|---|---|
| GET/POST | `/reporting-assignments` |
| GET | `/reporting-assignments/{id}` |
| GET/POST | `/submissions` |
| POST | `/submissions/import` (multipart CSV: item_id,period_id,value[,notes]) |
| GET/PUT | `/submissions/{id}` |
| POST | `.../submit\|return\|verify\|validate\|approve\|publish\|lock` |

## DQA

| Method | Path |
|---|---|
| GET/POST | `/dqa-assessments` |
| GET | `/dqa-assessments/{id}` |
| POST | `/dqa-assessments/{id}/complete` |
| GET/POST | `/dqa-issues` |
| GET | `/dqa-issues/{id}` |
| POST | `/dqa-issues/{id}/resolve` |
