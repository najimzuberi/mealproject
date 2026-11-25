# MEAL MASTER — Phase 2 Monitoring Plan Engine

## Role in the architecture

The Monitoring Plan Engine is the central differentiator. An approved Monitoring Plan is structured metadata that Phase 3 uses to drive:

- Reporting schedules
- Data collection assignments
- Collection forms
- Validation requirements
- Dashboard measures
- Reporting structures

Phase 2 does **not** execute field submission or verification queues.

## Plan questions encoded as metadata

1. What — `indicator_id` (+ baseline/target overrides)
2. Why — `intended_use`
3. How — `collection_method`, `data_source`, `validation_rules`
4. When — `collection_frequency`, `collection_deadline`
5. Who collects — `responsible_collector_id`
6. Who verifies — `supervisor_id`
7. Who validates — `me_validator_id`
8. Evidence — `means_of_verification`
9. Reporting format — `reporting_format`
10. Information use — plan/item `intended_use`

## Lifecycle

`draft` → `under_review` → `returned` (comment required) → resubmit → `approved` → `active` → `superseded` / `archived`

Approved/active plans are versioned on change; previous versions become superseded.

## Constraints

- Belongs to a project in the same organization
- Items must reference **approved** indicators
- Organization isolation enforced via bindings + policies

## Phase 3 readiness

Approved Monitoring Plan Items expose stable configuration keys for scheduling, form generation, assignment routing, and measure binding without redesigning Phase 2 tables.