<?php

namespace Database\Factories;

use App\Enums\ConfidentialityLevel;
use App\Enums\FeedbackCaseStatus;
use App\Modules\Feedback\Models\FeedbackCase;
use App\Modules\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FeedbackCase> */
class FeedbackCaseFactory extends Factory
{
    protected $model = FeedbackCase::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'reference_number' => 'FB-'.fake()->unique()->numerify('####'),
            'description' => fake()->paragraph(),
            'feedback_type' => 'complaint',
            'confidentiality_level' => ConfidentialityLevel::Internal->value,
            'status' => FeedbackCaseStatus::Received->value,
            'submitted_at' => now(),
        ];
    }

    public function safeguarding(): static
    {
        return $this->state(fn () => [
            'confidentiality_level' => ConfidentialityLevel::Safeguarding->value,
            'description' => 'Sensitive safeguarding matter',
        ]);
    }
}
