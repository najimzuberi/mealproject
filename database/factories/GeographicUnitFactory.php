<?php

namespace Database\Factories;

use App\Modules\Geography\Models\GeographicUnit;
use App\Modules\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GeographicUnit>
 */
class GeographicUnitFactory extends Factory
{
    protected $model = GeographicUnit::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'parent_id' => null,
            'name' => fake()->city(),
            'code' => strtoupper(fake()->optional()->bothify('GEO-###')),
            'level' => fake()->numberBetween(1, 5),
            'type' => fake()->randomElement(['region', 'district', 'ward', 'village', 'site']),
            'status' => 'active',
        ];
    }
}
