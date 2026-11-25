<?php

namespace App\Providers;

use App\Models\User;
use App\Modules\AccessControl\Models\Role;
use App\Modules\AccessControl\Policies\RolePolicy;
use App\Modules\AnnualWorkPlan\Models\AnnualWorkPlan;
use App\Modules\AnnualWorkPlan\Policies\AnnualWorkPlanPolicy;
use App\Modules\Audit\Models\AuditLog;
use App\Modules\Audit\Models\LoginLog;
use App\Modules\Audit\Policies\AuditLogPolicy;
use App\Modules\Audit\Policies\LoginLogPolicy;
use App\Modules\DataCollection\Models\ReportingAssignment;
use App\Modules\DataCollection\Models\Submission;
use App\Modules\DataCollection\Policies\ReportingAssignmentPolicy;
use App\Modules\DataCollection\Policies\SubmissionPolicy;
use App\Modules\Dqa\Models\DqaAssessment;
use App\Modules\Dqa\Models\DqaIssue;
use App\Modules\Dqa\Policies\DqaAssessmentPolicy;
use App\Modules\Dqa\Policies\DqaIssuePolicy;
use App\Modules\Evaluation\Models\Evaluation;
use App\Modules\Evaluation\Policies\EvaluationPolicy;
use App\Modules\Feedback\Models\FeedbackCase;
use App\Modules\Feedback\Policies\FeedbackCasePolicy;
use App\Modules\Geography\Models\GeographicType;
use App\Modules\Geography\Models\GeographicUnit;
use App\Modules\Geography\Policies\GeographicTypePolicy;
use App\Modules\Geography\Policies\GeographicUnitPolicy;
use App\Modules\Indicator\Models\Indicator;
use App\Modules\Indicator\Policies\IndicatorPolicy;
use App\Modules\Knowledge\Models\KnowledgeDocument;
use App\Modules\Knowledge\Policies\KnowledgeDocumentPolicy;
use App\Modules\Lesson\Models\Lesson;
use App\Modules\Lesson\Policies\LessonPolicy;
use App\Modules\MonitoringPlan\Models\MonitoringPlan;
use App\Modules\MonitoringPlan\Policies\MonitoringPlanPolicy;
use App\Modules\Organization\Models\Department;
use App\Modules\Organization\Models\Facility;
use App\Modules\Organization\Models\FacilityType;
use App\Modules\Organization\Models\Office;
use App\Modules\Organization\Models\Organization;
use App\Modules\Organization\Models\Team;
use App\Modules\Organization\Policies\DepartmentPolicy;
use App\Modules\Organization\Policies\FacilityPolicy;
use App\Modules\Organization\Policies\FacilityTypePolicy;
use App\Modules\Organization\Policies\OfficePolicy;
use App\Modules\Organization\Policies\OrganizationPolicy;
use App\Modules\Organization\Policies\TeamPolicy;
use App\Modules\Organization\Policies\UserPolicy;
use App\Modules\Project\Models\Programme;
use App\Modules\Project\Models\Project;
use App\Modules\Project\Policies\ProgrammePolicy;
use App\Modules\Project\Policies\ProjectPolicy;
use App\Modules\ReportingCalendar\Models\ReportingCalendar;
use App\Modules\ReportingCalendar\Policies\ReportingCalendarPolicy;
use App\Modules\Reports\Models\Report;
use App\Modules\Reports\Policies\ReportPolicy;
use App\Modules\ResultsFramework\Models\Result;
use App\Modules\ResultsFramework\Models\ResultsFramework;
use App\Modules\ResultsFramework\Policies\ResultPolicy;
use App\Modules\ResultsFramework\Policies\ResultsFrameworkPolicy;
use App\Modules\StrategicPlanning\Models\StrategicPlan;
use App\Modules\StrategicPlanning\Policies\StrategicPlanPolicy;
use App\Shared\Routing\OrganizationScopedRouteBindings;
use App\Shared\Support\OrganizationContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OrganizationContext::class);
    }

    public function boot(): void
    {
        OrganizationScopedRouteBindings::register();

        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(Office::class, OfficePolicy::class);
        Gate::policy(Facility::class, FacilityPolicy::class);
        Gate::policy(FacilityType::class, FacilityTypePolicy::class);
        Gate::policy(Team::class, TeamPolicy::class);
        Gate::policy(GeographicUnit::class, GeographicUnitPolicy::class);
        Gate::policy(GeographicType::class, GeographicTypePolicy::class);
        Gate::policy(ReportingCalendar::class, ReportingCalendarPolicy::class);
        Gate::policy(StrategicPlan::class, StrategicPlanPolicy::class);
        Gate::policy(AnnualWorkPlan::class, AnnualWorkPlanPolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Programme::class, ProgrammePolicy::class);
        Gate::policy(ResultsFramework::class, ResultsFrameworkPolicy::class);
        Gate::policy(Result::class, ResultPolicy::class);
        Gate::policy(Indicator::class, IndicatorPolicy::class);
        Gate::policy(MonitoringPlan::class, MonitoringPlanPolicy::class);
        Gate::policy(ReportingAssignment::class, ReportingAssignmentPolicy::class);
        Gate::policy(Submission::class, SubmissionPolicy::class);
        Gate::policy(DqaAssessment::class, DqaAssessmentPolicy::class);
        Gate::policy(DqaIssue::class, DqaIssuePolicy::class);
        Gate::policy(Report::class, ReportPolicy::class);
        Gate::policy(Evaluation::class, EvaluationPolicy::class);
        Gate::policy(FeedbackCase::class, FeedbackCasePolicy::class);
        Gate::policy(Lesson::class, LessonPolicy::class);
        Gate::policy(KnowledgeDocument::class, KnowledgeDocumentPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(LoginLog::class, LoginLogPolicy::class);

        Gate::define('viewPerformance', function ($user) {
            return $user->can('performance.view') || $user->can('indicators.view');
        });
    }
}
