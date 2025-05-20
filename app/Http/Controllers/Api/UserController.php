<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;

class UserController extends BaseApiController
{
    /**
     * Display a listing of users.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $users = User::with(['roles', 'department'])
                ->when($request->search, function($query) use ($request) {
                    $query->where('name', 'like', "%{$request->search}%")
                          ->orWhere('email', 'like', "%{$request->search}%");
                })
                ->when($request->status, function($query, $status) {
                    $query->where('status', $status);
                })
                ->paginate($perPage);
            
            return $this->success(
                UserResource::collection($users),
                'Users retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Failed to fetch users: ' . $e->getMessage());
            return $this->error('Failed to retrieve users', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created user in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'status' => 'required|in:active,inactive,suspended',
                'role_id' => 'required|exists:roles,id',
                'department_id' => 'nullable|exists:departments,id',
                'phone' => 'nullable|string|max:20',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'status' => $validated['status'],
                'department_id' => $validated['department_id'] ?? null,
                'phone' => $validated['phone'] ?? null,
            ]);

            $user->roles()->attach($validated['role_id']);

            return $this->success(
                new UserResource($user->load(['roles', 'department'])),
                'User created successfully',
                Response::HTTP_CREATED
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed', Response::HTTP_UNPROCESSABLE_ENTITY, $e->errors());
        } catch (\Exception $e) {
            Log::error('Failed to create user: ' . $e->getMessage());
            return $this->error('Failed to create user', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified user.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function show(User $user): JsonResponse
    {
        try {
            return $this->success(
                new UserResource($user->load(['roles', 'department'])),
                'User retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Failed to fetch user: ' . $e->getMessage());
            return $this->error('Failed to retrieve user', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified user in storage.
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function update(Request $request, User $user): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
                'status' => 'sometimes|in:active,inactive,suspended',
                'role_id' => 'sometimes|exists:roles,id',
                'department_id' => 'nullable|exists:departments,id',
                'phone' => 'nullable|string|max:20',
            ]);

            $user->update($validated);

            if ($request->has('role_id')) {
                $user->roles()->sync([$validated['role_id']]);
            }

            return $this->success(
                new UserResource($user->load(['roles', 'department'])),
                'User updated successfully'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed', Response::HTTP_UNPROCESSABLE_ENTITY, $e->errors());
        } catch (\Exception $e) {
            Log::error('Failed to update user: ' . $e->getMessage());
            return $this->error('Failed to update user', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified user from storage.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function destroy(User $user): JsonResponse
    {
        try {
            // Prevent deleting yourself
            if (auth()->id() === $user->id) {
                return $this->error('You cannot delete your own account', Response::HTTP_FORBIDDEN);
            }

            $user->delete();

            return $this->success(
                null,
                'User deleted successfully',
                Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            Log::error('Failed to delete user: ' . $e->getMessage());
            return $this->error('Failed to delete user', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update user status.
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function updateStatus(Request $request, User $user): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:active,inactive,suspended',
            ]);

            // Prevent changing your own status
            if (auth()->id() === $user->id) {
                return $this->error('You cannot change your own status', Response::HTTP_FORBIDDEN);
            }

            $user->update(['status' => $validated['status']]);

            return $this->success(
                new UserResource($user->load(['roles', 'department'])),
                'User status updated successfully'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed', Response::HTTP_UNPROCESSABLE_ENTITY, $e->errors());
        } catch (\Exception $e) {
            Log::error('Failed to update user status: ' . $e->getMessage());
            return $this->error('Failed to update user status', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Reset user password.
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function resetPassword(Request $request, User $user): JsonResponse
    {
        try {
            $validated = $request->validate([
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user->update([
                'password' => Hash::make($validated['password']),
                'password_changed_at' => now(),
            ]);

            return $this->success(
                null,
                'Password reset successfully',
                Response::HTTP_OK
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed', Response::HTTP_UNPROCESSABLE_ENTITY, $e->errors());
        } catch (\Exception $e) {
            Log::error('Failed to reset password: ' . $e->getMessage());
            return $this->error('Failed to reset password', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
