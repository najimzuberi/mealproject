<?php

namespace App\Modules\ResultsFramework\Requests;

use App\Enums\ResultLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreResultRequest extends FormRequest
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
            'parent_id' => ['nullable', 'integer', 'exists:results,id'],
            'level' => ['required', Rule::enum(ResultLevel::class)],
            'code' => ['nullable', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
        ];
    }
}
