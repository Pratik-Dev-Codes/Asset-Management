<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     *
     * @return \Inertia\Response|\Illuminate\Http\RedirectResponse
     */
    public function create()
    {
        // Check if registration is enabled
        if (!config('auth.registration_enabled', true)) {
            return redirect()->route('login')
                ->with('error', 'Registration is currently disabled.');
        }

        return Inertia::render('Auth/Register', [
            'terms' => true,
            'privacy' => true,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        // Check if registration is enabled
        if (!config('auth.registration_enabled', true)) {
            return $this->registrationDisabledResponse($request);
        }

        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'terms' => ['required', 'accepted'],
        ], [
            'terms.required' => 'You must accept the terms and conditions.',
            'terms.accepted' => 'You must accept the terms and conditions.',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            return back()->withErrors($validator)->withInput();
        }

        // Start database transaction
        try {
            // Create the user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'email_verified_at' => config('auth.verify_email') ? null : now(),
            ]);

            // Assign default role if specified
            if ($defaultRole = config('auth.default_role')) {
                if ($role = Role::findByName($defaultRole)) {
                    $user->assignRole($role);
                }
            }

            // Trigger the registered event
            event(new Registered($user));


            // Log the user in
            Auth::login($user);


            // Handle API response
            if ($request->wantsJson()) {
                $token = $user->createToken('auth-token')->plainTextToken;
                
                return response()->json([
                    'success' => true,
                    'message' => 'Registration successful!',
                    'token' => $token,
                    'user' => $user->load('roles', 'permissions'),
                ], 201);
            }

            // Handle web response
            return redirect(RouteServiceProvider::HOME)
                ->with('success', 'Registration successful! Welcome to ' . config('app.name'));

        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage());
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registration failed. Please try again later.',
                    'error' => config('app.debug') ? $e->getMessage() : null,
                ], 500);
            }
            
            return back()->with('error', 'Registration failed. Please try again.');
        }
    }
    
    /**
     * Get the response for disabled registration.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    protected function registrationDisabledResponse(Request $request)
    {
        $message = 'Registration is currently disabled.';
        
        if ($request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 403);
        }
        
        return redirect()->route('login')
            ->with('error', $message);
    }
}
