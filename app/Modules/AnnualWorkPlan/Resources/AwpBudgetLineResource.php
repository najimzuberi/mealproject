<?php

namespace App\Modules\AnnualWorkPlan\Resources;

use App\Modules\AnnualWorkPlan\Models\AwpBudgetLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AwpBudgetLine */
class AwpBudgetLineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ulid' => $this->ulid,
            'annual_work_plan_id' => $this->annual_work_plan_id,
            'awp_activity_id' => $this->awp_activity_id,
            'department_id' => $this->department_id,
            'code' => $this->code,
            'category' => $this->category,
            'description' => $this->description,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'funding_source' => $this->funding_source,
            'financial_year' => $this->financial_year,
            'quarter' => $this->quarter,
        ];
    }
}
