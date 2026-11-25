# Programme / Project / RF Workflows

## Programme
No formal approval workflow — status string (`active` default). Soft-delete blocked when projects exist.

## Project
```
draft ──submit──► under_review ──approve──► approved ──activate──► active ──archive──► archived
                      │                         ▲
                      └──return(+comment)──► returned ──submit──┘
```
Legacy: `draft → activate` still allowed.

## Results Framework
Uses `WorkflowService` + approval history:
```
draft/returned → under_review → approved → active → archived
```
`version` creates a new draft child (`parent_framework_id`, version+1).
