<?php

namespace Database\Factories;

use App\Modules\Organization\Models\Facility;
use App\Modules\Organization\Models\Office;
use App\Modules\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Facility>
 */
class FacilityFactory extends Factory
{
    protected $model = Facility::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'office_id' => Office::factory(),
            'name' => fake()->words(2, true),
            'code' => strtoupper(fake()->optional()->bothify('FAC-###')),
            'type' => fake()->optional()->randomElement(['clinic', 'school', 'warehouse', 'distribution_point']),
            'address' => fake()->optional()->address(),
            'status' => 'active',
        ];
    }
}
