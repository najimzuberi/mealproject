# DQA Workflow and Rules

## Dimensions scored (0–100)

Validity, Reliability, Completeness, Timeliness, Precision, Integrity → `overall_score` average.

## Automated flags (human review)

| Flag | Meaning |
|---|---|
| missing_value | No value/numerator |
| zero_denominator | Division by zero risk |
| formula_inconsistency | Value ≠ n/d × 100 |
| potential_duplicate | Same item+period already submitted |
| potential_outlier | Extreme absolute value |
| late_submission | Past assignment due date |

Flags create open `dqa_issues`; they do **not** auto-reject submissions.

## Issue resolution

Requires `resolution_notes`; status → `resolved`.
