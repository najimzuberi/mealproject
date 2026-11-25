<?php

namespace App\Modules\ReportingCalendar\Services;

use App\Enums\AuditAction;
use App\Enums\RecordStatus;
use App\Enums\ReportingPeriodStatus;
use App\Modules\Audit\Services\AuditService;
use App\Modules\DataCollection\Models\ReportingAssignment;
use App\Modules\ReportingCalendar\Models\ReportingCalendar;
use App\Modules\ReportingCalendar\Models\ReportingPeriod;
use App\Shared\Support\OrganizationContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReportingCalendarService
{
    public function __construct(
        protected AuditService $auditService,
        protected OrganizationContext $organizationContext,
    ) {}

    /**
     * @param  array<string, mixed>  $calendarData
     * @param  list<array<string, mixed>>  $periods
     */
    public function create(array $calendarData, array $periods = []): ReportingCalendar
    {
        return DB::transaction(function () use ($calendarData, $periods) {
            $organizationId = $this->organizationContext->id();

            $calendar = ReportingCalendar::query()->create([
                ...$calendarData,
                'organization_id' => $organizationId,
                'status' => $calendarData['status'] ?? RecordStatus::Active->value,
            ]);

            foreach ($periods as $periodData) {
                $this->createPeriod($calendar, $periodData);
            }

            $this->auditService->log(AuditAction::Created, 'reporting_calendars', ReportingCalendar::class, $calendar->id);

            return $calendar->load('periods');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPeriod(ReportingCalendar $calendar, array $data): ReportingPeriod
    {
        $this->assertOrg($calendar);

        $startsOn = Carbon::parse($data['starts_on']);
        $endsOn = Carbon::parse($data['ends_on']);

        if ($endsOn->lt($startsOn)) {
            throw ValidationException::withMessages([
                'ends_on' => ['End date must be on or after the start date.'],
            ]);
        }

        $duplicateName = ReportingPeriod::query()
            ->where('reporting_calendar_id', $calendar->id)
            ->where('name', $data['name'])
            ->exists();

        if ($duplicateName) {
            throw ValidationException::withMessages([
                'name' => ['A period with this name already exists in the calendar.'],
            ]);
        }

        if (! $calendar->allow_overlapping_periods) {
            $overlap = ReportingPeriod::query()
                ->where('reporting_calendar_id', $calendar->id)
                ->where(function ($query) use ($startsOn, $endsOn) {
                    $query->whereBetween('starts_on', [$startsOn, $endsOn])
                        ->orWhereBetween('ends_on', [$startsOn, $endsOn])
                        ->orWhere(function ($q) use ($startsOn, $endsOn) {
                            $q->where('starts_on', '<=', $startsOn)->where('ends_on', '>=', $endsOn);
                        });
                })
                ->exists();

            if ($overlap) {
                throw ValidationException::withMessages([
                    'starts_on' => ['This period overlaps an existing period.'],
                ]);
            }
        }

        $period = ReportingPeriod::query()->create([
            'reporting_calendar_id' => $calendar->id,
            'organization_id' => $calendar->organization_id,
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'starts_on' => $startsOn,
            'ends_on' => $endsOn,
            'submission_deadline' => $data['submission_deadline'] ?? $endsOn->toDateString(),
            'status' => $data['status'] ?? ReportingPeriodStatus::Draft->value,
            'responsible_officer_id' => $data['responsible_officer_id'] ?? null,
            'reminder_days_before' => $data['reminder_days_before'] ?? 7,
        ]);

        $this->auditService->log(AuditAction::Created, 'reporting_calendars', ReportingPeriod::class, $period->id);

        return $period;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCalendar(ReportingCalendar $calendar, array $data): ReportingCalendar
    {
        $this->assertOrg($calendar);
        $calendar->fill($data)->save();
        $this->auditService->log(AuditAction::Updated, 'reporting_calendars', ReportingCalendar::class, $calendar->id, null, $data);

        return $calendar;
    }

    public function openPeriod(ReportingPeriod $period): ReportingPeriod
    {
        $period->loadMissing('reportingCalendar');
        $this->assertOrg($period->reportingCalendar);

        $period->status = ReportingPeriodStatus::Open->value;
        $period->opened_at = now();
        $period->closed_at = null;
        $period->save();

        $this->auditService->log(
            AuditAction::Updated,
            'reporting_calendars',
            ReportingPeriod::class,
            $period->id,
            null,
            ['status' => ReportingPeriodStatus::Open->value, 'opened_at' => $period->opened_at?->toIso8601String()],
        );

        return $period;
    }

    public function closePeriod(ReportingPeriod $period, ?string $closureNotes = null): ReportingPeriod
    {
        $period->loadMissing('reportingCalendar');
        $this->assertOrg($period->reportingCalendar);

        $period->status = ReportingPeriodStatus::Closed->value;
        $period->closed_at = now();
        if ($closureNotes !== null) {
            $period->closure_notes = $closureNotes;
        }
        $period->save();

        $this->auditService->log(
            AuditAction::Updated,
            'reporting_calendars',
            ReportingPeriod::class,
            $period->id,
            null,
            [
                'status' => ReportingPeriodStatus::Closed->value,
                'closed_at' => $period->closed_at?->toIso8601String(),
            ],
        );

        return $period;
    }

    /**
     * @return array<string, mixed>
     */
    public function complianceOverview(): array
    {
        $organizationId = $this->organizationContext->id();

        $openPeriods = ReportingPeriod::query()
            ->where('organization_id', $organizationId)
            ->where('status', ReportingPeriodStatus::Open->value)
            ->with('reportingCalendar:id,name,frequency')
            ->orderBy('ends_on')
            ->get();

        $overduePeriods = ReportingPeriod::query()
            ->where('organization_id', $organizationId)
            ->where('status', ReportingPeriodStatus::Open->value)
            ->whereNotNull('submission_deadline')
            ->whereDate('submission_deadline', '<', now()->toDateString())
            ->with('reportingCalendar:id,name')
            ->orderBy('submission_deadline')
            ->get();

        $assignmentsPending = ReportingAssignment::query()
            ->where('organization_id', $organizationId)
            ->whereIn('status', ['pending', 'submitted'])
            ->count();

        $assignmentsOverdue = ReportingAssignment::query()
            ->where('organization_id', $organizationId)
            ->where('status', 'pending')
            ->whereNotNull('due_on')
            ->whereDate('due_on', '<', now()->toDateString())
            ->count();

        $totalAssignments = ReportingAssignment::query()
            ->where('organization_id', $organizationId)
            ->count();

        $completedAssignments = ReportingAssignment::query()
            ->where('organization_id', $organizationId)
            ->whereIn('status', ['submitted', 'completed'])
            ->count();

        return [
            'open_periods' => $openPeriods->map(fn (ReportingPeriod $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'code' => $p->code,
                'starts_on' => $p->starts_on?->toDateString(),
                'ends_on' => $p->ends_on?->toDateString(),
                'submission_deadline' => $p->submission_deadline?->toDateString(),
                'calendar' => $p->reportingCalendar?->name,
                'frequency' => $p->reportingCalendar?->frequency,
            ])->all(),
            'overdue_periods' => $overduePeriods->map(fn (ReportingPeriod $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'submission_deadline' => $p->submission_deadline?->toDateString(),
                'calendar' => $p->reportingCalendar?->name,
            ])->all(),
            'assignments' => [
                'total' => $totalAssignments,
                'pending' => $assignmentsPending,
                'overdue' => $assignmentsOverdue,
                'completed' => $completedAssignments,
                'completion_percent' => $totalAssignments > 0
                    ? round(($completedAssignments / $totalAssignments) * 100, 1)
                    : 0,
            ],
            'upcoming_deadlines' => ReportingPeriod::query()
                ->where('organization_id', $organizationId)
                ->where('status', ReportingPeriodStatus::Open->value)
                ->whereNotNull('submission_deadline')
                ->whereDate('submission_deadline', '>=', now()->toDateString())
                ->orderBy('submission_deadline')
                ->limit(10)
                ->get(['id', 'name', 'submission_deadline', 'reporting_calendar_id'])
                ->map(fn (ReportingPeriod $p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'submission_deadline' => $p->submission_deadline?->toDateString(),
                ])
                ->all(),
        ];
    }

    protected function assertOrg(ReportingCalendar $calendar): void
    {
        if ($calendar->organization_id !== $this->organizationContext->id()) {
            abort(404);
        }
    }
}
