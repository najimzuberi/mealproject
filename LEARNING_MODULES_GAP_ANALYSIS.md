# Learning Modules — Gap Analysis

**Date:** 2026-11-25

## Reuse
- Morph `documents`, `WorkflowService`, `HasApprovalHistory`, `AuditService`, org-scoped policies
- Angular Material page patterns from MEAL/Performance features

## Missing (greenfield)
| Module | Gap |
|---|---|
| Evaluations | Full domain + workflow + findings/recommendations/actions |
| Feedback & Complaints | Case model + restricted safeguarding access |
| Lessons Learned | Register + application actions + source links |
| Knowledge Repository | Catalog metadata over document storage |
| Permissions | `evaluations.*`, `feedback.*`, `lessons.*`, `knowledge.*` |
| Notifications | Domain notifications (optional reminders via audit + status flags) |

## ERD (logical)

```
evaluations ── evaluation_questions / criteria / methods
            ── evaluation_findings ── evaluation_recommendations
                                  └── management_responses
            ── learning_actions (polymorphic actionable)

feedback_cases ── feedback_categories
               ── feedback_referrals / resolutions
               └── documents (restricted)

lessons ── lesson_links (morph to plan/project/indicator/evaluation)
        └── learning_actions

knowledge_documents ── documents (versions)
                    └── related_knowledge
```
