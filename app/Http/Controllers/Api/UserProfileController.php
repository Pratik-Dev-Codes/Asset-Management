<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class UserProfileController extends BaseApiController
{
    /**
     * Get the authenticated user's profile.
     */
    public function show(): JsonResponse
    {
        try {
            $user = Auth::user()->load(['roles', 'department']);

            return $this->success(
                new UserResource($user),
                'Profile retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Failed to fetch user profile: '.$e->getMessage());

            return $this->error('Failed to retrieve profile', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the authenticated user's profile.
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
                'phone' => 'nullable|string|max:20',
                'department_id' => 'nullable|exists:departments,id',
            ]);

            $user->update($validated);

            return $this->success(
                new UserResource($user->load(['roles', 'department'])),
                'Profile updated successfully'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed', Response::HTTP_UNPROCESSABLE_ENTITY, $e->errors());
        } catch (\Exception $e) {
            Log::error('Failed to update profile: '.$e->getMessage());

            return $this->error('Failed to update profile', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the authenticated user's password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $validated = $request->validate([
                'current_password' => ['required', 'string', 'current_password:api'],
                'password' => 'required|string|min:8|confirmed|different:current_password',
            ]);

            $user->update([
                'password' => Hash::make($validated['password']),
                'password_changed_at' => now(),
            ]);

            return $this->success(
                null,
                'Password updated successfully',
                Response::HTTP_OK
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed', Response::HTTP_UNPROCESSABLE_ENTITY, $e->errors());
        } catch (\Exception $e) {
            Log::error('Failed to update password: '.$e->getMessage());

            return $this->error('Failed to update password', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the authenticated user's profile picture.
     */
    public function updateProfilePicture(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $request->validate([
                'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // Delete old avatar if exists
            if ($user->avatar_path) {
                Storage::delete($user->avatar_path);
            }

            // Store new avatar
            $path = $request->file('avatar')->store('avatars', 'public');

            $user->update([
                'avatar_path' => $path,
                'avatar_url' => Storage::url($path),
            ]);

            return $this->success(
                [
                    'avatar_url' => $user->avatar_url,
                ],
                'Profile picture updated successfully'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed', Response::HTTP_UNPROCESSABLE_ENTITY, $e->errors());
        } catch (\Exception $e) {
            Log::error('Failed to update profile picture: '.$e->getMessage());

            return $this->error('Failed to update profile picture', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the authenticated user's profile picture.
     */
    public function removeProfilePicture(): JsonResponse
    {
        try {
            $user = Auth::user();

            if ($user->avatar_path) {
                Storage::delete($user->avatar_path);

                $user->update([
                    'avatar_path' => null,
                    'avatar_url' => null,
                ]);
            }

            return $this->success(
                null,
                'Profile picture removed successfully',
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::error('Failed to remove profile picture: '.$e->getMessage());

            return $this->error('Failed to remove profile picture', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
