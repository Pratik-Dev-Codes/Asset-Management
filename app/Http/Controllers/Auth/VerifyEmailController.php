<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Carbon\Carbon;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     *
     * @param  \Illuminate\Foundation\Auth\EmailVerificationRequest  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function __invoke(EmailVerificationRequest $request)
    {
        $user = $request->user();

        // Check if the user is already verified
        if ($user->hasVerifiedEmail()) {
            return $this->sendAlreadyVerifiedResponse($request);
        }

        try {
            // Mark email as verified
            $user->email_verified_at = now();
            $user->last_verified_at = now();
            $user->save();

            // Trigger the verified event
            event(new Verified($user));

            // Log the verification
            Log::info('Email verified successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            // Return appropriate response
            return $this->sendVerificationSuccessResponse($request);

        } catch (\Exception $e) {
            Log::error('Email verification failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email verification failed. Please try again later.',
                    'error' => config('app.debug') ? $e->getMessage() : null,
                ], 500);
            }

            return redirect()
                ->route('verification.notice')
                ->with('error', 'Email verification failed. Please try again later.');
        }
    }

    /**
     * Resend the email verification notification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function resend(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No account found with this email address.',
                ], 404);
            }
            return back()->with('error', 'No account found with this email address.');
        }

        if ($user->hasVerifiedEmail()) {
            return $this->sendAlreadyVerifiedResponse($request);
        }

        try {
            $user->sendEmailVerificationNotification();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'A fresh verification link has been sent to your email address.',
                ]);
            }

            return back()->with('status', 'A fresh verification link has been sent to your email address.');

        } catch (\Exception $e) {
            Log::error('Failed to resend verification email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send verification email. Please try again later.',
                ], 500);
            }

            return back()->with('error', 'Failed to send verification email. Please try again later.');
        }
    }

    /**
     * Send response for already verified email.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    protected function sendAlreadyVerifiedResponse(Request $request)
    {
        $message = 'Your email is already verified.';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return redirect()
            ->intended(RouteServiceProvider::HOME)
            ->with('status', $message);
    }

    /**
     * Send successful verification response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    protected function sendVerificationSuccessResponse(Request $request)
    {
        $message = 'Your email has been verified successfully!';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'user' => $request->user()->load('roles', 'permissions'),
            ]);
        }

        return redirect()
            ->intended(RouteServiceProvider::HOME)
            ->with('status', $message);
    }
}
