<?php

namespace App\Modules\Dqa\Resources;

use App\Modules\Dqa\Models\DqaIssue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DqaIssue */
class DqaIssueResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ulid' => $this->ulid,
            'organization_id' => $this->organization_id,
            'dqa_assessment_id' => $this->dqa_assessment_id,
            'submission_id' => $this->submission_id,
            'assigned_to' => $this->assigned_to,
            'dimension' => $this->dimension,
            'severity' => $this->severity,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'resolution_notes' => $this->resolution_notes,
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
