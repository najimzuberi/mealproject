<?php

namespace Tests\Feature\Api;

use App\Enums\MonitoringPlanStatus;
use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Enums\SubmissionStatus;
use App\Modules\DataCollection\Models\Submission;
use App\Modules\Indicator\Models\Indicator;
use App\Modules\MonitoringPlan\Models\MonitoringPlan;
use App\Modules\MonitoringPlan\Models\MonitoringPlanItem;
use App\Modules\Project\Models\Project;
use App\Modules\Reports\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOrganizationContext;
use Tests\TestCase;

class ReportEngineTest extends TestCase
{
    use CreatesOrganizationContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMealMasterBasics();
    }

    public function test_can_generate_report_from_published_data_and_export(): void
    {
        $context = $this->createOrganizationContext(roleNames: ['me_officer']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $project = Project::factory()->create(['organization_id' => $context['organization']->id]);
        $indicator = Indicator::factory()->approved()->create([
            'organization_id' => $context['organization']->id,
            'project_id' => $project->id,
        ]);
        $plan = MonitoringPlan::factory()->create([
            'organization_id' => $context['organization']->id,
            'project_id' => $project->id,
            'status' => MonitoringPlanStatus::Active,
        ]);
        $item = MonitoringPlanItem::factory()->create([
            'organization_id' => $context['organization']->id,
            'monitoring_plan_id' => $plan->id,
            'indicator_id' => $indicator->id,
            'target_value' => '50',
            'baseline_value' => '5',
        ]);
        Submission::factory()->create([
            'organization_id' => $context['organization']->id,
            'monitoring_plan_id' => $plan->id,
            'monitoring_plan_item_id' => $item->id,
            'indicator_id' => $indicator->id,
            'status' => SubmissionStatus::Published,
            'value' => 40,
            'published_at' => now(),
        ]);

        $create = $this->withHeaders($headers)
            ->postJson('/api/v1/reports', [
                'title' => 'Q1 Performance',
                'report_type' => ReportType::Quarterly->value,
                'project_id' => $project->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', ReportStatus::Draft->value);

        $id = $create->json('data.id');
        $this->assertStringContainsString('[AI DRAFT]', (string) $create->json('data.executive_summary'));
        $this->assertGreaterThan(0, count($create->json('data.indicator_results')));

        $this->withHeaders($headers)->postJson("/api/v1/reports/{$id}/submit")->assertOk()
            ->assertJsonPath('data.status', ReportStatus::UnderReview->value);
        $this->withHeaders($headers)->postJson("/api/v1/reports/{$id}/approve")->assertOk()
            ->assertJsonPath('data.status', ReportStatus::Approved->value);
        $this->withHeaders($headers)->postJson("/api/v1/reports/{$id}/publish")->assertOk()
            ->assertJsonPath('data.status', ReportStatus::Published->value);

        $this->withHeaders($headers)
            ->get("/api/v1/reports/{$id}/export/excel")
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $pdf = $this->withHeaders($headers)
            ->get("/api/v1/reports/{$id}/export/pdf")
            ->assertOk();
        $this->assertStringStartsWith('%PDF', $pdf->streamedContent());
    }

    public function test_return_requires_comment(): void
    {
        $context = $this->createOrganizationContext(roleNames: ['me_officer']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $report = Report::factory()->create([
            'organization_id' => $context['organization']->id,
            'status' => ReportStatus::UnderReview,
            'prepared_by' => $context['user']->id,
        ]);

        $this->withHeaders($headers)
            ->postJson("/api/v1/reports/{$report->id}/return", [])
            ->assertStatus(422);

        $this->withHeaders($headers)
            ->postJson("/api/v1/reports/{$report->id}/return", ['comment' => 'Add challenges section'])
            ->assertOk()
            ->assertJsonPath('data.status', ReportStatus::Returned->value);
    }
}
