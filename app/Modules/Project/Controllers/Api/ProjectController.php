<?php

namespace App\Modules\Project\Controllers\Api;

use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Resources\DocumentResource;
use App\Modules\Project\Models\Project;
use App\Modules\Project\Requests\ReturnProjectRequest;
use App\Modules\Project\Requests\StoreProgrammeDocumentRequest;
use App\Modules\Project\Requests\StoreProjectRequest;
use App\Modules\Project\Requests\SyncProjectGeographicUnitsRequest;
use App\Modules\Project\Requests\SyncProjectTeamRequest;
use App\Modules\Project\Requests\UpdateProjectRequest;
use App\Modules\Project\Resources\ProjectResource;
use App\Modules\Project\Services\ProjectService;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use App\Shared\Requests\WorkflowCommentRequest;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(
        protected ProjectService $projectService,
        protected OrganizationContext $organizationContext,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Project::class);

        $projects = Project::query()
            ->where('organization_id', $this->organizationContext->id())
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->when($request->integer('programme_id'), fn ($query, $programmeId) => $query->where('programme_id', $programmeId))
            ->when($request->string('search')->toString(), function ($query, $search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->with(['activities.milestones', 'assumptions', 'risks', 'programme'])
            ->latest('updated_at')
            ->paginate();

        return ApiResponse::success(ProjectResource::collection($projects));
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $this->authorize('create', Project::class);

        $project = $this->projectService->create($request->validated());

        return ApiResponse::success(new ProjectResource($project), status: 201);
    }

    public function show(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        return ApiResponse::success(
            new ProjectResource($this->projectService->loadFullStructure($project)),
        );
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        return ApiResponse::success(new ProjectResource(
            $this->projectService->update($project, $request->validated()),
        ));
    }

    public function destroy(Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        $this->projectService->delete($project);

        return ApiResponse::success(null, 'Project deleted.');
    }

    public function submit(Project $project): JsonResponse
    {
        $this->authorize('submit', $project);

        return ApiResponse::success(
            new ProjectResource($this->projectService->submit($project)),
            'Project submitted for review.',
        );
    }

    public function returnForCorrection(ReturnProjectRequest $request, Project $project): JsonResponse
    {
        $this->authorize('review', $project);

        return ApiResponse::success(
            new ProjectResource($this->projectService->returnForCorrection(
                $project,
                (string) $request->validated('comment'),
            )),
            'Project returned for correction.',
        );
    }

    public function approve(WorkflowCommentRequest $request, Project $project): JsonResponse
    {
        $this->authorize('approve', $project);

        return ApiResponse::success(
            new ProjectResource($this->projectService->approve($project, $request->validated('comment'))),
            'Project approved.',
        );
    }

    public function activate(Project $project): JsonResponse
    {
        $this->authorize('activate', $project);

        return ApiResponse::success(
            new ProjectResource($this->projectService->activate($project)),
            'Project activated.',
        );
    }

    public function archive(Project $project): JsonResponse
    {
        $this->authorize('archive', $project);

        return ApiResponse::success(
            new ProjectResource($this->projectService->archive($project)),
            'Project archived.',
        );
    }

    public function storeDocument(StoreProgrammeDocumentRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $document = $this->projectService->storeDocument(
            $project,
            $request->file('file'),
            $request->validated('title'),
        );

        return ApiResponse::success(new DocumentResource($document), status: 201);
    }

    public function destroyDocument(Project $project, Document $document): JsonResponse
    {
        $this->authorize('update', $project);

        $this->projectService->deleteDocument($project, $document);

        return ApiResponse::success(null, 'Document deleted.');
    }

    public function syncTeam(SyncProjectTeamRequest $request, Project $project): JsonResponse
    {
        $this->authorize('manageTeam', $project);

        return ApiResponse::success(
            new ProjectResource($this->projectService->syncTeam($project, $request->members())),
            'Project team updated.',
        );
    }

    public function syncGeographicUnits(SyncProjectGeographicUnitsRequest $request, Project $project): JsonResponse
    {
        $this->authorize('manageTeam', $project);

        return ApiResponse::success(
            new ProjectResource($this->projectService->syncGeographicUnits(
                $project,
                $request->validated('geographic_unit_ids', []),
            )),
            'Project geographic units updated.',
        );
    }
}
