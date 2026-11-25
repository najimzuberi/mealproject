<?php

namespace App\Modules\Organization\Requests;

use App\Enums\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:departments,id'],
            'head_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', Rule::enum(RecordStatus::class)],
        ];
    }
}
