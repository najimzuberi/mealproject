<?php

namespace App\Modules\Geography\Services;

use App\Enums\AuditAction;
use App\Enums\RecordStatus;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Geography\Models\GeographicUnit;
use App\Shared\Support\OrganizationContext;
use Illuminate\Validation\ValidationException;

class GeographicUnitService
{
    public function __construct(
        protected AuditService $auditService,
        protected OrganizationContext $organizationContext,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): GeographicUnit
    {
        $organizationId = $this->organizationContext->id();
        $parentId = $data['parent_id'] ?? null;

        if ($parentId) {
            $parent = GeographicUnit::query()->findOrFail($parentId);
            if ($parent->organization_id !== $organizationId) {
                throw ValidationException::withMessages([
                    'parent_id' => ['Parent geographic unit must belong to the same organization.'],
                ]);
            }
            $data['level'] = ($parent->level ?? 0) + 1;
        } else {
            $data['level'] = 0;
        }

        $unit = GeographicUnit::query()->create([
            ...$data,
            'type' => $data['type'] ?? 'unit',
            'organization_id' => $organizationId,
            'status' => $data['status'] ?? RecordStatus::Active->value,
        ]);

        $this->auditService->log(AuditAction::Created, 'geography', GeographicUnit::class, $unit->id);

        return $unit;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(GeographicUnit $unit, array $data): GeographicUnit
    {
        $this->assertOrg($unit);

        if (array_key_exists('parent_id', $data)) {
            $parentId = $data['parent_id'];
            if ($parentId === null) {
                $data['level'] = 0;
            } else {
                $this->assertValidParent($unit, (int) $parentId);
                $parent = GeographicUnit::query()->findOrFail($parentId);
                $data['level'] = ($parent->level ?? 0) + 1;
            }
        }

        $unit->fill($data)->save();
        $this->auditService->log(AuditAction::Updated, 'geography', GeographicUnit::class, $unit->id, null, $data);

        return $unit;
    }

    public function delete(GeographicUnit $unit): void
    {
        $this->assertOrg($unit);
        $unit->delete();
        $this->auditService->log(AuditAction::Deleted, 'geography', GeographicUnit::class, $unit->id);
    }

    protected function assertValidParent(GeographicUnit $unit, int $parentId): void
    {
        if ($parentId === $unit->id) {
            throw ValidationException::withMessages([
                'parent_id' => ['A geographic unit cannot be its own parent.'],
            ]);
        }

        $parent = GeographicUnit::query()->findOrFail($parentId);
        if ($parent->organization_id !== $unit->organization_id) {
            throw ValidationException::withMessages([
                'parent_id' => ['Parent geographic unit must belong to the same organization.'],
            ]);
        }

        $cursor = $parent;
        while ($cursor->parent_id) {
            if ((int) $cursor->parent_id === (int) $unit->id) {
                throw ValidationException::withMessages([
                    'parent_id' => ['Cannot assign a descendant as the parent (circular hierarchy).'],
                ]);
            }
            $cursor = GeographicUnit::query()->find($cursor->parent_id);
            if (! $cursor) {
                break;
            }
        }
    }

    protected function assertOrg(GeographicUnit $unit): void
    {
        if ($unit->organization_id !== $this->organizationContext->id()) {
            abort(404);
        }
    }
}
