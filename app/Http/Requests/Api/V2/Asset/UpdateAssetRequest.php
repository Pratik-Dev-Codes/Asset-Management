<?php

namespace App\Http\Requests\Api\V2\Asset;

use App\Http\Requests\Api\V2\BaseApiRequest;
use Illuminate\Validation\Rule;

class UpdateAssetRequest extends BaseApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $assetId = $this->route('asset');
        
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'asset_tag' => [
                'sometimes',
                'string',
                Rule::unique('assets', 'asset_tag')->ignore($assetId)
            ],
            'serial_number' => [
                'nullable',
                'string',
                Rule::unique('assets', 'serial_number')->ignore($assetId)
            ],
            'model_number' => 'nullable|string|max:100',
            'manufacturer' => 'nullable|string|max:100',
            'category_id' => 'sometimes|exists:asset_categories,id',
            'location_id' => 'nullable|exists:locations,id',
            'department_id' => 'nullable|exists:departments,id',
            'assigned_to' => 'nullable|exists:users,id',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'warranty_expires' => 'nullable|date|after:purchase_date',
            'warranty_notes' => 'nullable|string',
            'status' => ['sometimes', Rule::in(['available', 'assigned', 'under_maintenance', 'disposed'])],
            'notes' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'asset_tag.unique' => 'This asset tag is already in use.',
            'serial_number.unique' => 'This serial number is already in use.',
            'category_id.exists' => 'The selected category is invalid.',
            'location_id.exists' => 'The selected location is invalid.',
            'department_id.exists' => 'The selected department is invalid.',
            'assigned_to.exists' => 'The selected user is invalid.',
            'warranty_expires.after' => 'Warranty expiration must be after purchase date.',
        ];
    }
}
