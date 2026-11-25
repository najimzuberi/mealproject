<?php

namespace Database\Factories;

use App\Enums\IndicatorStatus;
use App\Modules\Indicator\Models\Indicator;
use App\Modules\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Indicator>
 */
class IndicatorFactory extends Factory
{
    protected $model = Indicator::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'code' => 'IND-'.fake()->unique()->numerify('####'),
            'name' => fake()->sentence(4),
            'description' => fake()->optional()->sentence(),
            'definition' => fake()->optional()->paragraph(),
            'measurement_unit' => 'percent',
            'formula' => '(numerator/denominator)*100',
            'aggregation_method' => 'average',
            'direction_of_change' => 'increase',
            'indicator_type' => 'organizational',
            'status' => IndicatorStatus::Draft->value,
            'version' => 1,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => IndicatorStatus::Approved->value,
            'approved_at' => now(),
        ]);
    }
}
