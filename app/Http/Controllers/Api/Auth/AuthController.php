<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\API\BaseApiController;
use App\Http\Requests\API\Auth\ForgotPasswordRequest;
use App\Http\Requests\API\Auth\LoginRequest;
use App\Http\Requests\API\Auth\RegisterRequest;
use App\Http\Requests\API\Auth\ResetPasswordRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;

/**
 * @group Authentication
 *
 * APIs for user authentication and account management
 */
class AuthController extends BaseApiController
{
    /**
     * @var AuthService
     */
    protected $authService;

    /**
     * Create a new AuthController instance.
     *
     * @param  AuthService  $authService
     * @return void
     */
    public function __construct(AuthService $authService)
    {
        $this->middleware('auth:api', ['except' => ['login', 'register', 'forgotPassword', 'resetPassword']]);
        $this->authService = $authService;
    }

    /**
     * Login
     *
     * Authenticate a user and return an access token.
     *
     * @bodyParam email string required The user's email. Example: user@example.com
     * @bodyParam password string required The user's password. Example: password123
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
     *     "token_type": "bearer",
     *     "expires_in": 3600
     *   },
     *   "message": "Successfully logged in"
     * }
     * @response 401 {
     *   "success": false,
     *   "message": "Unauthorized"
     * }
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $credentials = $request->validated();
            $response = $this->authService->login($credentials);

            if ($response['success']) {
                return $this->success($response['data'], 'Successfully logged in');
            }

            return $this->unauthorized($response['message']);
        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            return $this->serverError('An error occurred during login', $e);
        }
    }

    /**
     * Register
     *
     * Register a new user account.
     *
     * @bodyParam name string required The user's name. Example: John Doe
     * @bodyParam email string required The user's email. Example: user@example.com
     * @bodyParam password string required The user's password. Example: password123
     * @bodyParam password_confirmation string required Confirm password. Must match password.
     *
     * @response 201 {
     *   "success": true,
     *   "data": {
     *     "user": {
     *       "name": "John Doe",
     *       "email": "user@example.com",
     *       "updated_at": "2023-01-01T12:00:00.000000Z",
     *       "created_at": "2023-01-01T12:00:00.000000Z",
     *       "id": 1
     *     },
     *     "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
     *   },
     *   "message": "User registered successfully"
     * }
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $response = $this->authService->register($data);

            if ($response['success']) {
                return $this->success($response['data'], 'User registered successfully', 201);
            }

            return $this->error($response['message'], $response['status_code'] ?? 400);
        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage());
            return $this->serverError('An error occurred during registration', $e);
        }
    }

    /**
     * Logout
     *
     * Invalidate the user's token.
     *
     * @authenticated
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Successfully logged out"
     * }
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $this->authService->logout($request->user());
            return $this->success([], 'Successfully logged out');
        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());
            return $this->serverError('An error occurred during logout', $e);
        }
    }

    /**
     * Refresh Token
     *
     * Refresh the user's access token.
     *
     * @authenticated
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
     *     "token_type": "bearer",
     *     "expires_in": 3600
     *   },
     *   "message": "Token refreshed successfully"
     * }
     */
    public function refresh(): JsonResponse
    {
        try {
            $response = $this->authService->refresh();
            return $this->success($response, 'Token refreshed successfully');
        } catch (\Exception $e) {
            Log::error('Token refresh error: ' . $e->getMessage());
            return $this->serverError('An error occurred refreshing token', $e);
        }
    }

    /**
     * Get Current User
     *
     * Get the currently authenticated user.
     *
     * @authenticated
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "user@example.com",
     *       "email_verified_at": "2023-01-01T12:00:00.000000Z",
     *       "created_at": "2023-01-01T12:00:00.000000Z",
     *       "updated_at": "2023-01-01T12:00:00.000000Z"
     *     }
     *   }
     * }
     */
    public function me(Request $request): JsonResponse
    {
        try {
            return $this->success(['user' => $request->user()]);
        } catch (\Exception $e) {
            Log::error('Get user error: ' . $e->getMessage());
            return $this->serverError('An error occurred fetching user data', $e);
        }
    }

    /**
     * Forgot Password
     *
     * Send a password reset link to the given user.
     *
     * @bodyParam email string required The user's email. Example: user@example.com
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Password reset link sent to your email"
     * }
     * @response 400 {
     *   "success": false,
     *   "message": "Unable to send password reset link"
     * }
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );

            return $status === Password::RESET_LINK_SENT
                ? $this->success([], 'Password reset link sent to your email')
                : $this->error('Unable to send password reset link', 400);
        } catch (\Exception $e) {
            Log::error('Forgot password error: ' . $e->getMessage());
            return $this->serverError('An error occurred processing your request', $e);
        }
    }

    /**
     * Reset Password
     *
     * Reset the user's password.
     *
     * @bodyParam token string required The password reset token.
     * @bodyParam email string required The user's email. Example: user@example.com
     * @bodyParam password string required The new password. Example: newpassword123
     * @bodyParam password_confirmation string required Confirm new password. Must match password.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Password has been reset successfully"
     * }
     * @response 400 {
     *   "success": false,
     *   "message": "Unable to reset password"
     * }
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    $this->authService->resetPassword($user, $password);
                }
            );

            return $status === Password::PASSWORD_RESET
                ? $this->success([], 'Password has been reset successfully')
                : $this->error('Unable to reset password', 400);
        } catch (\Exception $e) {
            Log::error('Reset password error: ' . $e->getMessage());
            return $this->serverError('An error occurred while resetting password', $e);
        }
    }
}
