<?php

namespace App\Http\Controllers\API\Auth;

<<<<<<< HEAD
use App\Http\Controllers\API\BaseApiController;
use App\Http\Requests\API\Auth\ForgotPasswordRequest;
use App\Http\Requests\API\Auth\LoginRequest;
use App\Http\Requests\API\Auth\RegisterRequest;
=======
use App\Http\Controllers\Controller;
use App\Http\Requests\API\Auth\LoginRequest;
use App\Http\Requests\API\Auth\RegisterRequest;
use App\Http\Requests\API\Auth\ForgotPasswordRequest;
>>>>>>> main
use App\Http\Requests\API\Auth\ResetPasswordRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
<<<<<<< HEAD
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
=======
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
>>>>>>> main
    protected $authService;

    /**
     * Create a new AuthController instance.
     *
     * @param  AuthService  $authService
     * @return void
     */
    public function __construct(AuthService $authService)
    {
<<<<<<< HEAD
        $this->middleware('auth:api', ['except' => ['login', 'register', 'forgotPassword', 'resetPassword']]);
=======
        $this->middleware('auth:api', ['except' => ['login', 'register', 'forgotPassword', 'reset']]);
>>>>>>> main
        $this->authService = $authService;
    }

    /**
<<<<<<< HEAD
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
=======
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
>>>>>>> main
        }
    }

    /**
<<<<<<< HEAD
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
=======
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
>>>>>>> main
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
<<<<<<< HEAD
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
=======
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
>>>>>>> main
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
<<<<<<< HEAD
            return $this->success(['user' => $request->user()]);
        } catch (\Exception $e) {
            Log::error('Get user error: ' . $e->getMessage());
            return $this->serverError('An error occurred fetching user data', $e);
=======
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
>>>>>>> main
        }
    }

    /**
<<<<<<< HEAD
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
=======
     * Send password reset link.
     *
     * @param ForgotPasswordRequest $request
     * @return JsonResponse
>>>>>>> main
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
<<<<<<< HEAD
            $status = Password::sendResetLink(
                $request->only('email')
            );

            return $status === Password::RESET_LINK_SENT
                ? $this->success([], 'Password reset link sent to your email')
                : $this->error('Unable to send password reset link', 400);
        } catch (\Exception $e) {
            Log::error('Forgot password error: ' . $e->getMessage());
            return $this->serverError('An error occurred processing your request', $e);
=======
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
>>>>>>> main
        }
    }

    /**
<<<<<<< HEAD
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
=======
     * Reset password.
     *
     * @param ResetPasswordRequest $request
     * @return JsonResponse
>>>>>>> main
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        try {
<<<<<<< HEAD
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
=======
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
>>>>>>> main
    }
}
