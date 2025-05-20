<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'asset_code' => $this->asset_code,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'condition' => $this->condition,
            'serial_number' => $this->serial_number,
            'model' => $this->model,
            'manufacturer' => $this->manufacturer,
            'purchase_date' => $this->purchase_date?->format('Y-m-d'),
            'purchase_cost' => (float) $this->purchase_cost,
            'warranty_expiry_date' => $this->warranty_expiry_date?->format('Y-m-d'),
            'warranty_provider' => $this->warranty_provider,
            'warranty_details' => $this->warranty_details,
            'insurance_provider' => $this->insurance_provider,
            'insurance_policy_number' => $this->insurance_policy_number,
            'insurance_start_date' => $this->insurance_start_date?->format('Y-m-d'),
            'insurance_end_date' => $this->insurance_end_date?->format('Y-m-d'),
            'insurance_premium' => (float) $this->insurance_premium,
            'insurance_coverage' => $this->insurance_coverage,
            'image_url' => $this->image_path ? asset('storage/' . $this->image_path) : null,
            'notes' => $this->notes,
            'category' => $this->whenLoaded('category'),
            'location' => $this->whenLoaded('location'),
            'department' => $this->whenLoaded('department'),
            'assigned_to' => $this->whenLoaded('assignedTo'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}