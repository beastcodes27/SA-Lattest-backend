<?php

namespace App\Http\Controllers\Api;

use App\Mail\PasswordResetCode;
use App\Models\User;
use App\Services\EmailService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class PasswordResetController extends Controller
{
    private const CODE_TTL_MINUTES = 15;

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
        $rateLimitKey = 'password-reset-email:'.sha1($email);
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

        RateLimiter::hit($rateLimitKey, 86400);

        $user = User::where('email', $email)->first();

        if ($user) {
            $code = (string) random_int(100000, 999999);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                ['token' => Hash::make($code), 'created_at' => now()]
            );

            try {
                EmailService::send($email, new PasswordResetCode($user, $code));
            } catch (\Throwable $e) {
                Log::error('Failed to email password reset code: '.$e->getMessage(), ['email' => $email]);
            }
        }

        return response()->json([
            'message' => 'If an account exists for that email, a reset code has been sent.',
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:255'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $email = strtolower(trim((string) $request->input('email')));
        $reset = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (! $reset || ! $reset->created_at || Carbon::parse($reset->created_at)->lt(now()->subMinutes(self::CODE_TTL_MINUTES))) {
            return response()->json(['message' => 'This reset code is invalid or has expired. Please request a new one.'], 422);
        }

        if (! Hash::check((string) $request->input('code'), $reset->token)) {
            return response()->json(['message' => 'This reset code is invalid or has expired. Please request a new one.'], 422);
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return response()->json(['message' => 'This reset code is invalid or has expired. Please request a new one.'], 422);
        }

        $user->forceFill([
            'password' => $request->input('password'),
            'must_change_password' => false,
        ])->save();

        $user->tokens()->delete();
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return response()->json([
            'message' => 'Your password has been reset. You can now sign in with your new password.',
        ]);
    }
}
