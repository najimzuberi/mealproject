<?php

namespace Database\Factories;

use App\Modules\DataCollection\Models\Submission;
use App\Modules\Dqa\Models\DqaAssessment;
use App\Modules\Dqa\Models\DqaIssue;
use App\Modules\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DqaIssue>
 */
class DqaIssueFactory extends Factory
{
    protected $model = DqaIssue::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'dqa_assessment_id' => DqaAssessment::factory(),
            'submission_id' => Submission::factory(),
            'dimension' => 'completeness',
            'severity' => 'medium',
            'title' => 'Missing evidence',
            'status' => 'open',
        ];
    }
}
