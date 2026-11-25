<?php

namespace Database\Factories;

use App\Modules\Organization\Models\Department;
use App\Modules\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->words(2, true),
            'code' => strtoupper(fake()->unique()->bothify('DEPT-###')),
            'description' => fake()->optional()->sentence(),
            'status' => 'active',
        ];
    }
}
