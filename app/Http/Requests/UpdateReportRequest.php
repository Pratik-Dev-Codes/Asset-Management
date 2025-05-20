<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateReportRequest extends StoreReportRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = parent::rules();

        // Make name optional for updates
        $rules['name'] = [
            'sometimes',
            'string',
            'max:255',
            Rule::unique('reports', 'name')->ignore($this->route('report')),
        ];

        // Make type immutable after creation
        if ($this->isMethod('PATCH') || $this->isMethod('PUT')) {
            $rules['type'] = [
                'sometimes',
                'string',
                Rule::in(['asset', 'inventory', 'maintenance', 'depreciation', 'custom']),
            ];
        }

        return $rules;
    }
}
