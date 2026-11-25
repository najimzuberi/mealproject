<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Modules\Organization\Models\Organization;
use App\Modules\Project\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'code' => 'PRJ-'.fake()->unique()->numerify('####'),
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'budget' => fake()->optional()->randomFloat(2, 10000, 5000000),
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->addYear()->toDateString(),
            'status' => ProjectStatus::Draft->value,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => ProjectStatus::Active->value]);
    }
}
