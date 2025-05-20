<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class HandleDarkMode
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
            // Check if dark mode is being toggled in the request
            if ($request->has('dark_mode')) {
                $darkMode = $request->boolean('dark_mode');
                session(['dark_mode' => $darkMode]);
                
                // Update user preference if authenticated
                if (Auth::check()) {
                    $user = User::find(Auth::id());
                    if ($user) {
                        $user->dark_mode = $darkMode;
                        $user->save();
                        Log::info("User {$user->id} dark mode preference updated to: " . ($darkMode ? 'dark' : 'light'));
                    }
                }
                
                // Set a cookie to remember the preference
                $this->setDarkModeCookie($darkMode);
            } 
            // If no session value is set, check user preference or system preference
            elseif (!session()->has('dark_mode')) {
                $darkMode = false;
                
                // Check if user is authenticated and has a preference
                if (Auth::check()) {
                    $user = User::find(Auth::id());
                    if ($user && $user->dark_mode !== null) {
                        $darkMode = (bool)$user->dark_mode;
                        Log::debug("Using user's dark mode preference: " . ($darkMode ? 'dark' : 'light'));
                    } else {
                        $darkMode = $this->getSystemDarkModePreference($request);
                    }
                } else {
                    $darkMode = $this->getSystemDarkModePreference($request);
                }
                
                session(['dark_mode' => $darkMode]);
                $this->setDarkModeCookie($darkMode);
            }
            
            // Share dark mode status with all views
            view()->share('darkMode', session('dark_mode', false));
            
            return $next($request);
            
        } catch (\Exception $e) {
            Log::error('Error in HandleDarkMode middleware: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => Auth::id()
            ]);
            
            // Continue with default light mode if there's an error
            session(['dark_mode' => false]);
            return $next($request);
        }
    }
    
    /**
     * Get the system's dark mode preference
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function getSystemDarkModePreference($request)
    {
        // Check for cookie preference first
        if ($request->hasCookie('dark_mode')) {
            $cookieValue = $request->cookie('dark_mode');
            Log::debug("Using cookie dark mode preference: " . $cookieValue);
            return $cookieValue === 'true';
        }
        
        // Fall back to system preference
        $prefersDark = $request->prefersDarkMode();
        Log::debug("Using system dark mode preference: " . ($prefersDark ? 'dark' : 'light'));
        return $prefersDark;
    }
    
    /**
     * Set the dark mode cookie
     * 
     * @param bool $darkMode
     * @return void
     */
    protected function setDarkModeCookie($darkMode)
    {
        // Set a cookie that expires in 1 year
        $minutes = 60 * 24 * 365; // 1 year
        $expires = time() + ($minutes * 60);
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        $domain = config('session.domain') ?: $_SERVER['HTTP_HOST'] ?? '';
        
        // Handle subdomains if needed (e.g., .example.com)
        if ($domain && !empty($domain) && $domain !== 'localhost') {
            // Ensure domain starts with a dot for subdomains
            if (strpos($domain, '.') !== 0) {
                $domain = ".{$domain}";
            }
        } else {
            // For localhost, set to empty string
            $domain = '';
        }
        
        setcookie(
            'dark_mode',
            $darkMode ? 'true' : 'false',
            [
                'expires' => $expires,
                'path' => '/',
                'domain' => $domain,
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }
}
