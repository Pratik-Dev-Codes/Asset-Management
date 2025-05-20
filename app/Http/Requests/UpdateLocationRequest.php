<?php

namespace App\Http\Requests;

use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLocationRequest extends StoreLocationRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('location.edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        // Make code optional for updates
        $rules['code'] = [
            'sometimes',
            'string',
            'max:50',
            Rule::unique('locations', 'code')->ignore($this->location->id),
        ];

        // Make name optional for updates
        $rules['name'] = 'sometimes|string|max:255';

        return $rules;
    }
}
