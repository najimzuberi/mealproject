<?php

namespace App\Modules\ResultsFramework\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateResultsFrameworkRequest extends FormRequest
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
            'project_id' => ['sometimes', 'integer', 'exists:projects,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'theory_of_change' => ['nullable', 'string'],
        ];
    }
}
