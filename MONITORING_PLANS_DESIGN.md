# Monitoring Plans — Design

## Role

The Monitoring Plan is the operational configuration source for MEAL data collection.

## Core tables

- `monitoring_plans` + `monitoring_plan_items` (indicator snapshot fields: baseline/target, collection method, responsibilities, MoV, validation rules)

## Lifecycle

`draft` → `under_review` → `returned` | `approved` → `active` → `archived` / `superseded`

## Activation rule

Only **approved** plans can activate. Activation calls `AssignmentGenerationService` to create `reporting_assignments` for each item with a responsible collector (optionally per open reporting period). Unapproved plans cannot generate official assignments.
