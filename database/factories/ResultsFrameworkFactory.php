<?php

namespace Database\Factories;

use App\Enums\WorkflowStatus;
use App\Modules\Organization\Models\Organization;
use App\Modules\Project\Models\Project;
use App\Modules\ResultsFramework\Models\ResultsFramework;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResultsFramework>
 */
class ResultsFrameworkFactory extends Factory
{
    protected $model = ResultsFramework::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'project_id' => Project::factory(),
            'title' => 'Results Framework '.fake()->unique()->numerify('###'),
            'theory_of_change' => fake()->optional()->paragraph(),
            'status' => WorkflowStatus::Draft->value,
            'version' => 1,
        ];
    }
}
