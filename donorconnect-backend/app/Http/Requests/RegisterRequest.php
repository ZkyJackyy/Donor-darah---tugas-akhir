<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'required|string',
            'birth_date' => ['required', 'date', 'before_or_equal:' . now()->subYears(17)->toDateString()],
            'weight' => 'required|numeric|min:45',
            'blood_type' => 'required|in:A,B,AB,O',
            'rhesus' => 'required|in:+,-',
        ];
    }

    public function messages(): array
    {
        return [
            'birth_date.before_or_equal' => 'Usia minimum untuk mendaftar adalah 17 tahun.',
        ];
    }
}
