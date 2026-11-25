# Evaluations — Database Design

## Tables
- `evaluations` — register + planning metadata + workflow status
- `evaluation_questions` — criteria/questions
- `evaluation_findings` — findings linked to project/result/indicator
- `evaluation_recommendations` — recommendations + management response fields
- `learning_actions` (morph) — follow-up actions from accepted recommendations

## Workflow
`draft` → `under_review` → `returned` | `approved` → `in_progress` → `completed`
