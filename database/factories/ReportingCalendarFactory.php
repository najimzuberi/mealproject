<?php

namespace Database\Factories;

use App\Modules\Organization\Models\Organization;
use App\Modules\ReportingCalendar\Models\ReportingCalendar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportingCalendar>
 */
class ReportingCalendarFactory extends Factory
{
    protected $model = ReportingCalendar::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->year().' Reporting Calendar',
            'frequency' => fake()->randomElement(['monthly', 'quarterly', 'annual', 'custom']),
            'description' => fake()->optional()->sentence(),
            'allow_overlapping_periods' => false,
            'status' => 'active',
        ];
    }
}
