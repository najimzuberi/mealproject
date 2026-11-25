# Data Collection — Implementation Report

## Delivered

- Migration: `reporting_assignments`, `submissions`, `submission_values`, `import_jobs`
- APIs: assignments CRUD (list/store/show), submissions full workflow + CSV import
- Services: `SubmissionService`, `AssignmentGenerationService`, `DataQualityCheckService`
- Policies + org-scoped route bindings
- Feature tests: activate→assignments, active-plan gate, full workflow, return comment, org isolation
