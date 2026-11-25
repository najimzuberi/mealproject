<?php

namespace App\Modules\Organization\Controllers\Api;

use App\Modules\Organization\Models\Team;
use App\Modules\Organization\Resources\TeamResource;
use App\Modules\Organization\Services\TeamService;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function __construct(
        protected TeamService $teamService,
        protected OrganizationContext $organizationContext,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Team::class);

        $query = Team::query()
            ->where('organization_id', $this->organizationContext->id())
            ->with(['members', 'leader']);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return ApiResponse::success(TeamResource::collection($query->paginate()));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Team::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'office_id' => ['nullable', 'integer', 'exists:offices,id'],
            'leader_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', 'string'],
        ]);

        return ApiResponse::success(new TeamResource($this->teamService->create($data)), status: 201);
    }

    public function show(Team $team): JsonResponse
    {
        $this->authorize('view', $team);

        return ApiResponse::success(new TeamResource($team->load(['members', 'leader', 'department', 'office'])));
    }

    public function update(Request $request, Team $team): JsonResponse
    {
        $this->authorize('update', $team);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'office_id' => ['nullable', 'integer', 'exists:offices,id'],
            'leader_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', 'string'],
        ]);

        return ApiResponse::success(new TeamResource($this->teamService->update($team, $data)));
    }

    public function destroy(Team $team): JsonResponse
    {
        $this->authorize('delete', $team);
        $this->teamService->delete($team);

        return ApiResponse::success(null, 'Team deleted.');
    }

    public function syncMembers(Request $request, Team $team): JsonResponse
    {
        $this->authorize('update', $team);

        $data = $request->validate([
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        return ApiResponse::success(new TeamResource(
            $this->teamService->syncMembers($team, $data['user_ids']),
        ));
    }
}
