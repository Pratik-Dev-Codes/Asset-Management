<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Log;

class AuthService
{
    /**
     * Authenticate a user and return token.
     */
    /**
     * Authenticate a user and return JWT token
     *
     * @param array $credentials
     * @return array
     */
    /**
     * Authenticate a user and return JWT token
     *
     * @param array $credentials
     * @return array
     */
    public function login(array $credentials): array
    {
        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return [
                    'success' => false,
                    'message' => 'Invalid email or password',
                    'status_code' => 401
                ];
            }

            $user = Auth::user();
            
            // Check if user is active
            if (isset($user->is_active) && !$user->is_active) {
                return [
                    'success' => false,
                    'message' => 'Your account has been deactivated',
                    'status_code' => 403
                ];
            }

            // Get token expiration time from JWT config
            $ttl = config('jwt.ttl', 1440); // Default to 24 hours
            
            return [
                'success' => true,
                'token' => $token,
                'user' => $user,
                'message' => 'Successfully logged in',
                'expires_in' => $ttl * 60 // in seconds
            ];
            
        } catch (JWTException $e) {
            Log::error('JWT Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Could not create token',
                'status_code' => 500
            ];
        } catch (\Exception $e) {
            Log::error('Login Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred during login',
                'status_code' => 500
            ];
        }
    }

    /**
     * Register a new user.
     */
    /**
     * Register a new user and return the user object
     *
     * @param array $data
     * @return User
     */
    /**
     * Register a new user
     *
     * @param array $data
     * @return User
     */
    public function register(array $data): User
    {
        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'email_verification_token' => Str::random(60),
            ]);

            // Assign default role if needed
            if (!isset($data['role'])) {
                $user->assignRole('user'); // Make sure you have this role in your database
            }

            return $user;
            
        } catch (\Exception $e) {
            Log::error('Registration Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Logout the user.
     */
    /**
     * Log the user out (Invalidate the token)
     *
     * @return void
     */
    /**
     * Log the user out (Invalidate the token)
     *
     * @return array
     */
    public function logout(): array
    {
        try {
            $token = JWTAuth::getToken();
            if ($token) {
                JWTAuth::invalidate($token);
            }
            
            return [
                'success' => true,
                'message' => 'Successfully logged out'
            ];
            
        } catch (JWTException $e) {
            Log::error('Logout Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to logout',
                'status_code' => 500
            ];
        }
    }

    /**
     * Refresh the access token.
     */
    /**
     * Refresh a token
     *
     * @param User $user
     * @return array
     */
    /**
     * Refresh a token
     *
     * @return array
     */
    public function refreshToken(): array
    {
        try {
            $token = JWTAuth::parseToken()->refresh();
            $user = JWTAuth::setToken($token)->toUser();
            
            // Get token expiration time from JWT config
            $ttl = config('jwt.ttl', 1440); // Default to 24 hours
            
            return [
                'success' => true,
                'token' => $token,
                'user' => $user,
                'expires_in' => $ttl * 60 // in seconds
            ];
        } catch (JWTException $e) {
            Log::error('Refresh Token Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Could not refresh token',
                'status_code' => 401
            ];
        } catch (\Exception $e) {
            Log::error('Refresh Token Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Could not refresh token',
                'status_code' => 500
            ];
        }
    }

    /**
     * Send a password reset link to a user.
     *
     * @param array $credentials
     * @return array
     */
    public function sendResetLinkEmail(array $credentials): array
    {
        try {
            $status = Password::sendResetLink(
                ['email' => $credentials['email']]
            );

            if ($status === Password::RESET_LINK_SENT) {
                return [
                    'success' => true,
                    'message' => __($status),
                    'status' => $status
                ];
            }

            return [
                'success' => false,
                'message' => __($status),
                'status' => $status,
                'status_code' => 400
            ];
        } catch (\Exception $e) {
            Log::error('Send Reset Link Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send password reset link',
                'status_code' => 500
            ];
        }
    }

    /**
     * Reset the user's password using Laravel's password reset functionality.
     *
     * @param array $credentials
     * @return array
     */
    public function resetUserPassword(array $credentials): array
    {
        try {
            $status = Password::reset(
                $credentials,
                function ($user, $password) {
                    $this->updateUserPassword($user, $password);
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return [
                    'success' => true,
                    'message' => __($status),
                    'status' => $status
                ];
            }

            return [
                'success' => false,
                'message' => __($status),
                'status' => $status,
                'status_code' => 400
            ];
        } catch (\Exception $e) {
            Log::error('Reset Password Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to reset password',
                'status_code' => 500
            ];
        }
    }

    /**
     * Update the user's password.
     *
     * @param User $user
     * @param string $password
     * @return void
     */
    public function updateUserPassword(User $user, string $password): void
    {
        $user->password = Hash::make($password);
        $user->save();
    }
}
