<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateLocationRequest;
use App\Http\Resources\UserResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserProfileController extends Controller
{
    use ApiResponse;

    public function show(Request $request): JsonResponse
    {
        return $this->success(new UserResource($request->user()));
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string',
            'weight' => 'sometimes|numeric',
            'birth_date' => 'sometimes|date',
            'is_available' => 'sometimes|boolean',
            'blood_type' => 'sometimes|in:A,B,AB,O',
            'rhesus' => 'sometimes|in:+,-',
        ]);

        $user = $request->user();
        $user->fill($validated);

        if ($user->weight && $user->weight < 45) {
            $user->is_available = false;
        }
        
        if ($user->birth_date) {
            $age = \Carbon\Carbon::parse($user->birth_date)->age;
            if ($age < 17) {
                $user->is_available = false;
            }
        }

        $user->save();
        
        return $this->success(new UserResource($user), 'Profile updated successfully');
    }

    public function updatePhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = $request->user();

        if ($user->photo) {
            Storage::disk('public')->delete($user->photo);
        }

        $path = $request->file('photo')->store('avatars', 'public');
        $user->update(['photo' => $path]);

        return $this->success(new UserResource($user), 'Photo updated successfully');
    }

    public function updateLocation(UpdateLocationRequest $request): JsonResponse
    {
        $request->user()->update($request->validated());
        
        return $this->success(new UserResource($request->user()), 'Location updated successfully');
    }
}
