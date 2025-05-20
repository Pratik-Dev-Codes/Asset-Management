<?php

namespace App\Http\Middleware;

use Closure;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthenticateWithMFA
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string[]  ...$guards
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $user = $request->user();
        
        // If user is not authenticated, proceed to auth middleware
        if (!$user) {
            return $next($request);
        }
        
        // Skip MFA for API tokens and specific routes
        if ($this->shouldPassThrough($request)) {
            return $next($request);
        }
        
        // Check if MFA is required but not verified
        if ($user->mfa_enabled && !$this->mfaVerified($request)) {
            if ($request->isMethod('get') && !$request->is('mfa*')) {
                return redirect()->route('mfa.verify');
            }
            
            if ($request->is('mfa/verify') || $request->is('mfa/verify-otp')) {
                return $next($request);
            }
            
            return response()->json(['error' => 'MFA verification required'], 403);
        }
        
        // Check rate limiting for failed attempts
        $throttleKey = 'mfa:'.($user->id ?: $request->ip());
        
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->view('errors.429', ['retryAfter' => $seconds], 429);
        }
        
        // Verify MFA token if provided
        if ($request->is('mfa/verify-otp') && $request->isMethod('post')) {
            $google2fa = new Google2FA();
            $valid = $google2fa->verifyKey(
                $user->mfa_secret,
                $request->input('otp'),
                2  // 2 code window
            );
            
            if ($valid) {
                $request->session()->put('mfa_verified', now()->addHours(8)->timestamp);
                RateLimiter::clear($throttleKey);
                return redirect()->intended();
            }
            
            RateLimiter::hit($throttleKey);
            return back()->withErrors(['otp' => 'Invalid one-time password']);
        }
        
        return $next($request);
    }
    
    /**
     * Check if the request should pass through MFA verification
     */
    protected function shouldPassThrough(Request $request): bool
    {
        // Skip for API tokens
        if ($request->bearerToken()) {
            return true;
        }
        
        // Skip for MFA routes
        if ($request->is('mfa*') || $request->is('logout')) {
            return true;
        }
        
        // Skip for specific routes
        $except = [
            'login*',
            'password/*',
            'register',
            'verification/*',
            'two-factor*',
            'user/confirmed-password-status',
            'user/confirm-password',
            'sanctum/csrf-cookie',
        ];
        
        foreach ($except as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if MFA is verified in the current session
     */
    protected function mfaVerified(Request $request): bool
    {
        $verifiedAt = $request->session()->get('mfa_verified');
        
        if (!$verifiedAt) {
            return false;
        }
        
        return $verifiedAt > now()->timestamp;
    }
}
