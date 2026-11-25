# MEAL MASTER — Phase 2 ERD

```mermaid
erDiagram
    ORGANIZATIONS ||--o{ STRATEGIC_PLANS : owns
    STRATEGIC_PLANS ||--o{ STRATEGIC_THEMES : has
    STRATEGIC_PLANS ||--o{ STRATEGIC_GOALS : has
    STRATEGIC_GOALS ||--o{ STRATEGIC_OBJECTIVES : has
    STRATEGIC_OBJECTIVES ||--o{ KEY_RESULT_AREAS : has

    STRATEGIC_PLANS ||--o{ ANNUAL_WORK_PLANS : informs
    STRATEGIC_OBJECTIVES ||--o{ AWP_PRIORITIES : traces
    ANNUAL_WORK_PLANS ||--o{ AWP_PRIORITIES : has
    AWP_PRIORITIES ||--o{ AWP_ACTIVITIES : has
    AWP_ACTIVITIES ||--o{ AWP_MILESTONES : has

    ORGANIZATIONS ||--o{ PROGRAMMES : owns
    PROGRAMMES ||--o{ PROJECTS : groups
    STRATEGIC_PLANS ||--o{ PROJECTS : aligns
    STRATEGIC_OBJECTIVES ||--o{ PROJECTS : aligns
    ANNUAL_WORK_PLANS ||--o{ PROJECTS : aligns
    PROJECTS ||--o{ PROJECT_ACTIVITIES : has
    PROJECTS ||--o{ RESULTS_FRAMEWORKS : has
    RESULTS_FRAMEWORKS ||--o{ RESULTS : tree
    RESULTS ||--o{ RESULTS : parent_child

    ORGANIZATIONS ||--o{ INDICATORS : library
    RESULTS ||--o{ INDICATORS : measures
    PROJECTS ||--o{ MONITORING_PLANS : defines
    MONITORING_PLANS ||--o{ MONITORING_PLAN_ITEMS : configures
    INDICATORS ||--o{ MONITORING_PLAN_ITEMS : referenced

    STRATEGIC_PLANS ||--o{ APPROVAL_HISTORIES : workflow
    ANNUAL_WORK_PLANS ||--o{ APPROVAL_HISTORIES : workflow
    INDICATORS ||--o{ APPROVAL_HISTORIES : workflow
    MONITORING_PLANS ||--o{ APPROVAL_HISTORIES : workflow
```

## Traceability path

Strategic Plan → Goal → Objective → AWP Priority → Project → Results Framework Result → Indicator → Monitoring Plan Item

## Version lineage

- `strategic_plans.parent_plan_id`
- `indicators.parent_indicator_id`
- `monitoring_plans.parent_monitoring_plan_id`
- `results_frameworks.parent_framework_id`
- `annual_work_plans.parent_awp_id`