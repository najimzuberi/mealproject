<?php

namespace Database\Factories;

use App\Modules\Indicator\Models\Indicator;
use App\Modules\MonitoringPlan\Models\MonitoringPlan;
use App\Modules\MonitoringPlan\Models\MonitoringPlanItem;
use App\Modules\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonitoringPlanItem>
 */
class MonitoringPlanItemFactory extends Factory
{
    protected $model = MonitoringPlanItem::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'monitoring_plan_id' => MonitoringPlan::factory(),
            'indicator_id' => Indicator::factory(),
            'baseline_value' => '10',
            'target_value' => '50',
            'sort_order' => 0,
        ];
    }
}
