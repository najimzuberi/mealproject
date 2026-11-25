<?php

namespace App\Modules\MonitoringPlan\Requests;

class UpdateMonitoringPlanRequest extends StoreMonitoringPlanRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['project_id'] = ['sometimes', 'integer', 'exists:projects,id'];
        $rules['code'] = ['sometimes', 'string', 'max:50'];
        $rules['title'] = ['sometimes', 'string', 'max:255'];

        return $rules;
    }
}
