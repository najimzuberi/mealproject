<?php

namespace Database\Factories;

use App\Modules\Geography\Models\GeographicType;
use App\Modules\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GeographicType>
 */
class GeographicTypeFactory extends Factory
{
    protected $model = GeographicType::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->randomElement(['Country', 'Region', 'District', 'Ward', 'Village']),
            'code' => strtoupper(fake()->unique()->bothify('GTYPE-###')),
            'default_level' => fake()->numberBetween(0, 4),
            'description' => fake()->optional()->sentence(),
            'status' => 'active',
        ];
    }
}
