<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found',
                    'code' => 'user_not_found'
                ], 404);
            }
            
            // Set the authenticated user on the request
            $request->setUserResolver(function () use ($user) {
                return $user;
            });
            
            // Also add it to the request for backward compatibility
            $request->merge(['user' => $user]);
            
        } catch (TokenExpiredException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token has expired',
                'code' => 'token_expired'
            ], 401);
            
        } catch (TokenInvalidException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token is invalid',
                'code' => 'token_invalid'
            ], 401);
            
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Authorization token not found',
                'code' => 'token_absent'
            ], 401);
        }
        
        return $next($request);
    }
}
