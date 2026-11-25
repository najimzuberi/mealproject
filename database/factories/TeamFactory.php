<?php

namespace Database\Factories;

use App\Modules\Organization\Models\Organization;
use App\Modules\Organization\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->words(2, true).' Team',
            'code' => strtoupper(fake()->unique()->bothify('TEAM-###')),
            'description' => fake()->optional()->sentence(),
            'status' => 'active',
        ];
    }
}
