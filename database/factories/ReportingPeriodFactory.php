<?php

namespace Database\Factories;

use App\Modules\Organization\Models\Organization;
use App\Modules\ReportingCalendar\Models\ReportingCalendar;
use App\Modules\ReportingCalendar\Models\ReportingPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportingPeriod>
 */
class ReportingPeriodFactory extends Factory
{
    protected $model = ReportingPeriod::class;

    public function definition(): array
    {
        $starts = fake()->dateTimeBetween('-1 year', 'now');
        $ends = (clone $starts)->modify('+1 month');

        return [
            'reporting_calendar_id' => ReportingCalendar::factory(),
            'organization_id' => Organization::factory(),
            'name' => fake()->monthName().' '.fake()->year(),
            'starts_on' => $starts,
            'ends_on' => $ends,
            'status' => fake()->randomElement(['draft', 'open', 'closed', 'locked']),
        ];
    }
}
