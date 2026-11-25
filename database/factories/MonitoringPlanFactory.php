<?php

namespace Database\Factories;

use App\Enums\MonitoringPlanStatus;
use App\Modules\MonitoringPlan\Models\MonitoringPlan;
use App\Modules\Organization\Models\Organization;
use App\Modules\Project\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonitoringPlan>
 */
class MonitoringPlanFactory extends Factory
{
    protected $model = MonitoringPlan::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'project_id' => Project::factory(),
            'code' => 'MP-'.fake()->unique()->numerify('####'),
            'title' => 'Monitoring Plan '.fake()->unique()->numerify('###'),
            'description' => fake()->optional()->paragraph(),
            'status' => MonitoringPlanStatus::Draft->value,
            'version' => 1,
            'intended_use' => 'Programme decision-making',
        ];
    }
}
