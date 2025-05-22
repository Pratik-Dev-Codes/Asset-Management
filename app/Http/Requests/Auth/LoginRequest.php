<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * The maximum number of attempts to allow.
     *
     * @var int
     */
    public $maxAttempts = 5;

    /**
     * The number of minutes to throttle for.
     *
     * @var int
     */
    public $decayMinutes = 15;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc,dns', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'remember' => ['boolean'],
        ];
    }

    /**
     * Get the validation messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'The email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'password.required' => 'The password is required.',
            'password.min' => 'The password must be at least 8 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'email' => strtolower(trim($this->email)),
        ]);
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // Check if the user exists
        $user = User::where('email', $this->email)->first();

        // If user doesn't exist or password doesn't match
        if (!$user || !Hash::check($this->password, $user->password)) {
            $this->handleFailedLogin($user);
            return;
        }

        // Check if the user is active
        if (!$this->isUserActive($user)) {
            $this->handleInactiveUser($user);
            return;
        }

        // Check if email is verified if required
        if (config('auth.verify_email') && !$user->hasVerifiedEmail()) {
            $this->handleUnverifiedEmail($user);
            return;
        }

        // Attempt to authenticate the user
        if (!Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            $this->handleFailedLogin($user);
            return;
        }

        // Login successful, clear login attempts and update last login
        $this->clearLoginAttempts();
        $this->updateLastLogin($user);
    }

    /**
     * Handle a failed login attempt.
     *
     * @param  \App\Models\User|null  $user
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function handleFailedLogin($user): void
    {
        RateLimiter::hit($this->throttleKey());
        $this->incrementLoginAttempts($user);

        throw ValidationException::withMessages([
            'email' => [trans('auth.failed')],
        ]);
    }

    /**
     * Handle an inactive user.
     *
     * @param  \App\Models\User  $user
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function handleInactiveUser($user): void
    {
        $this->incrementLoginAttempts($user);

        throw ValidationException::withMessages([
            'email' => 'Your account has been deactivated. Please contact support.',
        ]);
    }

    /**
     * Handle unverified email.
     *
     * @param  \App\Models\User  $user
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function handleUnverifiedEmail($user): void
    {
        $user->sendEmailVerificationNotification();

        throw ValidationException::withMessages([
            'email' => 'Please verify your email address before logging in. '.
                       'A new verification link has been sent to your email address.',
        ]);
    }

    /**
     * Check if the user is active.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    protected function isUserActive($user): bool
    {
        // Add any additional checks for user status here
        return $user->is_active ?? true;
    }

    /**
     * Update the user's last login timestamp.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    protected function updateLastLogin($user): void
    {
        $user->last_login_at = now();
        $user->last_login_ip = $this->ip();
        $user->save();
    }

    /**
     * Increment the login attempts for the user.
     *
     * @param  \App\Models\User|null  $user
     * @return void
     */
    protected function incrementLoginAttempts($user): void
    {
        if ($user) {
            $user->increment('login_attempts');
            $user->last_attempt_at = now();
            $user->save();
        }
    }

    /**
     * Clear the login attempts for the user.
     *
     * @return void
     */
    protected function clearLoginAttempts(): void
    {
        RateLimiter::clear($this->throttleKey());
        
        // Reset login attempts counter if user exists
        if ($user = User::where('email', $this->email)->first()) {
            $user->update([
                'login_attempts' => 0,
                'last_attempt_at' => null,
            ]);
        }
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
