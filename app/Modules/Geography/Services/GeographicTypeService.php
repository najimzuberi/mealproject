<?php

namespace App\Modules\Geography\Services;

use App\Enums\AuditAction;
use App\Enums\RecordStatus;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Geography\Models\GeographicType;
use App\Shared\Support\OrganizationContext;

class GeographicTypeService
{
    public function __construct(
        protected AuditService $auditService,
        protected OrganizationContext $organizationContext,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): GeographicType
    {
        $type = GeographicType::query()->create([
            ...$data,
            'organization_id' => $this->organizationContext->id(),
            'status' => $data['status'] ?? RecordStatus::Active->value,
        ]);

        $this->auditService->log(AuditAction::Created, 'geography', GeographicType::class, $type->id);

        return $type;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(GeographicType $type, array $data): GeographicType
    {
        $this->assertOrg($type);
        $type->fill($data)->save();
        $this->auditService->log(AuditAction::Updated, 'geography', GeographicType::class, $type->id, null, $data);

        return $type;
    }

    public function delete(GeographicType $type): void
    {
        $this->assertOrg($type);
        $type->delete();
        $this->auditService->log(AuditAction::Deleted, 'geography', GeographicType::class, $type->id);
    }

    protected function assertOrg(GeographicType $type): void
    {
        if ($type->organization_id !== $this->organizationContext->id()) {
            abort(404);
        }
    }
}
