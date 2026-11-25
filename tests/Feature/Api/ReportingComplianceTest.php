<?php

namespace Tests\Feature\Api;

use App\Enums\ReportingFrequency;
use App\Enums\ReportingPeriodStatus;
use App\Modules\ReportingCalendar\Models\ReportingCalendar;
use App\Modules\ReportingCalendar\Models\ReportingPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOrganizationContext;
use Tests\TestCase;

class ReportingComplianceTest extends TestCase
{
    use CreatesOrganizationContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMealMasterBasics();
    }

    public function test_compliance_endpoint_and_open_close_preserve_behavior(): void
    {
        $context = $this->createOrganizationContext(roleNames: ['me_officer']);
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $calendar = ReportingCalendar::query()->create([
            'organization_id' => $context['organization']->id,
            'name' => 'FY Compliance',
            'frequency' => ReportingFrequency::Quarterly->value,
            'allow_overlapping_periods' => false,
            'status' => 'active',
        ]);

        $period = ReportingPeriod::query()->create([
            'organization_id' => $context['organization']->id,
            'reporting_calendar_id' => $calendar->id,
            'name' => 'Q1',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-03-31',
            'submission_deadline' => '2026-01-01',
            'status' => ReportingPeriodStatus::Draft->value,
        ]);

        $this->withHeaders($headers)
            ->postJson("/api/v1/reporting-periods/{$period->id}/open")
            ->assertOk()
            ->assertJsonPath('data.status', ReportingPeriodStatus::Open->value);

        $period->refresh();
        $this->assertNotNull($period->opened_at);

        $this->withHeaders($headers)
            ->getJson('/api/v1/reporting-calendars/compliance')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'open_periods',
                    'overdue_periods',
                    'assignments',
                    'upcoming_deadlines',
                ],
            ]);

        $this->withHeaders($headers)
            ->postJson("/api/v1/reporting-periods/{$period->id}/close", [
                'closure_notes' => 'Period closed after reviews',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', ReportingPeriodStatus::Closed->value);

        $period->refresh();
        $this->assertNotNull($period->closed_at);
        $this->assertSame('Period closed after reviews', $period->closure_notes);
    }
}
