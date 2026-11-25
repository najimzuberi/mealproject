# Feedback & Complaints — Workflow

## Case lifecycle
`received` → `assigned` → `under_investigation` / `escalated` → `resolved` → `closed` (or `referred`)

## Confidentiality
Levels: `public`, `internal`, `restricted`, `safeguarding`

### Rules
- Safeguarding/restricted cases are **excluded from general list APIs** unless user has `feedback.sensitive` or `feedback.manage`
- Detail view forbidden without sensitive permission
- Resource redacts description/complainant fields when unauthorized
- Anonymous submissions clear complainant identity fields
- Categories can be marked `is_sensitive` (e.g. Safeguarding, Staff Conduct)

Referral workflows are configurable via status/`feedback_loop_status` — no invented safeguarding procedures.
