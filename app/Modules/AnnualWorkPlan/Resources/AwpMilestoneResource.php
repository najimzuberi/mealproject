<?php

namespace App\Modules\AnnualWorkPlan\Resources;

use App\Modules\AnnualWorkPlan\Models\AwpMilestone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AwpMilestone */
class AwpMilestoneResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'awp_activity_id' => $this->awp_activity_id,
            'quarter' => $this->quarter,
            'title' => $this->title,
            'due_on' => $this->due_on?->toDateString(),
            'status' => $this->status,
        ];
    }
}
