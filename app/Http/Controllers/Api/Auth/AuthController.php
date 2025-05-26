<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\Auth\LoginRequest;
use App\Http\Requests\API\Auth\RegisterRequest;
use App\Http\Requests\API\Auth\ForgotPasswordRequest;
use App\Http\Requests\API\Auth\ResetPasswordRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->middleware('auth:api', ['except' => ['login', 'register', 'forgotPassword', 'reset']]);
        $this->authService = $authService;
    }

    /**
     * Authenticate a user and return the token.
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    /**
     * Authenticate a user and return the JWT token.
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    /**
     * Authenticate a user and return the JWT token.
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        
        try {
            $result = $this->authService->login($credentials);
            
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Invalid credentials',
                    'status_code' => $result['status_code'] ?? 401
                ], $result['status_code'] ?? 401);
            }
            
            return response()->json([
                'success' => true,
                'access_token' => $result['token'],
                'token_type' => 'bearer',
                'expires_in' => $result['expires_in'] ?? (config('jwt.ttl', 1440) * 60),
                'user' => $result['user'] ?? null,
                'message' => $result['message'] ?? 'Successfully logged in'
            ]);
            
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not create token',
                'error' => $e->getMessage(),
                'status_code' => 500
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'error' => $e->getMessage(),
                'status_code' => 500
            ], 500);
        }
    }

    /**
     * Register a new user.
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    /**
     * Register a new user and return a success response.
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    /**
     * Register a new user and return a success response.
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->register($request->validated());
            
            // Generate token for the registered user
            $token = Auth::login($user);
            
            // Get token expiration time from JWT config
            $ttl = config('jwt.ttl', 1440);
            
            return response()->json([
                'success' => true,
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => $ttl * 60, // in seconds
                'message' => 'User registered successfully',
                'user' => $user
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
                'status_code' => 500
            ], 500);
        }
    }

    /**
     * Get the authenticated User.
     *
     * @return JsonResponse
     */
    /**
     * Get the authenticated User.
     *
     * @return JsonResponse
     */
    /**
     * Get the authenticated User.
     *
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // If user is not found, return an unauthorized response
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                    'status_code' => 401
                ], 401);
            }
            
            return response()->json([
                'success' => true,
                'user' => $user
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user data',
                'error' => $e->getMessage(),
                'status_code' => 500
            ], 500);
        }
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return JsonResponse
     */
    /**
     * Log the user out (Invalidate the token).
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        try {
            $result = $this->authService->logout();
            
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Logout failed',
                    'status_code' => $result['status_code'] ?? 500
                ], $result['status_code'] ?? 500);
            }
            
            return response()->json([
                'success' => true,
                'message' => $result['message'] ?? 'Successfully logged out'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'error' => $e->getMessage(),
                'status_code' => 500
            ], 500);
        }
    }

    /**
     * Refresh a token.
     *
     * @return JsonResponse
     */
    /**
     * Refresh a token.
     *
     * @return JsonResponse
     */
    public function refresh(): JsonResponse
    {
        try {
            $result = $this->authService->refreshToken();
            
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Token refresh failed',
                    'status_code' => $result['status_code'] ?? 401
                ], $result['status_code'] ?? 401);
            }
            
            return response()->json([
                'success' => true,
                'access_token' => $result['token'],
                'token_type' => 'bearer',
                'expires_in' => $result['expires_in'] ?? (config('jwt.ttl', 1440) * 60),
                'user' => $result['user'] ?? null,
                'message' => 'Token refreshed successfully'
            ]);
            
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not refresh token',
                'error' => $e->getMessage(),
                'status_code' => 401
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token refresh failed',
                'error' => $e->getMessage(),
                'status_code' => 500
            ], 500);
        }
    }

    /**
     * Send password reset link.
     *
     * @param ForgotPasswordRequest $request
     * @return JsonResponse
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->sendResetLinkEmail($request->only('email'));
            
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to send reset link',
                    'status_code' => $result['status_code'] ?? 400
                ], $result['status_code'] ?? 400);
            }
            
            return response()->json([
                'success' => true,
                'message' => $result['message'] ?? 'Password reset link sent to your email',
                'status' => $result['status'] ?? Password::RESET_LINK_SENT
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process password reset request',
                'error' => $e->getMessage(),
                'status_code' => 500
            ], 500);
        }
    }

    /**
     * Reset password.
     *
     * @param ResetPasswordRequest $request
     * @return JsonResponse
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->resetUserPassword([
                'email' => $request->email,
                'password' => $request->password,
                'password_confirmation' => $request->password_confirmation,
                'token' => $request->token
            ]);
            
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to reset password',
                    'status' => $result['status'] ?? null,
                    'status_code' => $result['status_code'] ?? 400
                ], $result['status_code'] ?? 400);
            }
            
            return response()->json([
                'success' => true,
                'message' => $result['message'] ?? 'Password has been reset successfully',
                'status' => $result['status'] ?? Password::PASSWORD_RESET
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password',
                'error' => $e->getMessage(),
                'status_code' => 500
            ], 500);
        }
    }
    /**
     * Get the token array structure.
     *
     * @param string $token
     * @return JsonResponse
     */
    /**
     * Get the token array structure.
     *
     * @param string $token
     * @param array $additionalData
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function respondWithToken(string $token, array $additionalData = [], int $statusCode = 200): JsonResponse
    {
        $ttl = config('jwt.ttl', 1440); // Default to 24 hours
        
        $response = [
            'success' => true,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $ttl * 60, // Convert to seconds
        ];
        
        if (!empty($additionalData)) {
            $response = array_merge($response, $additionalData);
        }
        
        return response()->json($response, $statusCode);
    }
}
