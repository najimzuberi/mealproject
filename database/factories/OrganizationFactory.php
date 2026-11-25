<?php

namespace Database\Factories;

use App\Modules\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'code' => Str::slug($name).'-'.fake()->unique()->numerify('####'),
            'registration_number' => fake()->optional()->bothify('REG-####'),
            'type' => fake()->randomElement(['ngo', 'cso', 'foundation', 'government', 'donor_programme', 'other']),
            'description' => fake()->optional()->sentence(),
            'email' => fake()->optional()->companyEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'website' => fake()->optional()->url(),
            'logo_path' => null,
            'country' => fake()->countryCode(),
            'address' => fake()->optional()->address(),
            'status' => 'active',
        ];
    }
}
