<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\V2\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * @group Authentication
 *
 * APIs for user authentication
 */
class AuthController extends Controller
{
    /**
     * Login user and create token
     *
     * @bodyParam email string required The email of the user. Example: admin@neepco.com
     * @bodyParam password string required The password of the user. Example: password
     * @bodyParam device_name string The device name. Example: iPhone 13
     *
     * @response 200 {
     *  "user": {
     *    "id": 1,
     *    "name": "Admin User",
     *    "email": "admin@neepco.com",
     *    "roles": ["admin"],
     *    "permissions": ["manage_users", "manage_assets"]
     *  },
     *  "access_token": "1|abcdef123456",
     *  "token_type": "Bearer"
     * }
     * @response 422 {
     *  "message": "The given data was invalid.",
     *  "errors": {
     *    "email": ["The email field is required."],
     *    "password": ["The password field is required."]
     *  }
     * }
     * @response 401 {
     *  "message": "The provided credentials are incorrect."
     * }
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'device_name' => 'required|string',
        ]);

        if (! Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = $request->user();

        // Revoke all tokens...
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Get the authenticated User
     *
     * @authenticated
     *
     * @response 200 {
     *  "data": {
     *    "id": 1,
     *    "name": "Admin User",
     *    "email": "admin@neepco.com",
     *    "roles": ["admin"],
     *    "permissions": ["manage_users", "manage_assets"]
     *  }
     * }
     */
    public function me(Request $request)
    {
        return new UserResource($request->user());
    }

    /**
     * Logout user (Revoke the token)
     *
     * @authenticated
     *
     * @response 204
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    /**
     * Refresh the token
     *
     * @authenticated
     *
     * @response 200 {
     *  "access_token": "new_token_here",
     *  "token_type": "Bearer"
     * }
     */
    public function refresh(Request $request)
    {
        $user = $request->user();

        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        // Create new token
        $token = $user->createToken($request->header('User-Agent') ?: 'api-token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }
}
