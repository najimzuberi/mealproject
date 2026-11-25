# Performance Dashboard — Design

## Data truth

Official metrics use **published/locked** submissions only. Draft/submitted values never enter official achievement figures.

## Aggregation

`PerformanceAggregationService` joins:

- Active projects
- Monitoring plan items (baseline/target)
- Published submissions (actual)
- Reporting periods (trends)

Performance bands: ≥90% on_track, ≥70% at_risk, else off_track (inverted for decrease-direction indicators).

## API

- `GET /performance/dashboard`
- `GET /performance/indicator-results`

Filters: programme, project, period, indicator (+ reserved geo/org structure params).
