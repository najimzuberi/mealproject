<?php

namespace App\Modules\DataCollection\Services;

use App\Modules\DataCollection\Models\ReportingAssignment;
use App\Modules\MonitoringPlan\Models\MonitoringPlan;
use App\Modules\ReportingCalendar\Models\ReportingPeriod;
use Illuminate\Support\Facades\DB;

class AssignmentGenerationService
{
    /**
     * Generate reporting assignments from an activated monitoring plan.
     * Only creates assignments when a responsible collector is defined.
     *
     * @return list<ReportingAssignment>
     */
    public function generateFromMonitoringPlan(MonitoringPlan $plan): array
    {
        return DB::transaction(function () use ($plan) {
            $plan->loadMissing('items');

            $periods = ReportingPeriod::query()
                ->where('organization_id', $plan->organization_id)
                ->where('status', 'open')
                ->orderBy('starts_on')
                ->get();

            $created = [];

            foreach ($plan->items as $item) {
                if (! $item->responsible_collector_id) {
                    continue;
                }

                $periodIds = $periods->isEmpty() ? [null] : $periods->pluck('id')->all();

                foreach ($periodIds as $periodId) {
                    $assignment = ReportingAssignment::query()->firstOrCreate(
                        [
                            'monitoring_plan_item_id' => $item->id,
                            'reporting_period_id' => $periodId,
                            'assigned_to' => $item->responsible_collector_id,
                        ],
                        [
                            'organization_id' => $plan->organization_id,
                            'monitoring_plan_id' => $plan->id,
                            'supervisor_id' => $item->supervisor_id,
                            'due_on' => $item->collection_deadline,
                            'status' => 'pending',
                        ],
                    );

                    $created[] = $assignment;
                }
            }

            return $created;
        });
    }
}
