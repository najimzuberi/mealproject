<?php

namespace App\Modules\Lesson\Resources;

use App\Modules\Lesson\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Lesson */
class LessonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ulid' => $this->ulid,
            'organization_id' => $this->organization_id,
            'reference' => $this->reference,
            'title' => $this->title,
            'description' => $this->description,
            'source' => $this->source,
            'project_id' => $this->project_id,
            'programme_id' => $this->programme_id,
            'evaluation_id' => $this->evaluation_id,
            'feedback_case_id' => $this->feedback_case_id,
            'context' => $this->context,
            'challenge' => $this->challenge,
            'what_worked' => $this->what_worked,
            'what_did_not_work' => $this->what_did_not_work,
            'recommendation' => $this->recommendation,
            'responsible_person_id' => $this->responsible_person_id,
            'action_required' => $this->action_required,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'links' => $this->whenLoaded('links'),
            'actions' => $this->whenLoaded('actions'),
            'applied_at' => $this->applied_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
