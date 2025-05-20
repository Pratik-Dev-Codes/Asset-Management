<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class SecurityServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->configurePasswordDefaults();
        $this->registerValidators();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Login rate limiting
        RateLimiter::for('login', function (Request $request) {
            $maxAttempts = config('security.rate_limiting.login.max_attempts', 5);
            $decayMinutes = config('security.rate_limiting.login.decay_minutes', 15);

            return Limit::perMinutes($decayMinutes, $maxAttempts)
                ->by($request->input('email').'|'.$request->ip());
        });

        // Global throttle for all requests
        RateLimiter::for('global', function (Request $request) {
            return Limit::perMinute(1000)->by($request->ip());
        });
    }

    /**
     * Configure the default password validation rules.
     */
    protected function configurePasswordDefaults(): void
    {
        Password::defaults(function () {
            $rule = Password::min(config('security.password.min_length', 12));

            if (config('security.password.require_mixed_case', true)) {
                $rule->mixedCase();
            }

            if (config('security.password.require_numbers', true)) {
                $rule->numbers();
            }

            if (config('security.password.require_symbols', true)) {
                $rule->symbols();
            }

            if (config('security.password.uncompromised', true)) {
                $rule->uncompromised();
            }

            return $rule;
        });
    }

    /**
     * Register custom validation rules.
     */
    protected function registerValidators(): void
    {
        // Secure URL validation
        Validator::extend('secure_url', function ($attribute, $value, $parameters, $validator) {
            if (empty($value)) {
                return true;
            }

            $parsed = parse_url($value);

            if (! isset($parsed['scheme']) || ! in_array(strtolower($parsed['scheme']), ['http', 'https'])) {
                return false;
            }

            // Validate hostname format
            if (! isset($parsed['host']) || ! preg_match('/^[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,}$/i', $parsed['host'])) {
                return false;
            }

            // Check for common XSS patterns
            if (preg_match('/[<>\'\"]/', $value)) {
                return false;
            }

            return true;
        }, 'The :attribute must be a valid and secure URL.');

        // HTML validation to prevent XSS
        Validator::extend('no_html', function ($attribute, $value, $parameters, $validator) {
            return strip_tags($value) === $value;
        }, 'The :attribute field contains invalid characters.');
    }
}
