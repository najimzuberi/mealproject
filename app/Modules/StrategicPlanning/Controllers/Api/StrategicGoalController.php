<?php

namespace App\Modules\StrategicPlanning\Controllers\Api;

use App\Modules\StrategicPlanning\Models\StrategicGoal;
use App\Modules\StrategicPlanning\Models\StrategicObjective;
use App\Modules\StrategicPlanning\Models\StrategicPlan;
use App\Modules\StrategicPlanning\Requests\StoreStrategicGoalRequest;
use App\Modules\StrategicPlanning\Requests\StoreStrategicObjectiveRequest;
use App\Modules\StrategicPlanning\Requests\UpdateStrategicGoalRequest;
use App\Modules\StrategicPlanning\Requests\UpdateStrategicObjectiveRequest;
use App\Modules\StrategicPlanning\Resources\StrategicGoalResource;
use App\Modules\StrategicPlanning\Resources\StrategicObjectiveResource;
use App\Modules\StrategicPlanning\Services\StrategicGoalService;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class StrategicGoalController extends Controller
{
    public function __construct(
        protected StrategicGoalService $strategicGoalService,
    ) {}

    public function index(StrategicPlan $strategicPlan): JsonResponse
    {
        $this->authorize('view', $strategicPlan);

        $goals = $this->strategicGoalService->listForPlan($strategicPlan);

        return ApiResponse::success(StrategicGoalResource::collection($goals));
    }

    public function store(StoreStrategicGoalRequest $request, StrategicPlan $strategicPlan): JsonResponse
    {
        $this->authorize('update', $strategicPlan);

        $goal = $this->strategicGoalService->createGoal($strategicPlan, $request->validated());

        return ApiResponse::success(new StrategicGoalResource($goal), status: 201);
    }

    public function show(StrategicGoal $goal): JsonResponse
    {
        $this->authorize('view', $goal->strategicPlan);

        return ApiResponse::success(new StrategicGoalResource(
            $this->strategicGoalService->loadGoal($goal),
        ));
    }

    public function update(UpdateStrategicGoalRequest $request, StrategicGoal $goal): JsonResponse
    {
        $this->authorize('update', $goal->strategicPlan);

        return ApiResponse::success(new StrategicGoalResource(
            $this->strategicGoalService->updateGoal($goal, $request->validated()),
        ));
    }

    public function destroy(StrategicPlan $strategicPlan, StrategicGoal $goal): JsonResponse
    {
        $this->authorize('update', $strategicPlan);
        $this->strategicGoalService->deleteGoal($strategicPlan, $goal);

        return ApiResponse::success(null, 'Goal deleted.');
    }

    public function storeObjective(StoreStrategicObjectiveRequest $request, StrategicGoal $goal): JsonResponse
    {
        $this->authorize('update', $goal->strategicPlan);

        $objective = $this->strategicGoalService->createObjective($goal, $request->validated());

        return ApiResponse::success(new StrategicObjectiveResource($objective), status: 201);
    }

    public function updateObjective(UpdateStrategicObjectiveRequest $request, StrategicObjective $objective): JsonResponse
    {
        $this->authorize('update', $objective->strategicGoal->strategicPlan);

        return ApiResponse::success(new StrategicObjectiveResource(
            $this->strategicGoalService->updateObjective($objective, $request->validated()),
        ));
    }

    public function showObjective(StrategicObjective $objective): JsonResponse
    {
        $this->authorize('view', $objective->strategicGoal->strategicPlan);

        return ApiResponse::success(new StrategicObjectiveResource(
            $this->strategicGoalService->loadObjective($objective),
        ));
    }
}
