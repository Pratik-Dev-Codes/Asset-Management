<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'asset_code' => $this->asset_code,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'serial_number' => $this->serial_number,
            'model_number' => $this->model_number,
            'manufacturer' => $this->manufacturer,
            'purchase_date' => $this->purchase_date?->toDateString(),
            'purchase_cost' => (float) $this->purchase_cost,
            'current_value' => $this->current_value ? (float) $this->current_value : null,
            'warranty_expiry_date' => $this->warranty_expiry_date?->toDateString(),
            'warranty_provider' => $this->warranty_provider,
            'barcode' => $this->barcode,
            'qr_code' => $this->qr_code,
            'notes' => $this->notes,
            'assigned_to' => $this->whenLoaded('assignedTo', function () {
                return $this->assignedTo ? [
                    'id' => $this->assignedTo->id,
                    'name' => $this->assignedTo->name,
                    'email' => $this->assignedTo->email,
                ] : null;
            }),
            'assigned_date' => $this->assigned_date?->toDateString(),
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'description' => $this->category->description,
                ];
            }),
            'location' => $this->whenLoaded('location', function () {
                return [
                    'id' => $this->location->id,
                    'name' => $this->location->name,
                    'code' => $this->location->code,
                ];
            }),
            'department' => $this->whenLoaded('department', function () {
                return $this->department ? [
                    'id' => $this->department->id,
                    'name' => $this->department->name,
                    'code' => $this->department->code,
                ] : null;
            }),
            'custom_fields' => $this->when($this->relationLoaded('customFields'), function () {
                return $this->customFields->mapWithKeys(fn ($field) => [
                    $field->name => $field->pivot->value,
                ]);
            }),
            'maintenance_logs_count' => $this->whenCounted('maintenanceLogs'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->when($this->deleted_at, $this->deleted_at),
        ];
    }

    /**
     * Get any additional data that should be returned with the resource array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function with($request)
    {
        return [
            'meta' => [
                'version' => '1.0.0',
                'api_version' => 'v1',
            ],
        ];
    }
}
