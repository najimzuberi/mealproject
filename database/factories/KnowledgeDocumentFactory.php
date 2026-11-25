<?php

namespace Database\Factories;

use App\Enums\KnowledgeDocumentStatus;
use App\Modules\Knowledge\Models\KnowledgeDocument;
use App\Modules\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<KnowledgeDocument> */
class KnowledgeDocumentFactory extends Factory
{
    protected $model = KnowledgeDocument::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'code' => 'KD-'.fake()->unique()->numerify('####'),
            'title' => 'Knowledge '.fake()->words(3, true),
            'status' => KnowledgeDocumentStatus::Draft->value,
            'version' => 1,
            'access_level' => 'internal',
        ];
    }
}
