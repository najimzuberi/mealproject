# MEAL MASTER — Phase 2 Architecture

## Objective

Phase 2 delivers the Strategic Planning and Results Management foundation as a modular monolith on the Phase 1 multi-organization Laravel API.

## Architecture principles

- **Organization-scoped modular monolith** under `app/Modules/*`
- **Reuse Phase 1** auth (Sanctum), tenancy (`X-Organization-Id`), Spatie teams RBAC, audit logging, and org-scoped route bindings
- **Traceable planning chain**: Strategic Plan → Goals/Objectives/KRAs → Annual Work Plan → Project → Results Framework → Indicators → Monitoring Plan
- **Workflow-first**: draft → submit → review/return → approve → activate/archive with `approval_histories`
- **Versioning**: approved Strategic Plans, Indicators, and Monitoring Plans are not silently overwritten
- **API-first** under `/api/v1/`

## Modules

| Module | Path | Responsibility |
|--------|------|----------------|
| StrategicPlanning | `app/Modules/StrategicPlanning` | Multi-year plans, themes, goals, objectives, KRAs |
| AnnualWorkPlan | `app/Modules/AnnualWorkPlan` | Yearly priorities linked to strategic objectives, activities, Q1–Q4 milestones |
| Project | `app/Modules/Project` | Programmes, projects, teams, geography, risks/assumptions |
| ResultsFramework | `app/Modules/ResultsFramework` | Configurable impact→activity hierarchy + theory of change |
| Indicator | `app/Modules/Indicator` | Private indicator library, measurement metadata, versioning |
| MonitoringPlan | `app/Modules/MonitoringPlan` | Monitoring Plan Engine metadata for Phase 3 collection |

## Shared workflow

- `App\Shared\Services\WorkflowService` — status transitions + audit
- `App\Shared\Models\ApprovalHistory` + `HasApprovalHistory` trait
- Enums: `StrategicPlanStatus`, `ProjectStatus`, `IndicatorStatus`, `MonitoringPlanStatus`, `ResultLevel`, `WorkflowStatus`, `ApprovalAction`

## Security model

- All programme records require organization membership + permission
- Route model binding returns 404 across organizations
- System administrators manage infrastructure; programme **approve** is not granted by default
- Project Managers operate plans/projects; M&E Officers own indicators, results frameworks, and monitoring plans

## Out of scope (Phase 3+)

Field data submission, verification queues, DQA execution, dashboards, donor portal, AI assistants.