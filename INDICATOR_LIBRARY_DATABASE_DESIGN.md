# Indicator Library — Database Design

## Entities (existing, reused)

- `indicators` — organization-scoped definitions with version, status, formula, sources, ownership
- `indicator_baselines`, `indicator_targets`, `indicator_disaggregations` — nested measurement metadata
- `approval_histories` (morph) — workflow audit

## Status flow

`draft` → `under_review` → `returned` | `approved` → `superseded` | `retired`

## Versioning

Approved indicators are never overwritten in place. Updates create a new draft version (`parent_indicator_id`, incremented `version`) and mark the prior approved row `superseded`.

## Isolation

Organization foreign key + scoped unique business codes; API route bindings enforce org context.
