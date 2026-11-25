<?php

namespace Tests\Feature\Api;

use App\Enums\ReportingFrequency;
use App\Modules\ReportingCalendar\Models\ReportingCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOrganizationContext;
use Tests\TestCase;

class ReportingCalendarTest extends TestCase
{
    use CreatesOrganizationContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMealMasterBasics();
    }

    public function test_can_create_reporting_calendar_with_periods(): void
    {
        $context = $this->createOrganizationContext();
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $this->withHeaders($headers)
            ->postJson('/api/v1/reporting-calendars', [
                'name' => 'FY 2026',
                'frequency' => ReportingFrequency::Quarterly->value,
                'periods' => [
                    [
                        'name' => 'Q1',
                        'starts_on' => '2026-01-01',
                        'ends_on' => '2026-03-31',
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'FY 2026');
    }

    public function test_period_end_before_start_is_rejected(): void
    {
        $context = $this->createOrganizationContext();
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $calendar = ReportingCalendar::query()->create([
            'organization_id' => $context['organization']->id,
            'name' => 'Calendar',
            'frequency' => ReportingFrequency::Monthly->value,
            'allow_overlapping_periods' => false,
            'status' => 'active',
        ]);

        $this->withHeaders($headers)
            ->postJson("/api/v1/reporting-calendars/{$calendar->id}/periods", [
                'name' => 'Bad Period',
                'starts_on' => '2026-02-01',
                'ends_on' => '2026-01-01',
            ])
            ->assertStatus(422);
    }

    public function test_overlapping_periods_are_rejected_when_disabled(): void
    {
        $context = $this->createOrganizationContext();
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $calendar = ReportingCalendar::query()->create([
            'organization_id' => $context['organization']->id,
            'name' => 'Strict Calendar',
            'frequency' => ReportingFrequency::Monthly->value,
            'allow_overlapping_periods' => false,
            'status' => 'active',
        ]);

        $this->withHeaders($headers)
            ->postJson("/api/v1/reporting-calendars/{$calendar->id}/periods", [
                'name' => 'January',
                'starts_on' => '2026-01-01',
                'ends_on' => '2026-01-31',
            ])
            ->assertCreated();

        $this->withHeaders($headers)
            ->postJson("/api/v1/reporting-calendars/{$calendar->id}/periods", [
                'name' => 'Overlap',
                'starts_on' => '2026-01-15',
                'ends_on' => '2026-02-15',
            ])
            ->assertStatus(422);
    }

    public function test_admin_can_open_and_close_reporting_period(): void
    {
        $context = $this->createOrganizationContext();
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $calendar = ReportingCalendar::query()->create([
            'organization_id' => $context['organization']->id,
            'name' => 'Status Calendar',
            'frequency' => ReportingFrequency::Monthly->value,
            'allow_overlapping_periods' => false,
            'status' => 'active',
        ]);

        $periodId = $this->withHeaders($headers)
            ->postJson("/api/v1/reporting-calendars/{$calendar->id}/periods", [
                'name' => 'January',
                'starts_on' => '2026-01-01',
                'ends_on' => '2026-01-31',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->withHeaders($headers)
            ->postJson("/api/v1/reporting-periods/{$periodId}/close")
            ->assertOk()
            ->assertJsonPath('data.status', 'closed');

        $this->withHeaders($headers)
            ->postJson("/api/v1/reporting-periods/{$periodId}/open")
            ->assertOk()
            ->assertJsonPath('data.status', 'open');

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $context['organization']->id,
            'module' => 'reporting_calendars',
            'entity_id' => (string) $periodId,
            'action' => 'updated',
        ]);
    }

    public function test_duplicate_period_names_are_rejected(): void
    {
        $context = $this->createOrganizationContext();
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $calendar = ReportingCalendar::query()->create([
            'organization_id' => $context['organization']->id,
            'name' => 'Named Calendar',
            'frequency' => ReportingFrequency::Monthly->value,
            'allow_overlapping_periods' => true,
            'status' => 'active',
        ]);

        $this->withHeaders($headers)
            ->postJson("/api/v1/reporting-calendars/{$calendar->id}/periods", [
                'name' => 'January',
                'starts_on' => '2026-01-01',
                'ends_on' => '2026-01-31',
            ])
            ->assertCreated();

        $this->withHeaders($headers)
            ->postJson("/api/v1/reporting-calendars/{$calendar->id}/periods", [
                'name' => 'January',
                'starts_on' => '2027-01-01',
                'ends_on' => '2027-01-31',
            ])
            ->assertStatus(422);
    }
}
