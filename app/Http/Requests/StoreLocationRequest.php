<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLocationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('location.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('locations', 'code')->ignore($this->location),
            ],
            'parent_id' => [
                'nullable',
                'exists:locations,id',
                function ($attribute, $value, $fail) {
                    if ($value && $this->location && $value == $this->location->id) {
                        $fail('A location cannot be its own parent.');
                    }
                    if ($value && $this->location && $this->location->isDescendantOf($value)) {
                        $fail('A location cannot be a descendant of itself.');
                    }
                },
            ],
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'contact_person' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'type' => 'required|string|in:facility,plant,office,warehouse',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The location name is required.',
            'code.required' => 'The location code is required.',
            'code.unique' => 'This location code is already in use.',
            'type.in' => 'The selected type is invalid. Must be one of: facility, plant, office, warehouse.',
        ];
    }
}
