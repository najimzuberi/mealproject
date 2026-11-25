<?php

namespace App\Modules\Organization\Services;

use App\Enums\AuditAction;
use App\Enums\RecordStatus;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Organization\Models\Facility;
use App\Modules\Organization\Models\Office;
use App\Shared\Support\OrganizationContext;
use Illuminate\Validation\ValidationException;

class FacilityService
{
    public function __construct(
        protected AuditService $auditService,
        protected OrganizationContext $organizationContext,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Facility
    {
        $organizationId = $this->organizationContext->id();
        $this->assertOfficeBelongsToOrg($data['office_id'] ?? null, $organizationId);

        $facility = Facility::query()->create([
            ...$data,
            'organization_id' => $organizationId,
            'status' => $data['status'] ?? RecordStatus::Active->value,
        ]);

        $this->auditService->log(AuditAction::Created, 'facilities', Facility::class, $facility->id);

        return $facility;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Facility $facility, array $data): Facility
    {
        $this->assertOrg($facility);
        if (isset($data['office_id'])) {
            $this->assertOfficeBelongsToOrg($data['office_id'], $facility->organization_id);
        }
        $facility->fill($data)->save();
        $this->auditService->log(AuditAction::Updated, 'facilities', Facility::class, $facility->id, null, $data);

        return $facility;
    }

    public function delete(Facility $facility): void
    {
        $this->assertOrg($facility);
        $facility->delete();
        $this->auditService->log(AuditAction::Deleted, 'facilities', Facility::class, $facility->id);
    }

    protected function assertOrg(Facility $facility): void
    {
        if ($facility->organization_id !== $this->organizationContext->id()) {
            abort(404);
        }
    }

    protected function assertOfficeBelongsToOrg(?int $officeId, ?int $organizationId): void
    {
        if ($officeId === null) {
            return;
        }

        $valid = Office::query()
            ->where('id', $officeId)
            ->where('organization_id', $organizationId)
            ->exists();

        if (! $valid) {
            throw ValidationException::withMessages([
                'office_id' => ['Office does not belong to this organization.'],
            ]);
        }
    }
}
