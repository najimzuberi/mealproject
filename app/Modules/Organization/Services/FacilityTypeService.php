<?php

namespace App\Modules\Organization\Services;

use App\Enums\AuditAction;
use App\Enums\RecordStatus;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Organization\Models\FacilityType;
use App\Shared\Support\OrganizationContext;

class FacilityTypeService
{
    public function __construct(
        protected AuditService $auditService,
        protected OrganizationContext $organizationContext,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): FacilityType
    {
        $type = FacilityType::query()->create([
            ...$data,
            'organization_id' => $this->organizationContext->id(),
            'status' => $data['status'] ?? RecordStatus::Active->value,
        ]);

        $this->auditService->log(AuditAction::Created, 'facilities', FacilityType::class, $type->id);

        return $type;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(FacilityType $type, array $data): FacilityType
    {
        $this->assertOrg($type);
        $type->fill($data)->save();
        $this->auditService->log(AuditAction::Updated, 'facilities', FacilityType::class, $type->id, null, $data);

        return $type;
    }

    public function delete(FacilityType $type): void
    {
        $this->assertOrg($type);
        $type->delete();
        $this->auditService->log(AuditAction::Deleted, 'facilities', FacilityType::class, $type->id);
    }

    protected function assertOrg(FacilityType $type): void
    {
        if ($type->organization_id !== $this->organizationContext->id()) {
            abort(404);
        }
    }
}
