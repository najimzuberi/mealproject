<?php

namespace App\Modules\ResultsFramework\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LinkIndicatorToResultRequest extends FormRequest
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
            'indicator_id' => ['required', 'integer', 'exists:indicators,id'],
        ];
    }
}
