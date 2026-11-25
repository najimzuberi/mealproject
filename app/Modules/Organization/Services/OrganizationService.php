<?php

namespace App\Modules\Organization\Services;

use App\Enums\AuditAction;
use App\Enums\MembershipStatus;
use App\Enums\OrganizationStatus;
use App\Models\User;
use App\Modules\AccessControl\Support\Permissions;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Organization\Models\Organization;
use App\Modules\Organization\Models\OrganizationSetting;
use App\Modules\Sector\Models\OrganizationSector;
use App\Modules\Sector\Models\Sector;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrganizationService
{
    public function __construct(
        protected OrganizationBootstrapService $organizationBootstrapService,
        protected AuditService $auditService,
        protected OrganizationContext $organizationContext,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $sectorIds
     */
    public function register(array $data, ?User $creator = null, array $sectorIds = []): Organization
    {
        return DB::transaction(function () use ($data, $creator, $sectorIds) {
            $organization = Organization::query()->create([
                'name' => $data['name'],
                'code' => $data['code'],
                'registration_number' => $data['registration_number'] ?? null,
                'type' => $data['type'],
                'description' => $data['description'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'website' => $data['website'] ?? null,
                'country' => $data['country'] ?? null,
                'address' => $data['address'] ?? null,
                'status' => $data['status'] ?? OrganizationStatus::Active->value,
            ]);

            $this->seedDefaultSettings($organization);

            if ($sectorIds !== []) {
                $this->syncSectors($organization, $sectorIds);
            }

            $this->organizationBootstrapService->bootstrapRolesForOrganization($organization);

            if ($creator) {
                $organization->users()->attach($creator->id, [
                    'status' => MembershipStatus::Active->value,
                    'is_default' => true,
                    'joined_at' => now(),
                ]);

                setPermissionsTeamId($organization->id);
                $creator->assignRole(Permissions::SYSTEM_ADMINISTRATOR_ROLE);
            }

            $this->organizationContext->set($organization);
            $this->auditService->log(
                AuditAction::Created,
                'organization',
                Organization::class,
                $organization->id,
                null,
                $organization->only([
                    'name', 'code', 'type', 'status', 'email', 'phone', 'country',
                ]),
                $creator,
            );

            return $organization->load(['sectors', 'settings']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Organization $organization, array $data): Organization
    {
        $old = $organization->only(array_keys($data));
        $organization->fill($data)->save();

        $this->auditService->log(
            AuditAction::Updated,
            'organization',
            Organization::class,
            $organization->id,
            $old,
            $organization->only(array_keys($data)),
        );

        return $organization->fresh();
    }

    public function activate(Organization $organization): Organization
    {
        return $this->setStatus($organization, OrganizationStatus::Active);
    }

    public function deactivate(Organization $organization): Organization
    {
        return $this->setStatus($organization, OrganizationStatus::Inactive);
    }

    public function uploadLogo(Organization $organization, UploadedFile $file): Organization
    {
        if (! $file->isValid() || ! str_starts_with((string) $file->getMimeType(), 'image/')) {
            throw ValidationException::withMessages([
                'logo' => ['The logo must be a valid image file.'],
            ]);
        }

        $path = $file->store('organizations/'.$organization->id.'/logos', 'public');
        $organization->logo_path = $path;
        $organization->save();

        $this->auditService->log(
            AuditAction::Updated,
            'organization',
            Organization::class,
            $organization->id,
            null,
            ['logo_path' => $path],
        );

        return $organization;
    }

    /**
     * @param  list<int>  $sectorIds
     */
    public function syncSectors(Organization $organization, array $sectorIds): Organization
    {
        $validIds = Sector::query()->whereIn('id', $sectorIds)->pluck('id')->all();

        OrganizationSector::query()
            ->where('organization_id', $organization->id)
            ->whereNotIn('sector_id', $validIds)
            ->delete();

        foreach ($validIds as $index => $sectorId) {
            OrganizationSector::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'sector_id' => $sectorId,
                ],
                [
                    'is_primary' => $index === 0,
                    'status' => 'active',
                ],
            );
        }

        $organization->sectors()->sync(
            collect($validIds)->mapWithKeys(fn ($id, $index) => [
                $id => ['is_primary' => $index === 0, 'status' => 'active'],
            ])->all(),
        );

        return $organization->load('sectors');
    }

    protected function seedDefaultSettings(Organization $organization): void
    {
        $defaults = [
            'timezone' => 'UTC',
            'locale' => 'en',
            'currency' => 'USD',
        ];

        foreach ($defaults as $key => $value) {
            OrganizationSetting::query()->create([
                'organization_id' => $organization->id,
                'key' => $key,
                'value' => $value,
            ]);
        }
    }

    protected function setStatus(Organization $organization, OrganizationStatus $status): Organization
    {
        $old = $organization->status;
        $organization->status = $status->value;
        $organization->save();

        $this->auditService->log(
            $status === OrganizationStatus::Active ? AuditAction::Activated : AuditAction::Deactivated,
            'organization',
            Organization::class,
            $organization->id,
            ['status' => $old],
            ['status' => $organization->status],
        );

        return $organization;
    }
}
