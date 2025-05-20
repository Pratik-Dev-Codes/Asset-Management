<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'asset_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('assets')->whereNull('deleted_at'),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:asset_categories,id',
            'location_id' => 'required|exists:locations,id',
            'department_id' => 'nullable|exists:departments,id',
            'status' => 'required|in:operational,under-maintenance,out-of-service,retired,available,in_use,disposed,lost',
            'manufacturer' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255|unique:assets,serial_number',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'warranty_start_date' => 'nullable|date',
            'warranty_expiry_date' => 'nullable|date|after_or_equal:warranty_start_date',
            'warranty_provider' => 'nullable|string|max:255',
            'warranty_details' => 'nullable|string',
            'expected_lifetime_years' => 'nullable|integer|min:0',
            'salvage_value' => 'nullable|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'depreciation_method' => 'nullable|in:straight_line,declining_balance,double_declining_balance,sum_of_years_digits,units_of_production',
            'depreciation_rate' => 'nullable|numeric|min:0|max:100',
            'depreciation_start_date' => 'nullable|date',
            'depreciation_frequency' => 'nullable|in:monthly,quarterly,annually',
            'last_maintenance_date' => 'nullable|date',
            'next_maintenance_date' => 'nullable|date|after_or_equal:last_maintenance_date',
            'assigned_to' => 'nullable|exists:users,id',
            'insurer_company' => 'nullable|string|max:255',
            'policy_number' => 'nullable|string|max:255',
            'coverage_details' => 'nullable|string',
            'insurance_start_date' => 'nullable|date',
            'insurance_end_date' => 'nullable|date|after_or_equal:insurance_start_date',
            'premium_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:255',
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
            'asset_code.unique' => 'The asset code has already been taken.',
            'serial_number.unique' => 'The serial number has already been used for another asset.',
            'category_id.exists' => 'The selected category is invalid.',
            'location_id.exists' => 'The selected location is invalid.',
            'department_id.exists' => 'The selected department is invalid.',
            'assigned_to.exists' => 'The selected user is invalid.',
        ];
    }
}
