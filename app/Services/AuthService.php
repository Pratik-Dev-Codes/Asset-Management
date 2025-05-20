<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    /**
     * Authenticate a user and return token.
     *
     * @param array $credentials
     * @return array
     */
    public function login(array $credentials): array
    {
        if (!Auth::attempt($credentials)) {
            return [
                'success' => false,
                'message' => 'Unauthorized',
                'status_code' => 401
            ];
        }

        $user = Auth::user();
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->plainTextToken;

        return [
            'success' => true,
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user
            ],
            'message' => 'Successfully logged in'
        ];
    }

    /**
     * Register a new user.
     *
     * @param array $data
     * @return array
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'email_verification_token' => Str::random(60)
        ]);

        // Send verification email here if needed

        return [
            'success' => true,
            'data' => $user,
            'message' => 'User registered successfully. Please check your email to verify your account.'
        ];
    }

    /**
     * Logout the user.
     *
     * @param User $user
     * @return void
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    /**
     * Refresh the access token.
     *
     * @return array
     */
    public function refresh(): array
    {
        $user = Auth::user();
        $user->tokens()->delete();
        $token = $user->createToken('Personal Access Token')->plainTextToken;

        return [
            'access_token' => $token,
            'token_type' => 'Bearer'
        ];
    }

    /**
     * Reset user password.
     *
     * @param User $user
     * @param string $password
     * @return void
     */
    public function resetPassword(User $user, string $password): void
    {
        $user->password = Hash::make($password);
        $user->save();
    }
}
