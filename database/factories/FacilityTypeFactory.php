<?php

namespace Database\Factories;

use App\Modules\Organization\Models\FacilityType;
use App\Modules\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FacilityType>
 */
class FacilityTypeFactory extends Factory
{
    protected $model = FacilityType::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->words(2, true),
            'code' => strtoupper(fake()->unique()->bothify('FTYPE-###')),
            'description' => fake()->optional()->sentence(),
            'status' => 'active',
        ];
    }
}
