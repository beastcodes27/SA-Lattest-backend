<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Services\OtpService;
use App\Support\PasswordPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class PasswordResetController extends Controller
{
    private const GENERIC_MESSAGE = 'If an account exists for that email, a reset code has been sent.';

    public function sendCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Enter a valid email address.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $email = strtolower(trim((string) $request->input('email')));
        $rateLimitKey = 'password-reset-email:'.sha1($email.'|'.$request->ip());
        $dailyLimit = max(1, (int) config('services.email.password_reset_daily_limit', 2));

        if (RateLimiter::tooManyAttempts($rateLimitKey, $dailyLimit)) {
            $retryAfter = RateLimiter::availableIn($rateLimitKey);
            $hours = max(1, (int) ceil($retryAfter / 3600));

            return response()
                ->json([
                    'message' => "You have reached the limit of {$dailyLimit} reset emails per day. Please try again in about {$hours} hour(s).",
                    'retry_after' => $retryAfter,
                ], 429)
                ->header('Retry-After', (string) $retryAfter);
        }

        // Count the request before looking up the account so responses do not
        // reveal whether an email address exists.
        RateLimiter::hit($rateLimitKey, 86400);

        $user = User::where('email', $email)->first();

        if ($user) {
            try {
                OtpService::send($user, 'password_reset', 'password reset');
            } catch (\Throwable $e) {
                Log::error('Failed to email password reset code: '.$e->getMessage(), ['email' => $email]);
            }
        }

        return response()->json([
            'message' => self::GENERIC_MESSAGE,
            'expires_in_minutes' => OtpService::TTL_MINUTES,
        ]);
    }

    public function verifyCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:255'],
            'code' => ['required', 'digits:6'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $email = strtolower(trim((string) $request->input('email')));
        $user = User::where('email', $email)->first();

        if (! $user || ! OtpService::check($user, 'password_reset', (string) $request->input('code'))) {
            return response()->json(['message' => 'This reset code is invalid or has expired. Please request a new one.'], 422);
        }

        return response()->json([
            'message' => 'Code verified. Set your new password.',
            'verified' => true,
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:255'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'confirmed', PasswordPolicy::rule()],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $email = strtolower(trim((string) $request->input('email')));
        $user = User::where('email', $email)->first();

        if (! $user || ! OtpService::verify($user, 'password_reset', (string) $request->input('code'))) {
            return response()->json(['message' => 'This reset code is invalid or has expired. Please request a new one.'], 422);
        }

        $user->forceFill([
            'password' => $request->input('password'),
            'must_change_password' => false,
        ])->save();

        $user->tokens()->delete();

        return response()->json([
            'message' => 'Your password has been reset. You can now sign in with your new password.',
        ]);
    }
}
