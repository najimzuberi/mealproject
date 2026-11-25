<?php

namespace App\Modules\Performance\Controllers\Api;

use App\Modules\Performance\Services\PerformanceAggregationService;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PerformanceController extends Controller
{
    public function __construct(
        protected PerformanceAggregationService $performanceAggregationService,
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $this->authorize('viewPerformance');

        return ApiResponse::success($this->performanceAggregationService->dashboard($request));
    }

    public function indicatorResults(Request $request): JsonResponse
    {
        $this->authorize('viewPerformance');

        $filters = [
            'programme_id' => $request->integer('programme_id') ?: null,
            'project_id' => $request->integer('project_id') ?: null,
            'reporting_period_id' => $request->integer('reporting_period_id') ?: null,
            'indicator_id' => $request->integer('indicator_id') ?: null,
            'department_id' => null,
            'office_id' => null,
            'facility_id' => null,
            'geographic_unit_id' => null,
            'sector_id' => null,
            'granularity' => $request->string('granularity', 'quarterly')->toString(),
        ];

        return ApiResponse::success([
            'items' => $this->performanceAggregationService->indicatorResults($filters),
        ]);
    }
}
