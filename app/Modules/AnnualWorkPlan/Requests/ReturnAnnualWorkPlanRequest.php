<?php

namespace App\Modules\AnnualWorkPlan\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReturnAnnualWorkPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'comment' => ['required', 'string', 'max:2000'],
        ];
    }
}
