<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BloodRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'admin_id' => $this->admin_id,
            'blood_type' => $this->blood_type,
            'rhesus' => $this->rhesus,
            'urgency_level' => $this->urgency_level,
            'hospital_name' => $this->hospital_name,
            'hospital_address' => $this->hospital_address,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'required_bags' => $this->required_bags,
            'deadline' => $this->deadline,
            'status' => $this->status,
            'notes' => $this->notes,
            'candidates' => DonorCandidateResource::collection($this->whenLoaded('donorCandidates')),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
