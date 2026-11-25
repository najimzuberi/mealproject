<?php

namespace App\Modules\ResultsFramework\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreResultsFrameworkRequest extends FormRequest
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
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'theory_of_change' => ['nullable', 'string'],
        ];
    }
}
