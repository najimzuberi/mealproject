<?php

namespace Database\Factories;

use App\Enums\StrategicPlanStatus;
use App\Modules\AnnualWorkPlan\Models\AnnualWorkPlan;
use App\Modules\Organization\Models\Organization;
use App\Modules\StrategicPlanning\Models\StrategicPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnnualWorkPlan>
 */
class AnnualWorkPlanFactory extends Factory
{
    protected $model = AnnualWorkPlan::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'strategic_plan_id' => StrategicPlan::factory()->active(),
            'code' => 'AWP-'.fake()->unique()->numerify('####'),
            'title' => fake()->sentence(3),
            'planning_year' => (int) now()->year,
            'description' => fake()->optional()->paragraph(),
            'status' => StrategicPlanStatus::Draft->value,
            'version' => 1,
        ];
    }
}
