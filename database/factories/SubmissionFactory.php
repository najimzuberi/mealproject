<?php

namespace Database\Factories;

use App\Enums\SubmissionStatus;
use App\Modules\DataCollection\Models\Submission;
use App\Modules\Indicator\Models\Indicator;
use App\Modules\MonitoringPlan\Models\MonitoringPlan;
use App\Modules\MonitoringPlan\Models\MonitoringPlanItem;
use App\Modules\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    protected $model = Submission::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'monitoring_plan_id' => MonitoringPlan::factory(),
            'monitoring_plan_item_id' => MonitoringPlanItem::factory(),
            'indicator_id' => Indicator::factory(),
            'status' => SubmissionStatus::Draft->value,
            'value' => fake()->randomFloat(2, 1, 100),
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn () => [
            'status' => SubmissionStatus::Submitted->value,
            'submitted_at' => now(),
        ]);
    }
}
