# MEAL MASTER — Phase 2 Workflow Documentation

## Common lifecycle actions

| Action | Typical transition | Permission pattern |
|--------|--------------------|--------------------|
| Submit | draft → under_review | `*.submit` |
| Return | under_review → returned | `*.review` (Monitoring Plans) |
| Approve | under_review → approved | `*.approve` |
| Activate | approved → active | `*.manage` |
| Archive | active/approved → archived | `*.manage` |

All transitions write `approval_histories` and audit logs via `WorkflowService`.

## Entity-specific statuses

### Strategic Plans / Annual Work Plans
`draft` → `under_review` → `approved` → `active` → `archived`

### Projects
`draft` → `active` → `on_hold` / `completed` / `archived`

### Results Frameworks
`draft` → `under_review` → `approved` → `active` → `archived`

### Indicators
`draft` → `under_review` → `approved` → `superseded` / `retired`

### Monitoring Plans
`draft` → `under_review` → `returned` → (resubmit) → `approved` → `active` → `superseded` / `archived`

## Role responsibilities

| Role | Programme authority |
|------|---------------------|
| System Administrator | Infrastructure/access; **no** default programme approve |
| Project Manager | Create/edit/submit strategic plans, AWPs, projects |
| M&E Officer | Approve planning artefacts; own RF, indicators, monitoring plans |
| Field Officer / Verification Supervisor / Donor Officer | View-oriented access |

## Amendment rules

- Approved plans/indicators/monitoring plans cannot be silently edited
- Updates create a new version (`version + 1`, parent lineage FK)
- Prior approved records remain for historical integrity (often marked superseded)
- Returns and retirements require comments where configured