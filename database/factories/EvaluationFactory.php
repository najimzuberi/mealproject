<?php

namespace Database\Factories;

use App\Enums\EvaluationStatus;
use App\Modules\Evaluation\Models\Evaluation;
use App\Modules\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Evaluation> */
class EvaluationFactory extends Factory
{
    protected $model = Evaluation::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'code' => 'EV-'.fake()->unique()->numerify('####'),
            'title' => 'Evaluation '.fake()->words(3, true),
            'status' => EvaluationStatus::Draft->value,
        ];
    }
}
