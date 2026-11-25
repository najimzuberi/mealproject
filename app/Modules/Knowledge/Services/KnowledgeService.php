<?php

namespace App\Modules\Knowledge\Services;

use App\Enums\AuditAction;
use App\Enums\KnowledgeDocumentStatus;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Knowledge\Models\KnowledgeDocument;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KnowledgeService
{
    /** @var list<string> */
    protected array $allowedMimes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
        'text/csv',
        'image/png',
        'image/jpeg',
    ];

    public function __construct(
        protected OrganizationContext $organizationContext,
        protected AuditService $auditService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $file = null): KnowledgeDocument
    {
        return DB::transaction(function () use ($data, $file) {
            $document = KnowledgeDocument::query()->create([
                'organization_id' => $this->organizationContext->id(),
                'code' => $data['code'],
                'title' => $data['title'],
                'category' => $data['category'] ?? null,
                'description' => $data['description'] ?? null,
                'document_type' => $data['document_type'] ?? null,
                'tags' => $data['tags'] ?? [],
                'keywords' => $data['keywords'] ?? [],
                'project_id' => $data['project_id'] ?? null,
                'programme_id' => $data['programme_id'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'author_id' => $data['author_id'] ?? Auth::id(),
                'published_on' => $data['published_on'] ?? null,
                'version' => 1,
                'access_level' => $data['access_level'] ?? 'internal',
                'status' => KnowledgeDocumentStatus::Draft->value,
                'ai_summary' => $data['ai_summary'] ?? null,
                'ai_assisted' => (bool) ($data['ai_assisted'] ?? false),
            ]);

            if ($file) {
                $this->attachFile($document, $file);
            }

            $this->auditService->log(AuditAction::Created, 'knowledge', KnowledgeDocument::class, $document->id);

            return $document->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(KnowledgeDocument $document, array $data): KnowledgeDocument
    {
        $this->assertOrg($document);

        $document->fill(collect($data)->only([
            'title', 'category', 'description', 'document_type', 'tags', 'keywords',
            'project_id', 'programme_id', 'department_id', 'published_on', 'access_level',
            'ai_summary', 'ai_assisted',
        ])->all())->save();

        $this->auditService->log(AuditAction::Updated, 'knowledge', KnowledgeDocument::class, $document->id);

        return $document->fresh();
    }

    public function uploadNewVersion(KnowledgeDocument $document, UploadedFile $file, ?string $title = null): KnowledgeDocument
    {
        $this->assertOrg($document);

        return DB::transaction(function () use ($document, $file, $title) {
            $rootId = $document->parent_document_id ?? $document->id;
            $version = KnowledgeDocument::query()
                ->where('organization_id', $document->organization_id)
                ->where(function ($q) use ($rootId) {
                    $q->where('id', $rootId)->orWhere('parent_document_id', $rootId);
                })
                ->max('version') + 1;

            $new = KnowledgeDocument::query()->create([
                ...$document->only([
                    'organization_id', 'code', 'category', 'description', 'document_type', 'tags',
                    'keywords', 'project_id', 'programme_id', 'department_id', 'access_level',
                ]),
                'title' => $title ?? $document->title,
                'author_id' => Auth::id(),
                'version' => $version,
                'parent_document_id' => $rootId,
                'status' => KnowledgeDocumentStatus::Draft->value,
            ]);

            $this->attachFile($new, $file);
            $this->auditService->log(AuditAction::Created, 'knowledge', KnowledgeDocument::class, $new->id, null, [
                'versioned_from' => $document->id,
            ]);

            return $new->fresh();
        });
    }

    public function publish(KnowledgeDocument $document): KnowledgeDocument
    {
        $this->assertOrg($document);
        $document->status = KnowledgeDocumentStatus::Published->value;
        $document->published_on = $document->published_on ?? now()->toDateString();
        $document->save();

        $this->auditService->log(AuditAction::Updated, 'knowledge', KnowledgeDocument::class, $document->id, null, [
            'status' => KnowledgeDocumentStatus::Published->value,
        ]);

        return $document->fresh();
    }

    public function archive(KnowledgeDocument $document): KnowledgeDocument
    {
        $this->assertOrg($document);
        $document->status = KnowledgeDocumentStatus::Archived->value;
        $document->save();

        $this->auditService->log(AuditAction::Updated, 'knowledge', KnowledgeDocument::class, $document->id, null, [
            'status' => KnowledgeDocumentStatus::Archived->value,
        ]);

        return $document->fresh();
    }

    public function download(KnowledgeDocument $document): StreamedResponse
    {
        $this->assertOrg($document);

        if (! $document->path || ! $document->disk) {
            throw ValidationException::withMessages(['file' => ['No file attached.']]);
        }

        $this->auditService->log(AuditAction::Updated, 'knowledge', KnowledgeDocument::class, $document->id, null, [
            'action' => 'download',
        ]);

        return Storage::disk($document->disk)->download($document->path, $document->original_name ?? $document->title);
    }

    protected function attachFile(KnowledgeDocument $document, UploadedFile $file): void
    {
        if ($file->getSize() > 10 * 1024 * 1024) {
            throw ValidationException::withMessages(['file' => ['File must be 10MB or smaller.']]);
        }

        $mime = $file->getMimeType() ?? '';
        if (! in_array($mime, $this->allowedMimes, true)) {
            throw ValidationException::withMessages(['file' => ['File type is not allowed.']]);
        }

        $path = $file->store('knowledge/'.$document->organization_id.'/'.$document->id, 'local');

        $document->fill([
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $mime,
            'size' => $file->getSize(),
        ])->save();
    }

    protected function assertOrg(KnowledgeDocument $document): void
    {
        if ($document->organization_id !== $this->organizationContext->id()) {
            abort(404);
        }
    }
}
