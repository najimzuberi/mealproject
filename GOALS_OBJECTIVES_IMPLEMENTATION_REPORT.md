# Goals & Objectives Implementation Report

## Delivered

- Dedicated APIs:
  - `GET/POST /strategic-plans/{id}/goals`
  - `GET/PUT /goals/{id}`, `DELETE /strategic-plans/{id}/goals/{goal}`
  - `POST /goals/{id}/objectives`, `GET/PUT /objectives/{id}`
- Goals/objectives attributes: codes, priority, timelines, status, ownership.
- KRAs, expected outcomes, high-level indicators (plan-scoped; optional library link).
- Hierarchy endpoint on strategic plans.
- Angular Goals & Objectives page with plan filter and expansion hierarchy.

## Service

`StrategicGoalService` — org-scoped create/update/delete with nested KRA/outcome/indicator creation.

## Tests

`tests/Feature/Api/GoalsObjectivesTest.php` — create goal+objective with KRA/outcomes/indicators, hierarchy, cross-org prevention, goal show.

## Known limitations

- Full Indicator Library measurement/collection is intentionally out of scope; `strategic_indicators.indicator_id` is the future bridge.
- Deep edit of nested KRA/outcome collections after create is primarily via plan nested sync or re-create flows.
