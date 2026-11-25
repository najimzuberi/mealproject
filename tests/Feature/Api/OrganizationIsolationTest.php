<?php

namespace Tests\Feature\Api;

use App\Modules\Audit\Models\AuditLog;
use App\Modules\Geography\Models\GeographicUnit;
use App\Modules\Organization\Models\Department;
use App\Modules\Organization\Models\Facility;
use App\Modules\Organization\Models\Office;
use App\Modules\ReportingCalendar\Models\ReportingCalendar;
use App\Modules\ReportingCalendar\Models\ReportingPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOrganizationContext;
use Tests\TestCase;

class OrganizationIsolationTest extends TestCase
{
    use CreatesOrganizationContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMealMasterBasics();
    }

    public function test_org_a_cannot_read_org_b_department(): void
    {
        $contextA = $this->createOrganizationContext();
        $contextB = $this->createOrganizationContext();

        $departmentB = Department::query()->create([
            'organization_id' => $contextB['organization']->id,
            'name' => 'Finance',
            'code' => 'fin',
            'status' => 'active',
        ]);

        $this->withHeaders($this->withOrganizationHeaders($contextA['token'], $contextA['organization']))
            ->getJson("/api/v1/departments/{$departmentB->id}")
            ->assertNotFound();
    }

    public function test_org_a_cannot_mutate_org_b_department(): void
    {
        $contextA = $this->createOrganizationContext();
        $contextB = $this->createOrganizationContext();

        $departmentB = Department::query()->create([
            'organization_id' => $contextB['organization']->id,
            'name' => 'Finance',
            'code' => 'fin-b',
            'status' => 'active',
        ]);

        $headers = $this->withOrganizationHeaders($contextA['token'], $contextA['organization']);

        $this->withHeaders($headers)
            ->putJson("/api/v1/departments/{$departmentB->id}", ['name' => 'Hacked'])
            ->assertNotFound();

        $this->withHeaders($headers)
            ->deleteJson("/api/v1/departments/{$departmentB->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('departments', [
            'id' => $departmentB->id,
            'name' => 'Finance',
        ]);
    }

    public function test_org_a_cannot_access_org_b_office_or_facility(): void
    {
        $contextA = $this->createOrganizationContext();
        $contextB = $this->createOrganizationContext();

        $officeB = Office::query()->create([
            'organization_id' => $contextB['organization']->id,
            'name' => 'HQ B',
            'code' => 'hq-b',
            'status' => 'active',
        ]);

        $facilityB = Facility::query()->create([
            'organization_id' => $contextB['organization']->id,
            'office_id' => $officeB->id,
            'name' => 'Clinic B',
            'code' => 'cl-b',
            'status' => 'active',
        ]);

        $headers = $this->withOrganizationHeaders($contextA['token'], $contextA['organization']);

        $this->withHeaders($headers)->getJson("/api/v1/offices/{$officeB->id}")->assertNotFound();
        $this->withHeaders($headers)->putJson("/api/v1/offices/{$officeB->id}", ['name' => 'X'])->assertNotFound();
        $this->withHeaders($headers)->getJson("/api/v1/facilities/{$facilityB->id}")->assertNotFound();
        $this->withHeaders($headers)->deleteJson("/api/v1/facilities/{$facilityB->id}")->assertNotFound();
    }

    public function test_org_a_cannot_read_org_b_geographic_unit(): void
    {
        $contextA = $this->createOrganizationContext();
        $contextB = $this->createOrganizationContext();

        $unitB = GeographicUnit::query()->create([
            'organization_id' => $contextB['organization']->id,
            'name' => 'Region B',
            'code' => 'rb',
            'type' => 'region',
            'level' => 0,
            'status' => 'active',
        ]);

        $this->withHeaders($this->withOrganizationHeaders($contextA['token'], $contextA['organization']))
            ->getJson("/api/v1/geographic-units/{$unitB->id}")
            ->assertNotFound();
    }

    public function test_org_a_cannot_read_org_b_user(): void
    {
        $contextA = $this->createOrganizationContext();
        $contextB = $this->createOrganizationContext();

        $this->withHeaders($this->withOrganizationHeaders($contextA['token'], $contextA['organization']))
            ->getJson("/api/v1/users/{$contextB['user']->id}")
            ->assertNotFound();
    }

    public function test_org_a_cannot_access_org_b_reporting_calendar_or_period(): void
    {
        $contextA = $this->createOrganizationContext();
        $contextB = $this->createOrganizationContext();

        $calendarB = ReportingCalendar::query()->create([
            'organization_id' => $contextB['organization']->id,
            'name' => 'FY B',
            'frequency' => 'annual',
            'status' => 'active',
        ]);

        $periodB = ReportingPeriod::query()->create([
            'reporting_calendar_id' => $calendarB->id,
            'organization_id' => $contextB['organization']->id,
            'name' => 'Year',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
            'status' => 'open',
        ]);

        $headers = $this->withOrganizationHeaders($contextA['token'], $contextA['organization']);

        $this->withHeaders($headers)
            ->getJson("/api/v1/reporting-calendars/{$calendarB->id}")
            ->assertNotFound();

        $this->withHeaders($headers)
            ->postJson("/api/v1/reporting-periods/{$periodB->id}/close")
            ->assertNotFound();

        $this->assertSame('open', $periodB->fresh()->status);
    }

    public function test_org_a_cannot_read_org_b_audit_log(): void
    {
        $contextA = $this->createOrganizationContext();
        $contextB = $this->createOrganizationContext();

        $logB = AuditLog::query()->create([
            'organization_id' => $contextB['organization']->id,
            'user_id' => $contextB['user']->id,
            'action' => 'created',
            'module' => 'organization',
            'created_at' => now(),
        ]);

        $headers = $this->withOrganizationHeaders($contextA['token'], $contextA['organization']);

        $this->withHeaders($headers)
            ->getJson("/api/v1/audit-logs/{$logB->id}")
            ->assertNotFound();

        $list = $this->withHeaders($headers)
            ->getJson('/api/v1/audit-logs')
            ->assertOk()
            ->json();

        $encoded = json_encode($list);
        $this->assertStringNotContainsString('"id":'.$logB->id, (string) $encoded);
    }

    public function test_org_a_list_endpoints_exclude_org_b_structure(): void
    {
        $contextA = $this->createOrganizationContext();
        $contextB = $this->createOrganizationContext();

        Department::query()->create([
            'organization_id' => $contextB['organization']->id,
            'name' => 'Secret Dept',
            'code' => 'secret',
            'status' => 'active',
        ]);

        $response = $this->withHeaders($this->withOrganizationHeaders($contextA['token'], $contextA['organization']))
            ->getJson('/api/v1/departments')
            ->assertOk();

        $this->assertStringNotContainsString('Secret Dept', (string) json_encode($response->json()));
    }
}
