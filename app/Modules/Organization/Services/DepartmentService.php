<?php

namespace App\Modules\Organization\Services;

use App\Enums\AuditAction;
use App\Enums\RecordStatus;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Organization\Models\Department;
use App\Shared\Support\OrganizationContext;
use Illuminate\Validation\ValidationException;

class DepartmentService
{
    public function __construct(
        protected AuditService $auditService,
        protected OrganizationContext $organizationContext,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Department
    {
        $organizationId = $this->organizationContext->id();
        if (! empty($data['parent_id'])) {
            $this->assertParentBelongsToOrg((int) $data['parent_id'], $organizationId);
        }

        $department = Department::query()->create([
            ...$data,
            'organization_id' => $organizationId,
            'status' => $data['status'] ?? RecordStatus::Active->value,
        ]);

        $this->auditService->log(AuditAction::Created, 'departments', Department::class, $department->id);

        return $department;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Department $department, array $data): Department
    {
        $this->assertOrg($department);

        if (array_key_exists('parent_id', $data) && $data['parent_id'] !== null) {
            $this->assertValidParent($department, (int) $data['parent_id']);
        }

        $old = $department->only(array_keys($data));
        $department->fill($data)->save();
        $this->auditService->log(AuditAction::Updated, 'departments', Department::class, $department->id, $old, $data);

        return $department;
    }

    public function delete(Department $department): void
    {
        $this->assertOrg($department);

        if ($department->children()->exists() || $department->offices()->exists()) {
            throw ValidationException::withMessages([
                'department' => ['Cannot delete a department that has child departments or offices. Deactivate it instead.'],
            ]);
        }

        $department->delete();
        $this->auditService->log(AuditAction::Deleted, 'departments', Department::class, $department->id);
    }

    protected function assertValidParent(Department $department, int $parentId): void
    {
        if ($parentId === $department->id) {
            throw ValidationException::withMessages([
                'parent_id' => ['A department cannot be its own parent.'],
            ]);
        }

        $this->assertParentBelongsToOrg($parentId, $department->organization_id);

        $cursor = Department::query()->find($parentId);
        while ($cursor?->parent_id) {
            if ((int) $cursor->parent_id === (int) $department->id) {
                throw ValidationException::withMessages([
                    'parent_id' => ['Cannot assign a descendant as the parent (circular hierarchy).'],
                ]);
            }
            $cursor = Department::query()->find($cursor->parent_id);
        }
    }

    protected function assertParentBelongsToOrg(int $parentId, int $organizationId): void
    {
        $valid = Department::query()
            ->where('id', $parentId)
            ->where('organization_id', $organizationId)
            ->exists();

        if (! $valid) {
            throw ValidationException::withMessages([
                'parent_id' => ['Parent department must belong to the same organization.'],
            ]);
        }
    }

    protected function assertOrg(Department $department): void
    {
        if ($department->organization_id !== $this->organizationContext->id()) {
            abort(404);
        }
    }
}
