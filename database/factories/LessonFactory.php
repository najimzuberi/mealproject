<?php

namespace Database\Factories;

use App\Enums\LessonStatus;
use App\Modules\Lesson\Models\Lesson;
use App\Modules\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Lesson> */
class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'reference' => 'LL-'.fake()->unique()->numerify('####'),
            'title' => 'Lesson '.fake()->words(3, true),
            'status' => LessonStatus::Documented->value,
        ];
    }
}
