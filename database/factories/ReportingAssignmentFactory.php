<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\DataCollection\Models\ReportingAssignment;
use App\Modules\MonitoringPlan\Models\MonitoringPlan;
use App\Modules\MonitoringPlan\Models\MonitoringPlanItem;
use App\Modules\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportingAssignment>
 */
class ReportingAssignmentFactory extends Factory
{
    protected $model = ReportingAssignment::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'monitoring_plan_id' => MonitoringPlan::factory(),
            'monitoring_plan_item_id' => MonitoringPlanItem::factory(),
            'assigned_to' => User::factory(),
            'status' => 'pending',
        ];
    }
}
