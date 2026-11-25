<?php

namespace App\Modules\ReportingCalendar\Controllers\Api;

use App\Modules\ReportingCalendar\Models\ReportingCalendar;
use App\Modules\ReportingCalendar\Models\ReportingPeriod;
use App\Modules\ReportingCalendar\Requests\StoreReportingCalendarRequest;
use App\Modules\ReportingCalendar\Requests\StoreReportingPeriodRequest;
use App\Modules\ReportingCalendar\Requests\UpdateReportingCalendarRequest;
use App\Modules\ReportingCalendar\Resources\ReportingCalendarResource;
use App\Modules\ReportingCalendar\Resources\ReportingPeriodResource;
use App\Modules\ReportingCalendar\Services\ReportingCalendarService;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportingCalendarController extends Controller
{
    public function __construct(
        protected ReportingCalendarService $reportingCalendarService,
        protected OrganizationContext $organizationContext,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', ReportingCalendar::class);

        return ApiResponse::success(ReportingCalendarResource::collection(
            ReportingCalendar::query()
                ->where('organization_id', $this->organizationContext->id())
                ->with('periods')
                ->paginate(),
        ));
    }

    public function compliance(): JsonResponse
    {
        $this->authorize('viewAny', ReportingCalendar::class);

        return ApiResponse::success($this->reportingCalendarService->complianceOverview());
    }

    public function store(StoreReportingCalendarRequest $request): JsonResponse
    {
        $this->authorize('create', ReportingCalendar::class);

        $calendar = $this->reportingCalendarService->create(
            $request->safe()->except('periods'),
            $request->validated('periods', []),
        );

        return ApiResponse::success(new ReportingCalendarResource($calendar), status: 201);
    }

    public function show(ReportingCalendar $reportingCalendar): JsonResponse
    {
        $this->authorize('view', $reportingCalendar);

        return ApiResponse::success(new ReportingCalendarResource($reportingCalendar->load('periods')));
    }

    public function update(UpdateReportingCalendarRequest $request, ReportingCalendar $reportingCalendar): JsonResponse
    {
        $this->authorize('update', $reportingCalendar);

        return ApiResponse::success(new ReportingCalendarResource(
            $this->reportingCalendarService->updateCalendar($reportingCalendar, $request->validated()),
        ));
    }

    public function storePeriod(StoreReportingPeriodRequest $request, ReportingCalendar $reportingCalendar): JsonResponse
    {
        $this->authorize('manage', $reportingCalendar);

        $period = $this->reportingCalendarService->createPeriod($reportingCalendar, $request->validated());

        return ApiResponse::success(new ReportingPeriodResource($period), status: 201);
    }

    public function periods(ReportingCalendar $reportingCalendar): JsonResponse
    {
        $this->authorize('view', $reportingCalendar);

        return ApiResponse::success(ReportingPeriodResource::collection($reportingCalendar->periods));
    }

    public function openPeriod(ReportingPeriod $reportingPeriod): JsonResponse
    {
        $reportingPeriod->loadMissing('reportingCalendar');
        $this->authorize('manage', $reportingPeriod->reportingCalendar);

        return ApiResponse::success(
            new ReportingPeriodResource($this->reportingCalendarService->openPeriod($reportingPeriod)),
            'Reporting period opened.',
        );
    }

    public function closePeriod(Request $request, ReportingPeriod $reportingPeriod): JsonResponse
    {
        $reportingPeriod->loadMissing('reportingCalendar');
        $this->authorize('manage', $reportingPeriod->reportingCalendar);

        $data = $request->validate([
            'closure_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        return ApiResponse::success(
            new ReportingPeriodResource(
                $this->reportingCalendarService->closePeriod($reportingPeriod, $data['closure_notes'] ?? null),
            ),
            'Reporting period closed.',
        );
    }
}
