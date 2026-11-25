<?php

namespace Database\Factories;

use App\Modules\Sector\Models\Sector;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Sector>
 */
class SectorFactory extends Factory
{
    protected $model = Sector::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ucfirst($name),
            'code' => Str::slug($name),
            'description' => fake()->optional()->sentence(),
            'is_system' => false,
            'status' => 'active',
        ];
    }
}
