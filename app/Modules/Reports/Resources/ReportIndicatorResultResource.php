<?php

namespace App\Modules\Reports\Resources;

use App\Modules\Reports\Models\ReportIndicatorResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ReportIndicatorResult */
class ReportIndicatorResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'indicator_id' => $this->indicator_id,
            'project_id' => $this->project_id,
            'reporting_period_id' => $this->reporting_period_id,
            'indicator_code' => $this->indicator_code,
            'indicator_name' => $this->indicator_name,
            'baseline' => $this->baseline,
            'target' => $this->target,
            'actual' => $this->actual,
            'achievement_percent' => $this->achievement_percent,
            'variance' => $this->variance,
            'performance_status' => $this->performance_status,
        ];
    }
}
