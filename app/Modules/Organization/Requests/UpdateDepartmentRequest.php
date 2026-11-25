<?php

namespace App\Modules\Organization\Requests;

use App\Enums\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:departments,id'],
            'head_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', Rule::enum(RecordStatus::class)],
        ];
    }
}
