<?php

namespace App\Modules\Project\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncProjectTeamRequest extends FormRequest
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
            'members' => ['required', 'array'],
            'members.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'members.*.role_label' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return list<array{user_id: int, role_label?: string|null}>
     */
    public function members(): array
    {
        return $this->validated('members', []);
    }
}
