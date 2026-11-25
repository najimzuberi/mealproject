<?php

namespace Database\Factories;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Modules\Organization\Models\Organization;
use App\Modules\Reports\Models\Report;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'reference_number' => 'RPT-'.fake()->unique()->numerify('####'),
            'title' => 'Performance Report '.fake()->numerify('###'),
            'report_type' => ReportType::Quarterly->value,
            'status' => ReportStatus::Draft->value,
            'ai_assisted' => false,
        ];
    }
}
