<?php

namespace App\Modules\Feedback\Resources;

use App\Modules\Feedback\Models\FeedbackCase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FeedbackCase */
class FeedbackCaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $restricted = $this->resource->isRestricted();
        $canSeeSensitive = $request->user()?->can('feedback.sensitive') || $request->user()?->can('feedback.manage');
        $redact = $restricted && ! $canSeeSensitive;

        return [
            'id' => $this->id,
            'ulid' => $this->ulid,
            'organization_id' => $this->organization_id,
            'reference_number' => $this->reference_number,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'feedback_type' => $this->feedback_type,
            'feedback_category_id' => $this->feedback_category_id,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
                'is_sensitive' => $this->category?->is_sensitive,
            ]),
            'description' => $redact ? '[Restricted — authorized access required]' : $this->description,
            'submission_channel' => $this->submission_channel,
            'project_id' => $this->project_id,
            'geographic_unit_id' => $this->geographic_unit_id,
            'is_anonymous' => $this->is_anonymous,
            'complainant_name' => $redact || $this->is_anonymous ? null : $this->complainant_name,
            'complainant_contact' => $redact || $this->is_anonymous ? null : $this->complainant_contact,
            'consent_given' => $this->consent_given,
            'confidentiality_level' => $this->confidentiality_level instanceof \BackedEnum
                ? $this->confidentiality_level->value
                : $this->confidentiality_level,
            'is_restricted' => $restricted,
            'priority' => $this->priority,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'assigned_to' => $this->assigned_to,
            'resolution_deadline' => $this->resolution_deadline?->toDateString(),
            'escalation_level' => $this->escalation_level,
            'investigation_notes' => $redact ? null : $this->investigation_notes,
            'resolution_description' => $redact ? null : $this->resolution_description,
            'action_taken' => $redact ? null : $this->action_taken,
            'feedback_loop_status' => $this->feedback_loop_status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
