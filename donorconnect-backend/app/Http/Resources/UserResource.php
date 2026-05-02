<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'weight' => (float) $this->weight,
            'blood_type' => $this->blood_type,
            'rhesus' => $this->rhesus,
            'last_donor_date' => $this->last_donor_date?->format('Y-m-d'),
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'is_available' => (bool) $this->is_available,
            'role' => $this->role,
        ];
    }
}
