<?php

namespace App\Modules\Organization\Services;

use App\Enums\AuditAction;
use App\Enums\RecordStatus;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Organization\Models\Department;
use App\Modules\Organization\Models\Office;
use App\Shared\Support\OrganizationContext;
use Illuminate\Validation\ValidationException;

class OfficeService
{
    public function __construct(
        protected AuditService $auditService,
        protected OrganizationContext $organizationContext,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Office
    {
        $organizationId = $this->organizationContext->id();
        $this->assertDepartmentBelongsToOrg($data['department_id'] ?? null, $organizationId);

        $office = Office::query()->create([
            ...$data,
            'organization_id' => $organizationId,
            'status' => $data['status'] ?? RecordStatus::Active->value,
        ]);

        $this->auditService->log(AuditAction::Created, 'offices', Office::class, $office->id);

        return $office;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Office $office, array $data): Office
    {
        $this->assertOrg($office);
        if (isset($data['department_id'])) {
            $this->assertDepartmentBelongsToOrg($data['department_id'], $office->organization_id);
        }
        $office->fill($data)->save();
        $this->auditService->log(AuditAction::Updated, 'offices', Office::class, $office->id, null, $data);

        return $office;
    }

    public function delete(Office $office): void
    {
        $this->assertOrg($office);
        $office->delete();
        $this->auditService->log(AuditAction::Deleted, 'offices', Office::class, $office->id);
    }

    protected function assertOrg(Office $office): void
    {
        if ($office->organization_id !== $this->organizationContext->id()) {
            abort(404);
        }
    }

    protected function assertDepartmentBelongsToOrg(?int $departmentId, ?int $organizationId): void
    {
        if ($departmentId === null) {
            return;
        }

        $valid = Department::query()
            ->where('id', $departmentId)
            ->where('organization_id', $organizationId)
            ->exists();

        if (! $valid) {
            throw ValidationException::withMessages([
                'department_id' => ['Department does not belong to this organization.'],
            ]);
        }
    }
}
