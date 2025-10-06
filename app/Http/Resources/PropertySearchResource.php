<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertySearchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'lat' => $this->lat,
            'long' => $this->long,
            'apartments' => ApartmentSearchResource::collection($this->apartments),
            'photos' => $this->whenLoaded('media') ? $this->media->map(fn($media) => $media->getUrl('thumbnail')) : [],
            'avg_rating' => $this->bookings_avg_rating ? round($this->bookings_avg_rating, 2) : null,
            'number_of_bookings' => $this->bookings_count,
        ];
    }
}
