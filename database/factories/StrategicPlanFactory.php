<?php

namespace Database\Factories;

use App\Enums\StrategicPlanStatus;
use App\Modules\Organization\Models\Organization;
use App\Modules\StrategicPlanning\Models\StrategicPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StrategicPlan>
 */
class StrategicPlanFactory extends Factory
{
    protected $model = StrategicPlan::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'code' => 'SP-'.fake()->unique()->numerify('####'),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'vision' => fake()->optional()->sentence(),
            'mission' => fake()->optional()->sentence(),
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->addYears(4)->endOfYear()->toDateString(),
            'status' => StrategicPlanStatus::Draft->value,
            'version' => 1,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => StrategicPlanStatus::Approved->value,
            'approved_at' => now(),
        ]);
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => StrategicPlanStatus::Active->value,
            'approved_at' => now(),
        ]);
    }
}
