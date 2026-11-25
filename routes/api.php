<?php

use App\Modules\AccessControl\Controllers\Api\RoleController;
use App\Modules\AnnualWorkPlan\Controllers\Api\AnnualWorkPlanController;
use App\Modules\Audit\Controllers\Api\AuditLogController;
use App\Modules\Audit\Controllers\Api\LoginLogController;
use App\Modules\Auth\Controllers\Api\AuthController;
use App\Modules\Dashboard\Controllers\Api\DashboardController;
use App\Modules\DataCollection\Controllers\Api\ReportingAssignmentController;
use App\Modules\DataCollection\Controllers\Api\SubmissionController;
use App\Modules\Dqa\Controllers\Api\DqaAssessmentController;
use App\Modules\Dqa\Controllers\Api\DqaIssueController;
use App\Modules\Evaluation\Controllers\Api\EvaluationController;
use App\Modules\Feedback\Controllers\Api\FeedbackController;
use App\Modules\Knowledge\Controllers\Api\KnowledgeDocumentController;
use App\Modules\Learning\Controllers\Api\LearningActionController;
use App\Modules\Lesson\Controllers\Api\LessonController;
use App\Modules\Performance\Controllers\Api\PerformanceController;
use App\Modules\Reports\Controllers\Api\ReportController;
use App\Modules\Geography\Controllers\Api\GeographicTypeController;
use App\Modules\Geography\Controllers\Api\GeographicUnitController;
use App\Modules\Indicator\Controllers\Api\IndicatorController;
use App\Modules\MonitoringPlan\Controllers\Api\MonitoringPlanController;
use App\Modules\Organization\Controllers\Api\DepartmentController;
use App\Modules\Organization\Controllers\Api\FacilityController;
use App\Modules\Organization\Controllers\Api\FacilityTypeController;
use App\Modules\Organization\Controllers\Api\OfficeController;
use App\Modules\Organization\Controllers\Api\OrganizationController;
use App\Modules\Organization\Controllers\Api\TeamController;
use App\Modules\Organization\Controllers\Api\UserController;
use App\Modules\Project\Controllers\Api\ProgrammeController;
use App\Modules\Project\Controllers\Api\ProjectController;
use App\Modules\ReportingCalendar\Controllers\Api\ReportingCalendarController;
use App\Modules\ResultsFramework\Controllers\Api\ResultController;
use App\Modules\ResultsFramework\Controllers\Api\ResultsFrameworkController;
use App\Modules\Sector\Controllers\Api\SectorController;
use App\Modules\StrategicPlanning\Controllers\Api\StrategicGoalController;
use App\Modules\StrategicPlanning\Controllers\Api\StrategicPlanController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::get('/sectors', [SectorController::class, 'index']);

        Route::post('/organizations', [OrganizationController::class, 'store']);
        Route::get('/organizations', [OrganizationController::class, 'index']);
        Route::get('/platform/organizations', [OrganizationController::class, 'platformIndex']);
    });

    Route::middleware(['auth:sanctum', 'organization.context'])->group(function () {
        Route::get('/organizations/{organization}', [OrganizationController::class, 'show']);
        Route::post('/organizations/{organization}/activate', [OrganizationController::class, 'activate']);
        Route::post('/organizations/{organization}/deactivate', [OrganizationController::class, 'deactivate']);
    });

    Route::middleware(['auth:sanctum', 'organization.context', 'organization.active'])->group(function () {
        Route::put('/organizations/{organization}', [OrganizationController::class, 'update']);
        Route::post('/organizations/{organization}/logo', [OrganizationController::class, 'uploadLogo']);
        Route::put('/organizations/{organization}/sectors', [OrganizationController::class, 'syncSectors']);
        Route::get('/organization/settings', [OrganizationController::class, 'showSettings']);
        Route::put('/organization/settings', [OrganizationController::class, 'updateSettings']);

        Route::apiResource('users', UserController::class)->except(['create', 'edit']);
        Route::post('/users/{user}/activate', [UserController::class, 'activate']);
        Route::post('/users/{user}/deactivate', [UserController::class, 'deactivate']);
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword']);
        Route::put('/users/{user}/roles', [UserController::class, 'assignRoles']);

        Route::get('/permissions', [RoleController::class, 'permissions']);
        Route::apiResource('roles', RoleController::class)->except(['create', 'edit', 'update']);
        Route::put('/roles/{role}/permissions', [RoleController::class, 'syncPermissions']);

        Route::apiResource('departments', DepartmentController::class)->except(['create', 'edit']);
        Route::apiResource('offices', OfficeController::class)->except(['create', 'edit']);
        Route::apiResource('facilities', FacilityController::class)->except(['create', 'edit']);
        Route::apiResource('facility-types', FacilityTypeController::class)->except(['create', 'edit']);
        Route::apiResource('teams', TeamController::class)->except(['create', 'edit']);
        Route::put('/teams/{team}/members', [TeamController::class, 'syncMembers']);

        Route::apiResource('geographic-units', GeographicUnitController::class)->except(['create', 'edit']);
        Route::apiResource('geographic-types', GeographicTypeController::class)->except(['create', 'edit']);

        Route::get('/reporting-calendars/compliance', [ReportingCalendarController::class, 'compliance']);
        Route::apiResource('reporting-calendars', ReportingCalendarController::class)->except(['create', 'edit', 'destroy']);
        Route::get('/reporting-calendars/{reporting_calendar}/periods', [ReportingCalendarController::class, 'periods']);
        Route::post('/reporting-calendars/{reporting_calendar}/periods', [ReportingCalendarController::class, 'storePeriod']);
        Route::post('/reporting-periods/{reporting_period}/open', [ReportingCalendarController::class, 'openPeriod']);
        Route::post('/reporting-periods/{reporting_period}/close', [ReportingCalendarController::class, 'closePeriod']);

        Route::get('/audit-logs', [AuditLogController::class, 'index']);
        Route::get('/audit-logs/{audit_log}', [AuditLogController::class, 'show']);
        Route::get('/login-logs', [LoginLogController::class, 'index']);

        Route::get('/dashboard/overview', [DashboardController::class, 'overview']);
        Route::get('/performance/dashboard', [PerformanceController::class, 'dashboard']);
        Route::get('/performance/indicator-results', [PerformanceController::class, 'indicatorResults']);

        Route::get('/reports', [ReportController::class, 'index']);
        Route::post('/reports', [ReportController::class, 'store']);
        Route::get('/reports/{report}', [ReportController::class, 'show']);
        Route::put('/reports/{report}', [ReportController::class, 'update']);
        Route::post('/reports/{report}/submit', [ReportController::class, 'submit']);
        Route::post('/reports/{report}/return', [ReportController::class, 'returnReport']);
        Route::post('/reports/{report}/approve', [ReportController::class, 'approve']);
        Route::post('/reports/{report}/publish', [ReportController::class, 'publish']);
        Route::get('/reports/{report}/export/pdf', [ReportController::class, 'exportPdf']);
        Route::get('/reports/{report}/export/excel', [ReportController::class, 'exportExcel']);

        Route::apiResource('strategic-plans', StrategicPlanController::class)->except(['create', 'edit']);
        Route::post('/strategic-plans/{strategic_plan}/submit', [StrategicPlanController::class, 'submit']);
        Route::post('/strategic-plans/{strategic_plan}/return', [StrategicPlanController::class, 'returnForCorrection']);
        Route::post('/strategic-plans/{strategic_plan}/approve', [StrategicPlanController::class, 'approve']);
        Route::post('/strategic-plans/{strategic_plan}/activate', [StrategicPlanController::class, 'activate']);
        Route::post('/strategic-plans/{strategic_plan}/archive', [StrategicPlanController::class, 'archive']);
        Route::post('/strategic-plans/{strategic_plan}/duplicate', [StrategicPlanController::class, 'duplicate']);
        Route::get('/strategic-plans/{strategic_plan}/versions', [StrategicPlanController::class, 'versions']);
        Route::get('/strategic-plans/{strategic_plan}/hierarchy', [StrategicPlanController::class, 'hierarchy']);
        Route::post('/strategic-plans/{strategic_plan}/documents', [StrategicPlanController::class, 'storeDocument']);
        Route::delete('/strategic-plans/{strategic_plan}/documents/{document}', [StrategicPlanController::class, 'destroyDocument']);

        Route::get('/strategic-plans/{strategic_plan}/goals', [StrategicGoalController::class, 'index']);
        Route::post('/strategic-plans/{strategic_plan}/goals', [StrategicGoalController::class, 'store']);
        Route::delete('/strategic-plans/{strategic_plan}/goals/{goal}', [StrategicGoalController::class, 'destroy']);
        Route::get('/goals/{goal}', [StrategicGoalController::class, 'show']);
        Route::put('/goals/{goal}', [StrategicGoalController::class, 'update']);
        Route::post('/goals/{goal}/objectives', [StrategicGoalController::class, 'storeObjective']);
        Route::get('/objectives/{objective}', [StrategicGoalController::class, 'showObjective']);
        Route::put('/objectives/{objective}', [StrategicGoalController::class, 'updateObjective']);

        Route::apiResource('annual-work-plans', AnnualWorkPlanController::class)->except(['create', 'edit']);
        Route::post('/annual-work-plans/{annual_work_plan}/submit', [AnnualWorkPlanController::class, 'submit']);
        Route::post('/annual-work-plans/{annual_work_plan}/return', [AnnualWorkPlanController::class, 'returnForCorrection']);
        Route::post('/annual-work-plans/{annual_work_plan}/approve', [AnnualWorkPlanController::class, 'approve']);
        Route::post('/annual-work-plans/{annual_work_plan}/activate', [AnnualWorkPlanController::class, 'activate']);
        Route::post('/annual-work-plans/{annual_work_plan}/archive', [AnnualWorkPlanController::class, 'archive']);
        Route::put('/annual-work-plans/{annual_work_plan}/budget-lines', [AnnualWorkPlanController::class, 'syncBudgetLines']);
        Route::get('/annual-work-plans/{annual_work_plan}/budget-summary', [AnnualWorkPlanController::class, 'budgetSummary']);

        Route::apiResource('programmes', ProgrammeController::class)->except(['create', 'edit']);
        Route::post('/programmes/{programme}/documents', [ProgrammeController::class, 'storeDocument']);
        Route::delete('/programmes/{programme}/documents/{document}', [ProgrammeController::class, 'destroyDocument']);

        Route::apiResource('projects', ProjectController::class)->except(['create', 'edit']);
        Route::post('/projects/{project}/submit', [ProjectController::class, 'submit']);
        Route::post('/projects/{project}/return', [ProjectController::class, 'returnForCorrection']);
        Route::post('/projects/{project}/approve', [ProjectController::class, 'approve']);
        Route::post('/projects/{project}/activate', [ProjectController::class, 'activate']);
        Route::post('/projects/{project}/archive', [ProjectController::class, 'archive']);
        Route::put('/projects/{project}/team', [ProjectController::class, 'syncTeam']);
        Route::put('/projects/{project}/geographic-units', [ProjectController::class, 'syncGeographicUnits']);
        Route::post('/projects/{project}/documents', [ProjectController::class, 'storeDocument']);
        Route::delete('/projects/{project}/documents/{document}', [ProjectController::class, 'destroyDocument']);

        Route::apiResource('results-frameworks', ResultsFrameworkController::class)->except(['create', 'edit', 'destroy']);
        Route::get('/results-frameworks/{results_framework}/hierarchy', [ResultsFrameworkController::class, 'hierarchy']);
        Route::post('/results-frameworks/{results_framework}/results', [ResultsFrameworkController::class, 'storeResult']);
        Route::post('/results-frameworks/{results_framework}/submit', [ResultsFrameworkController::class, 'submit']);
        Route::post('/results-frameworks/{results_framework}/return', [ResultsFrameworkController::class, 'returnForCorrection']);
        Route::post('/results-frameworks/{results_framework}/approve', [ResultsFrameworkController::class, 'approve']);
        Route::post('/results-frameworks/{results_framework}/activate', [ResultsFrameworkController::class, 'activate']);
        Route::post('/results-frameworks/{results_framework}/archive', [ResultsFrameworkController::class, 'archive']);
        Route::post('/results-frameworks/{results_framework}/version', [ResultsFrameworkController::class, 'version']);

        Route::put('/results/{result}', [ResultController::class, 'update']);
        Route::delete('/results/{result}', [ResultsFrameworkController::class, 'destroyResult']);
        Route::post('/results/{result}/link-indicator', [ResultController::class, 'linkIndicator']);

        Route::apiResource('indicators', IndicatorController::class)->except(['create', 'edit', 'destroy']);
        Route::get('/indicators/{indicator}/versions', [IndicatorController::class, 'versions']);
        Route::post('/indicators/{indicator}/submit', [IndicatorController::class, 'submit']);
        Route::post('/indicators/{indicator}/return', [IndicatorController::class, 'returnIndicator']);
        Route::post('/indicators/{indicator}/approve', [IndicatorController::class, 'approve']);
        Route::post('/indicators/{indicator}/retire', [IndicatorController::class, 'retire']);

        Route::apiResource('monitoring-plans', MonitoringPlanController::class)->except(['create', 'edit', 'destroy']);
        Route::get('/monitoring-plans/{monitoring_plan}/versions', [MonitoringPlanController::class, 'versions']);
        Route::post('/monitoring-plans/{monitoring_plan}/submit', [MonitoringPlanController::class, 'submit']);
        Route::post('/monitoring-plans/{monitoring_plan}/return', [MonitoringPlanController::class, 'returnPlan']);
        Route::post('/monitoring-plans/{monitoring_plan}/approve', [MonitoringPlanController::class, 'approve']);
        Route::post('/monitoring-plans/{monitoring_plan}/activate', [MonitoringPlanController::class, 'activate']);
        Route::post('/monitoring-plans/{monitoring_plan}/archive', [MonitoringPlanController::class, 'archive']);

        Route::get('/reporting-assignments', [ReportingAssignmentController::class, 'index']);
        Route::post('/reporting-assignments', [ReportingAssignmentController::class, 'store']);
        Route::get('/reporting-assignments/{reporting_assignment}', [ReportingAssignmentController::class, 'show']);

        Route::get('/submissions', [SubmissionController::class, 'index']);
        Route::post('/submissions', [SubmissionController::class, 'store']);
        Route::post('/submissions/import', [SubmissionController::class, 'import']);
        Route::get('/submissions/{submission}', [SubmissionController::class, 'show']);
        Route::put('/submissions/{submission}', [SubmissionController::class, 'update']);
        Route::post('/submissions/{submission}/submit', [SubmissionController::class, 'submit']);
        Route::post('/submissions/{submission}/return', [SubmissionController::class, 'returnSubmission']);
        Route::post('/submissions/{submission}/verify', [SubmissionController::class, 'verify']);
        Route::post('/submissions/{submission}/validate', [SubmissionController::class, 'validateSubmission']);
        Route::post('/submissions/{submission}/approve', [SubmissionController::class, 'approve']);
        Route::post('/submissions/{submission}/publish', [SubmissionController::class, 'publish']);
        Route::post('/submissions/{submission}/lock', [SubmissionController::class, 'lock']);

        Route::get('/dqa-assessments', [DqaAssessmentController::class, 'index']);
        Route::post('/dqa-assessments', [DqaAssessmentController::class, 'store']);
        Route::get('/dqa-assessments/{dqa_assessment}', [DqaAssessmentController::class, 'show']);
        Route::post('/dqa-assessments/{dqa_assessment}/complete', [DqaAssessmentController::class, 'complete']);

        Route::get('/dqa-issues', [DqaIssueController::class, 'index']);
        Route::post('/dqa-issues', [DqaIssueController::class, 'store']);
        Route::get('/dqa-issues/{dqa_issue}', [DqaIssueController::class, 'show']);
        Route::post('/dqa-issues/{dqa_issue}/resolve', [DqaIssueController::class, 'resolve']);

        Route::get('/evaluations', [EvaluationController::class, 'index']);
        Route::post('/evaluations', [EvaluationController::class, 'store']);
        Route::get('/evaluations/{evaluation}', [EvaluationController::class, 'show']);
        Route::put('/evaluations/{evaluation}', [EvaluationController::class, 'update']);
        Route::post('/evaluations/{evaluation}/submit', [EvaluationController::class, 'submit']);
        Route::post('/evaluations/{evaluation}/return', [EvaluationController::class, 'returnEvaluation']);
        Route::post('/evaluations/{evaluation}/approve', [EvaluationController::class, 'approve']);
        Route::post('/evaluations/{evaluation}/complete', [EvaluationController::class, 'complete']);
        Route::post('/evaluations/{evaluation}/findings', [EvaluationController::class, 'storeFinding']);
        Route::post('/evaluations/{evaluation}/recommendations', [EvaluationController::class, 'storeRecommendation']);
        Route::post('/evaluation-recommendations/{evaluation_recommendation}/respond', [EvaluationController::class, 'respondRecommendation']);

        Route::get('/feedback-categories', [FeedbackController::class, 'categories']);
        Route::post('/feedback-categories', [FeedbackController::class, 'storeCategory']);
        Route::get('/feedback-cases', [FeedbackController::class, 'index']);
        Route::post('/feedback-cases', [FeedbackController::class, 'store']);
        Route::get('/feedback-cases/{feedback_case}', [FeedbackController::class, 'show']);
        Route::post('/feedback-cases/{feedback_case}/assign', [FeedbackController::class, 'assign']);
        Route::post('/feedback-cases/{feedback_case}/escalate', [FeedbackController::class, 'escalate']);
        Route::post('/feedback-cases/{feedback_case}/resolve', [FeedbackController::class, 'resolve']);
        Route::post('/feedback-cases/{feedback_case}/close', [FeedbackController::class, 'close']);

        Route::get('/lessons', [LessonController::class, 'index']);
        Route::post('/lessons', [LessonController::class, 'store']);
        Route::get('/lessons/{lesson}', [LessonController::class, 'show']);
        Route::put('/lessons/{lesson}', [LessonController::class, 'update']);
        Route::post('/lessons/{lesson}/submit', [LessonController::class, 'submit']);
        Route::post('/lessons/{lesson}/approve', [LessonController::class, 'approve']);
        Route::post('/lessons/{lesson}/assign-action', [LessonController::class, 'assignAction']);
        Route::post('/lessons/{lesson}/apply', [LessonController::class, 'apply']);

        Route::get('/knowledge-documents', [KnowledgeDocumentController::class, 'index']);
        Route::post('/knowledge-documents', [KnowledgeDocumentController::class, 'store']);
        Route::get('/knowledge-documents/{knowledge_document}', [KnowledgeDocumentController::class, 'show']);
        Route::put('/knowledge-documents/{knowledge_document}', [KnowledgeDocumentController::class, 'update']);
        Route::post('/knowledge-documents/{knowledge_document}/upload', [KnowledgeDocumentController::class, 'upload']);
        Route::post('/knowledge-documents/{knowledge_document}/publish', [KnowledgeDocumentController::class, 'publish']);
        Route::post('/knowledge-documents/{knowledge_document}/archive', [KnowledgeDocumentController::class, 'archive']);
        Route::get('/knowledge-documents/{knowledge_document}/download', [KnowledgeDocumentController::class, 'download']);

        Route::get('/learning-actions', [LearningActionController::class, 'index']);
        Route::post('/learning-actions/{learning_action}/complete', [LearningActionController::class, 'complete']);
    });
});
