<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DonorCandidateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'blood_request_id' => $this->blood_request_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'distance_km' => (float) $this->distance_km,
            'status' => $this->status,
            'notified_at' => $this->notified_at?->toDateTimeString(),
            'confirmed_at' => $this->confirmed_at?->toDateTimeString(),
            'verified_at' => $this->verified_at?->toDateTimeString(),
            'verification_method' => $this->verification_method,
        ];
    }
}
