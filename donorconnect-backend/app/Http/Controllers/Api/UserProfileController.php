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
            'gender' => 'sometimes|in:male,female',
        ]);

        $user = $request->user();
        $user->fill($validated);

        $isUnderweight = $user->weight && $user->weight < 45;
        $isUnderage = $user->birth_date && \Carbon\Carbon::parse($user->birth_date)->age < 17;

        if ($isUnderweight || $isUnderage) {
            // Hard lock: overrides any explicit is_available=true in this
            // same request, since medical eligibility always wins.
            $user->is_available = false;
        } elseif (!array_key_exists('is_available', $validated)) {
            // Weight/age are valid again (e.g. user corrected a bad input
            // that previously locked them out) — re-open self-service,
            // but only if they didn't explicitly set is_available in this
            // same call, and only if they're not still in post-donation
            // cooldown (that lock is managed separately by UnlockDonorsJob).
            $isInCooldown = $user->last_donor_date
                && \Carbon\Carbon::parse($user->last_donor_date)->diffInDays(now()) < config('donorconnect.donation_cooldown_days', 56);

            if (!$isInCooldown) {
                $user->is_available = true;
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
