<?php

namespace App\Modules\ResultsFramework\Controllers\Api;

use App\Modules\Indicator\Resources\IndicatorResource;
use App\Modules\ResultsFramework\Models\Result;
use App\Modules\ResultsFramework\Requests\LinkIndicatorToResultRequest;
use App\Modules\ResultsFramework\Requests\UpdateResultRequest;
use App\Modules\ResultsFramework\Resources\ResultResource;
use App\Modules\ResultsFramework\Services\ResultsFrameworkService;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ResultController extends Controller
{
    public function __construct(
        protected ResultsFrameworkService $resultsFrameworkService,
    ) {}

    public function update(UpdateResultRequest $request, Result $result): JsonResponse
    {
        $this->authorize('update', $result);

        $updated = $this->resultsFrameworkService->updateResult($result, $request->validated());

        return ApiResponse::success(new ResultResource($updated), 'Result updated.');
    }

    public function linkIndicator(LinkIndicatorToResultRequest $request, Result $result): JsonResponse
    {
        $this->authorize('linkIndicator', $result);

        $indicator = $this->resultsFrameworkService->linkIndicator($result, (int) $request->validated('indicator_id'));

        return ApiResponse::success(new IndicatorResource($indicator), 'Indicator linked to result.');
    }
}
