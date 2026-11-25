# Strategy Workflow Documentation

Shared engine: `App\Shared\Services\WorkflowService`  
History: `approval_histories` via `HasApprovalHistory`.

## Strategic Plan

```
DRAFT ──submit──► UNDER_REVIEW ──approve──► APPROVED ──activate──► ACTIVE ──archive──► ARCHIVED
                      │                                              │
                      └──return(+comment)──► RETURNED ──submit──► UNDER_REVIEW
                                                                 ACTIVE may archive siblings when activating a new plan
```

Rules:
- Return requires non-empty comment.
- Submitted sets `submitted_at`.
- Approve sets `approved_at` / `approved_by`.
- Editing approved/active creates a new draft version (`parent_plan_id`).

## Annual Work Plan

Same status enum. Submit requires at least one annual priority. Budget lines sync only in draft/returned. Activate from approved.

## Audit

Every transition logs `AuditAction::Updated` with status/action payload under module `strategic_plans` or `annual_work_plans`.
