<?php

namespace App\Modules\Knowledge\Controllers\Api;

use App\Modules\Knowledge\Models\KnowledgeDocument;
use App\Modules\Knowledge\Resources\KnowledgeDocumentResource;
use App\Modules\Knowledge\Services\KnowledgeService;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KnowledgeDocumentController extends Controller
{
    public function __construct(
        protected KnowledgeService $knowledgeService,
        protected OrganizationContext $organizationContext,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', KnowledgeDocument::class);
        $query = KnowledgeDocument::query()->where('organization_id', $this->organizationContext->id());

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($category = $request->string('category')->toString()) {
            $query->where('category', $category);
        }
        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return ApiResponse::success(KnowledgeDocumentResource::collection(
            $query->latest()->paginate($request->integer('per_page', 15)),
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', KnowledgeDocument::class);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'document_type' => ['nullable', 'string', 'max:100'],
            'tags' => ['nullable', 'array'],
            'keywords' => ['nullable', 'array'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'programme_id' => ['nullable', 'integer', 'exists:programmes,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'access_level' => ['nullable', 'in:public,internal,restricted'],
            'published_on' => ['nullable', 'date'],
            'ai_summary' => ['nullable', 'string'],
            'ai_assisted' => ['sometimes', 'boolean'],
            'file' => ['nullable', 'file', 'max:10240'],
        ]);

        $document = $this->knowledgeService->create($data, $request->file('file'));

        return ApiResponse::success(new KnowledgeDocumentResource($document), 'Knowledge document created.', status: 201);
    }

    public function show(KnowledgeDocument $knowledgeDocument): JsonResponse
    {
        $this->authorize('view', $knowledgeDocument);

        return ApiResponse::success(new KnowledgeDocumentResource($knowledgeDocument->load(['project', 'programme', 'versions'])));
    }

    public function update(Request $request, KnowledgeDocument $knowledgeDocument): JsonResponse
    {
        $this->authorize('update', $knowledgeDocument);
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'document_type' => ['nullable', 'string', 'max:100'],
            'tags' => ['nullable', 'array'],
            'keywords' => ['nullable', 'array'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'programme_id' => ['nullable', 'integer', 'exists:programmes,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'access_level' => ['nullable', 'in:public,internal,restricted'],
            'published_on' => ['nullable', 'date'],
            'ai_summary' => ['nullable', 'string'],
            'ai_assisted' => ['sometimes', 'boolean'],
        ]);

        return ApiResponse::success(new KnowledgeDocumentResource($this->knowledgeService->update($knowledgeDocument, $data)), 'Document updated.');
    }

    public function upload(Request $request, KnowledgeDocument $knowledgeDocument): JsonResponse
    {
        $this->authorize('update', $knowledgeDocument);
        $request->validate(['file' => ['required', 'file', 'max:10240'], 'title' => ['nullable', 'string', 'max:255']]);

        return ApiResponse::success(
            new KnowledgeDocumentResource($this->knowledgeService->uploadNewVersion($knowledgeDocument, $request->file('file'), $request->string('title')->toString() ?: null)),
            'New version uploaded.',
            status: 201,
        );
    }

    public function publish(KnowledgeDocument $knowledgeDocument): JsonResponse
    {
        $this->authorize('manage', $knowledgeDocument);

        return ApiResponse::success(new KnowledgeDocumentResource($this->knowledgeService->publish($knowledgeDocument)), 'Document published.');
    }

    public function archive(KnowledgeDocument $knowledgeDocument): JsonResponse
    {
        $this->authorize('manage', $knowledgeDocument);

        return ApiResponse::success(new KnowledgeDocumentResource($this->knowledgeService->archive($knowledgeDocument)), 'Document archived.');
    }

    public function download(KnowledgeDocument $knowledgeDocument): StreamedResponse
    {
        $this->authorize('download', $knowledgeDocument);

        return $this->knowledgeService->download($knowledgeDocument);
    }
}
