# MEAL MASTER — Phase 2 Indicator Specification

## Purpose

Each organization owns a **private indicator library**. Indicators are organizational definitions, optionally aligned to strategic objectives, projects, results frameworks, and result levels.

## Identity

- `code` + `version` unique per organization
- `name`, `description`, `definition`
- `indicator_type`: organizational | donor | government | project
- `status`: draft | under_review | approved | superseded | retired

## Measurement

- `measurement_unit`, `numerator`, `denominator`, `formula`
- `aggregation_method`, `direction_of_change` (increase|decrease|maintain)
- Related tables: `indicator_baselines`, `indicator_targets` (`is_cumulative`), `indicator_disaggregations` (sex|age|disability|geography|custom)

## Results alignment

- `strategic_objective_id`, `project_id`, `results_framework_id`, `result_id`, `result_level`

## Data operations metadata

- `data_source`, `collection_method`, `collection_frequency`
- `responsible_collector_id`, `supervisor_id`, `me_validator_id`
- `means_of_verification`, `validation_rules`, `quality_checks`, `limitations`, `assumptions`

## Versioning

Updating an **approved** indicator creates a new draft version and marks the previous version superseded. Historical definitions are preserved via `parent_indicator_id` lineage. API: `GET /indicators/{id}/versions`.

## IndiKit legal boundary

Do **not** scrape or bulk-copy IndiKit catalogues.

Allowed reference fields only:

- `source_url`
- `source_organization`
- `source_accessed_on`
- `adaptation_notes`

Users author their own organizational definitions.