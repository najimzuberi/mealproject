<?php

namespace App\Modules\Indicator\Requests;

class UpdateIndicatorRequest extends StoreIndicatorRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['code'] = ['sometimes', 'string', 'max:50'];
        $rules['name'] = ['sometimes', 'string', 'max:255'];

        return $rules;
    }
}
