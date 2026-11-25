<?php

namespace App\Modules\StrategicPlanning\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStrategicPlanDocumentRequest extends FormRequest
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
            'file' => ['required', 'file', 'max:10240'],
            'title' => ['nullable', 'string', 'max:255'],
        ];
    }
}
