<?php

namespace App\Shared\Routing;

use App\Models\User;
use App\Modules\AccessControl\Models\Role;
use App\Modules\AnnualWorkPlan\Models\AnnualWorkPlan;
use App\Modules\Audit\Models\AuditLog;
use App\Modules\DataCollection\Models\ReportingAssignment;
use App\Modules\DataCollection\Models\Submission;
use App\Modules\Dqa\Models\DqaAssessment;
use App\Modules\Dqa\Models\DqaIssue;
use App\Modules\Evaluation\Models\Evaluation;
use App\Modules\Evaluation\Models\EvaluationRecommendation;
use App\Modules\Feedback\Models\FeedbackCase;
use App\Modules\Geography\Models\GeographicType;
use App\Modules\Geography\Models\GeographicUnit;
use App\Modules\Indicator\Models\Indicator;
use App\Modules\Knowledge\Models\KnowledgeDocument;
use App\Modules\Learning\Models\LearningAction;
use App\Modules\Lesson\Models\Lesson;
use App\Modules\MonitoringPlan\Models\MonitoringPlan;
use App\Modules\Organization\Models\Department;
use App\Modules\Organization\Models\Facility;
use App\Modules\Organization\Models\FacilityType;
use App\Modules\Organization\Models\Office;
use App\Modules\Organization\Models\Team;
use App\Modules\Project\Models\Programme;
use App\Modules\Project\Models\Project;
use App\Modules\ReportingCalendar\Models\ReportingCalendar;
use App\Modules\ReportingCalendar\Models\ReportingPeriod;
use App\Modules\Reports\Models\Report;
use App\Modules\ResultsFramework\Models\Result;
use App\Modules\ResultsFramework\Models\ResultsFramework;
use App\Modules\StrategicPlanning\Models\StrategicPlan;
use App\Shared\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

class OrganizationScopedRouteBindings
{
    public static function register(): void
    {
        $scoped = [
            'department' => Department::class,
            'office' => Office::class,
            'facility' => Facility::class,
            'facility_type' => FacilityType::class,
            'team' => Team::class,
            'geographic_unit' => GeographicUnit::class,
            'geographic_type' => GeographicType::class,
            'reporting_calendar' => ReportingCalendar::class,
            'reporting_period' => ReportingPeriod::class,
            'results_framework' => ResultsFramework::class,
            'result' => Result::class,
            'indicator' => Indicator::class,
            'monitoring_plan' => MonitoringPlan::class,
            'reporting_assignment' => ReportingAssignment::class,
            'submission' => Submission::class,
            'dqa_assessment' => DqaAssessment::class,
            'dqa_issue' => DqaIssue::class,
            'report' => Report::class,
            'evaluation' => Evaluation::class,
            'evaluation_recommendation' => EvaluationRecommendation::class,
            'feedback_case' => FeedbackCase::class,
            'lesson' => Lesson::class,
            'knowledge_document' => KnowledgeDocument::class,
            'learning_action' => LearningAction::class,
            'role' => Role::class,
            'audit_log' => AuditLog::class,
            'strategic_plan' => StrategicPlan::class,
            'annual_work_plan' => AnnualWorkPlan::class,
            'project' => Project::class,
            'programme' => Programme::class,
        ];

        foreach ($scoped as $parameter => $modelClass) {
            Route::bind($parameter, function (string $value) use ($modelClass) {
                /** @var Model|null $model */
                $model = $modelClass::query()->find($value);

                if (! $model) {
                    abort(404);
                }

                $contextId = app(OrganizationContext::class)->id();

                if ($contextId !== null && (int) $model->getAttribute('organization_id') !== $contextId) {
                    abort(404);
                }

                return $model;
            });
        }

        Route::bind('user', function (string $value) {
            $user = User::query()->find($value);

            if (! $user) {
                abort(404);
            }

            $contextId = app(OrganizationContext::class)->id();

            if ($contextId !== null && ! $user->organizations()->where('organizations.id', $contextId)->exists()) {
                abort(404);
            }

            return $user;
        });
    }
}
