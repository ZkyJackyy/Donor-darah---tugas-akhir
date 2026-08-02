<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBloodRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:emergency,event',
            'blood_type' => 'required_if:type,emergency|nullable|in:A,B,AB,O',
            'rhesus' => 'required_if:type,emergency|nullable|in:+,-',
            'urgency_level' => 'required|in:normal,urgent,critical',
            'hospital_name' => 'nullable|string|max:255',
            'hospital_address' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'required_bags' => 'required_if:type,emergency|nullable|integer|min:1',
            'event_starts_at' => 'required_if:type,event|nullable|date|after:now|before:deadline',
            'deadline' => 'required|date|after:now',
            'notes' => 'nullable|string',
        ];
    }
}
