<?php

namespace Tests\Feature\Api;

use App\Modules\Project\Models\Programme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesOrganizationContext;
use Tests\TestCase;

class ProgrammeTest extends TestCase
{
    use CreatesOrganizationContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMealMasterBasics();
    }

    public function test_can_create_and_update_programme(): void
    {
        $context = $this->createOrganizationContext();
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $id = $this->withHeaders($headers)
            ->postJson('/api/v1/programmes', [
                'code' => 'PRG-001',
                'name' => 'Health Programme',
                'description' => 'Community health',
                'objectives' => 'Improve outcomes',
                'budget_envelope' => 100000,
                'currency' => 'USD',
                'target_groups' => ['Women', 'Youth'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.code', 'PRG-001')
            ->assertJsonPath('data.currency', 'USD')
            ->json('data.id');

        $this->withHeaders($headers)
            ->putJson("/api/v1/programmes/{$id}", [
                'name' => 'Health Programme Updated',
                'donor_name' => 'Global Fund',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Health Programme Updated')
            ->assertJsonPath('data.donor_name', 'Global Fund');
    }

    public function test_can_upload_programme_document(): void
    {
        Storage::fake('public');

        $context = $this->createOrganizationContext();
        $headers = $this->withOrganizationHeaders($context['token'], $context['organization']);

        $id = $this->withHeaders($headers)
            ->postJson('/api/v1/programmes', [
                'code' => 'PRG-DOC',
                'name' => 'Doc Programme',
            ])
            ->json('data.id');

        $this->withHeaders($headers)
            ->post("/api/v1/programmes/{$id}/documents", [
                'file' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
                'title' => 'Proposal',
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Proposal');
    }

    public function test_org_a_cannot_see_org_b_programme(): void
    {
        $contextA = $this->createOrganizationContext();
        $contextB = $this->createOrganizationContext();

        $programmeB = Programme::query()->create([
            'organization_id' => $contextB['organization']->id,
            'code' => 'PRG-B',
            'name' => 'Org B Programme',
            'status' => 'active',
        ]);

        $this->withHeaders($this->withOrganizationHeaders($contextA['token'], $contextA['organization']))
            ->getJson("/api/v1/programmes/{$programmeB->id}")
            ->assertNotFound();
    }
}
