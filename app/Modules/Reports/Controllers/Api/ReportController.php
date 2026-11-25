<?php

namespace App\Modules\Reports\Controllers\Api;

use App\Enums\ReportType;
use App\Modules\Reports\Models\Report;
use App\Modules\Reports\Resources\ReportResource;
use App\Modules\Reports\Services\ReportService;
use App\Modules\StrategicPlanning\Requests\WorkflowCommentRequest;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use App\Shared\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService,
        protected OrganizationContext $organizationContext,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Report::class);

        $query = Report::query()
            ->with(['project', 'reportingPeriod'])
            ->where('organization_id', $this->organizationContext->id());

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($type = $request->string('report_type')->toString()) {
            $query->where('report_type', $type);
        }

        if ($projectId = $request->integer('project_id')) {
            $query->where('project_id', $projectId);
        }

        return ApiResponse::success(ReportResource::collection(
            $query->latest()->paginate($request->integer('per_page', 15)),
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Report::class);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'report_type' => ['required', Rule::enum(ReportType::class)],
            'reference_number' => ['nullable', 'string', 'max:50'],
            'programme_id' => ['nullable', 'integer', 'exists:programmes,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'reporting_period_id' => ['nullable', 'integer', 'exists:reporting_periods,id'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'executive_summary' => ['nullable', 'string'],
            'performance_summary' => ['nullable', 'string'],
            'challenges' => ['nullable', 'string'],
            'risks' => ['nullable', 'string'],
            'recommendations' => ['nullable', 'string'],
            'ai_assisted' => ['sometimes', 'boolean'],
            'indicator_id' => ['nullable', 'integer', 'exists:indicators,id'],
        ]);

        return ApiResponse::success(
            new ReportResource($this->reportService->generate($data)),
            'Report draft generated from approved published data.',
            status: 201,
        );
    }

    public function show(Report $report): JsonResponse
    {
        $this->authorize('view', $report);

        if ($report->organization_id !== $this->organizationContext->id()) {
            abort(404);
        }

        return ApiResponse::success(new ReportResource(
            $report->load(['indicatorResults', 'project', 'programme', 'reportingPeriod', 'approvalHistories', 'preparer']),
        ));
    }

    public function update(Request $request, Report $report): JsonResponse
    {
        $this->authorize('update', $report);

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'executive_summary' => ['nullable', 'string'],
            'performance_summary' => ['nullable', 'string'],
            'challenges' => ['nullable', 'string'],
            'risks' => ['nullable', 'string'],
            'recommendations' => ['nullable', 'string'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'reviewed_by' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        return ApiResponse::success(
            new ReportResource($this->reportService->update($report, $data)),
            'Report updated.',
        );
    }

    public function submit(WorkflowCommentRequest $request, Report $report): JsonResponse
    {
        $this->authorize('submit', $report);

        return ApiResponse::success(
            new ReportResource($this->reportService->submit($report, $request->validated('comment'))),
            'Report submitted for review.',
        );
    }

    public function returnReport(WorkflowCommentRequest $request, Report $report): JsonResponse
    {
        $this->authorize('approve', $report);

        return ApiResponse::success(
            new ReportResource($this->reportService->returnForCorrection($report, $request->validated('comment'))),
            'Report returned.',
        );
    }

    public function approve(WorkflowCommentRequest $request, Report $report): JsonResponse
    {
        $this->authorize('approve', $report);

        return ApiResponse::success(
            new ReportResource($this->reportService->approve($report, $request->validated('comment'))),
            'Report approved.',
        );
    }

    public function publish(WorkflowCommentRequest $request, Report $report): JsonResponse
    {
        $this->authorize('approve', $report);

        return ApiResponse::success(
            new ReportResource($this->reportService->publish($report, $request->validated('comment'))),
            'Report published.',
        );
    }

    public function exportPdf(Report $report): StreamedResponse
    {
        $this->authorize('export', $report);

        $content = $this->reportService->exportPdf($report);
        $filename = $report->reference_number.'.pdf';

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function exportExcel(Report $report): StreamedResponse
    {
        $this->authorize('export', $report);

        $content = $this->reportService->exportExcel($report);
        $filename = $report->reference_number.'.csv';

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
