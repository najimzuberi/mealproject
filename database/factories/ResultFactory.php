<?php

namespace Database\Factories;

use App\Enums\ResultLevel;
use App\Modules\Organization\Models\Organization;
use App\Modules\ResultsFramework\Models\Result;
use App\Modules\ResultsFramework\Models\ResultsFramework;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Result>
 */
class ResultFactory extends Factory
{
    protected $model = Result::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'results_framework_id' => ResultsFramework::factory(),
            'level' => fake()->randomElement(ResultLevel::cases()),
            'code' => strtoupper(fake()->optional()->bothify('R-###')),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }
}
