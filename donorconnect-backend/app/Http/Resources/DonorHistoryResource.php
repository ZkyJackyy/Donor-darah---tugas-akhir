<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DonorHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'donor_date' => $this->donor_date?->format('Y-m-d'),
            'location_name' => $this->location_name,
            'verified_by' => new UserResource($this->whenLoaded('verifier')),
        ];
    }
}
