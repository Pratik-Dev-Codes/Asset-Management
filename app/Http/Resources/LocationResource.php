<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Location
 */
class LocationResource extends JsonResource
{
    /**
     * The "data" wrapper that should be applied.
     *
     * @var string|null
     */
    public static $wrap = 'location';

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'is_active' => $this->is_active,
            'coordinates' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
            'contact' => [
                'person' => $this->contact_person,
                'email' => $this->contact_email,
                'phone' => $this->contact_phone,
            ],
            'address' => [
                'line1' => $this->address,
                'city' => $this->city,
                'state' => $this->state,
                'postal_code' => $this->postal_code,
                'country' => $this->country,
                'formatted' => $this->getFormattedAddress(),
            ],
            'metadata' => [
                'notes' => $this->notes,
                'created_at' => $this->created_at?->toIso8601String(),
                'updated_at' => $this->updated_at?->toIso8601String(),
            ],
            'relationships' => [
                'parent' => $this->whenLoaded('parent', function () {
                    return [
                        'id' => $this->parent->id,
                        'name' => $this->parent->name,
                        'code' => $this->parent->code,
                        'type' => $this->parent->type,
                    ];
                }, null),
                'children_count' => $this->whenCounted('children', $this->children_count),
                'assets_count' => $this->whenCounted('assets', $this->assets_count),
            ],
        ];

        // Include full resource for includes
        if ($request->has('include')) {
            $includes = explode(',', $request->include);
            
            if (in_array('parent', $includes) && !$this->relationLoaded('parent')) {
                $this->loadMissing('parent');
            }
            
            if (in_array('children', $includes) && !$this->relationLoaded('children')) {
                $this->loadMissing('children');
            }
            
            if (in_array('assets', $includes) && !$this->relationLoaded('assets')) {
                $this->loadMissing('assets');
            }
            
            if ($this->relationLoaded('children')) {
                $data['relationships']['children'] = LocationResource::collection($this->children);
            }
        }

        // Add HATEOAS links
        $data['links'] = $this->getLinks();

        return $data;
    }
    
    /**
     * Get the HATEOAS links for the resource.
     *
     * @return array<string, string>
     */
    protected function getLinks(): array
    {
        return [
            'self' => route('api.v1.locations.show', $this->id),
            'parent' => $this->parent_id ? route('api.v1.locations.show', $this->parent_id) : null,
            'children' => route('api.v1.locations.index', ['parent_id' => $this->id]),
            'assets' => route('api.v1.assets.index', ['location_id' => $this->id]),
        ];
    }
    
    /**
     * Get a formatted address string.
     */
    protected function getFormattedAddress(): ?string
    {
        if (!$this->address) {
            return null;
        }
        
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);
        
        return $parts ? implode(', ', $parts) : null;
    }
}
