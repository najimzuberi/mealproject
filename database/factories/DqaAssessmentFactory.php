<?php

namespace Database\Factories;

use App\Enums\DqaAssessmentStatus;
use App\Modules\DataCollection\Models\Submission;
use App\Modules\Dqa\Models\DqaAssessment;
use App\Modules\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DqaAssessment>
 */
class DqaAssessmentFactory extends Factory
{
    protected $model = DqaAssessment::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'submission_id' => Submission::factory(),
            'status' => DqaAssessmentStatus::InProgress->value,
            'validity_score' => 80,
            'reliability_score' => 80,
            'completeness_score' => 80,
            'timeliness_score' => 80,
            'precision_score' => 80,
            'integrity_score' => 80,
            'overall_score' => 80,
        ];
    }
}
