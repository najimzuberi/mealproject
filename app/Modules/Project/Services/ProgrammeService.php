<?php

namespace App\Modules\Project\Services;

use App\Enums\AuditAction;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Documents\Models\Document;
use App\Modules\Project\Models\Programme;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProgrammeService
{
    public function __construct(
        protected AuditService $auditService,
        protected OrganizationContext $organizationContext,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Programme
    {
        return DB::transaction(function () use ($data) {
            $team = $data['team_members'] ?? [];
            $geoIds = $data['geographic_unit_ids'] ?? [];
            $stakeholders = $data['stakeholders'] ?? [];
            unset($data['team_members'], $data['geographic_unit_ids'], $data['stakeholders']);

            $programme = Programme::query()->create([
                ...$data,
                'organization_id' => $this->organizationContext->id(),
                'status' => $data['status'] ?? 'active',
                'created_by' => Auth::id(),
            ]);

            $this->syncTeam($programme, $team);
            if ($geoIds !== []) {
                $programme->geographicUnits()->sync($geoIds);
            }
            $this->syncStakeholders($programme, $stakeholders);

            $this->auditService->log(AuditAction::Created, 'projects', Programme::class, $programme->id);

            return $this->load($programme);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Programme $programme, array $data): Programme
    {
        $this->assertOrg($programme);

        return DB::transaction(function () use ($programme, $data) {
            $team = $data['team_members'] ?? null;
            $geoIds = $data['geographic_unit_ids'] ?? null;
            $stakeholders = $data['stakeholders'] ?? null;
            unset($data['team_members'], $data['geographic_unit_ids'], $data['stakeholders']);

            if ($data !== []) {
                $programme->fill($data)->save();
            }

            if ($team !== null) {
                $this->syncTeam($programme, $team);
            }

            if ($geoIds !== null) {
                $programme->geographicUnits()->sync($geoIds);
            }

            if ($stakeholders !== null) {
                $programme->stakeholders()->delete();
                $this->syncStakeholders($programme, $stakeholders);
            }

            $this->auditService->log(AuditAction::Updated, 'projects', Programme::class, $programme->id, null, $data);

            return $this->load($programme->fresh());
        });
    }

    public function delete(Programme $programme): void
    {
        $this->assertOrg($programme);

        if ($programme->projects()->exists()) {
            abort(422, 'Cannot delete a programme that has associated projects.');
        }

        $programme->delete();

        $this->auditService->log(AuditAction::Deleted, 'projects', Programme::class, $programme->id);
    }

    /**
     * @param  list<array{user_id: int, role_label?: string|null}>  $members
     */
    public function syncTeam(Programme $programme, array $members): void
    {
        $sync = [];
        foreach ($members as $member) {
            $sync[(int) $member['user_id']] = ['role_label' => $member['role_label'] ?? null];
        }
        $programme->teamMembers()->sync($sync);
    }

    /**
     * @param  list<array<string, mixed>>  $stakeholders
     */
    public function syncStakeholders(Programme $programme, array $stakeholders): void
    {
        foreach ($stakeholders as $stakeholder) {
            $programme->stakeholders()->create([
                'organization_id' => $programme->organization_id,
                'name' => $stakeholder['name'],
                'type' => $stakeholder['type'] ?? null,
                'role' => $stakeholder['role'] ?? null,
                'contact_email' => $stakeholder['contact_email'] ?? null,
                'contact_phone' => $stakeholder['contact_phone'] ?? null,
                'notes' => $stakeholder['notes'] ?? null,
            ]);
        }
    }

    public function storeDocument(Programme $programme, UploadedFile $file, ?string $title = null): Document
    {
        $this->assertOrg($programme);

        $path = $file->store("programmes/{$programme->id}", 'public');

        $document = $programme->documents()->create([
            'organization_id' => $programme->organization_id,
            'title' => $title ?: $file->getClientOriginalName(),
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => Auth::id(),
        ]);

        $this->auditService->log(AuditAction::Created, 'projects', Document::class, $document->id);

        return $document;
    }

    public function deleteDocument(Programme $programme, Document $document): void
    {
        $this->assertOrg($programme);

        if ($document->documentable_type !== Programme::class || (int) $document->documentable_id !== (int) $programme->id) {
            abort(404);
        }

        Storage::disk($document->disk)->delete($document->path);
        $document->delete();
    }

    public function load(Programme $programme): Programme
    {
        return $programme->load([
            'projects',
            'sector',
            'department',
            'programmeManager',
            'strategicPlan',
            'teamMembers',
            'geographicUnits',
            'documents',
            'stakeholders',
        ]);
    }

    protected function assertOrg(Programme $programme): void
    {
        if ($programme->organization_id !== $this->organizationContext->id()) {
            abort(404);
        }
    }
}
