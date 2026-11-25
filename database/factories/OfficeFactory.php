<?php

namespace Database\Factories;

use App\Modules\Organization\Models\Department;
use App\Modules\Organization\Models\Office;
use App\Modules\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Office>
 */
class OfficeFactory extends Factory
{
    protected $model = Office::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'department_id' => Department::factory(),
            'name' => fake()->city().' Office',
            'code' => strtoupper(fake()->optional()->bothify('OFF-###')),
            'address' => fake()->optional()->address(),
            'status' => 'active',
        ];
    }
}
