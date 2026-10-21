<?php

namespace App\Providers;

use App\Services\SonicPesaService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SonicPesaService::class, function () {
            return new SonicPesaService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // 1. General API requests limiter
        RateLimiter::for('api', function (Request $request) {
            $user = $request->user();
            $maxAttempts = $user ? (int) env('RATE_LIMIT_API_AUTH', 120) : (int) env('RATE_LIMIT_API_GUEST', 60);

            return Limit::perMinute($maxAttempts)
                ->by($user ? $user->id : $request->ip())
                ->response(function (Request $request, array $headers) {
                    $retryAfter = $headers['Retry-After'] ?? 60;

                    return response()->json([
                        'message' => "Too many requests. Please wait {$retryAfter} seconds before trying again.",
                        'retry_after' => (int) $retryAfter,
                    ], 429, $headers);
                });
        });

        // 2. Login & Authentication limiter (prevents credential stuffing)
        RateLimiter::for('auth', function (Request $request) {
            $identifier = strtolower(trim($request->input('employee_id') ?: $request->input('email') ?: ''));
            $maxAttempts = (int) env('RATE_LIMIT_AUTH', 10);

            return Limit::perMinute($maxAttempts)
                ->by($request->ip().'|'.$identifier)
                ->response(function (Request $request, array $headers) {
                    $retryAfter = $headers['Retry-After'] ?? 60;

                    return response()->json([
                        'message' => "Too many login attempts. Please wait {$retryAfter} seconds before trying again.",
                        'retry_after' => (int) $retryAfter,
                    ], 429, $headers);
                });
        });

        // 3. OTP & Password Reset Request limiter (prevents email spamming)
        RateLimiter::for('otp', function (Request $request) {
            $email = strtolower(trim($request->input('email') ?: ''));
            $maxAttempts = (int) env('RATE_LIMIT_OTP', 5);

            return Limit::perMinute($maxAttempts)
                ->by($request->ip().'|'.$email)
                ->response(function (Request $request, array $headers) {
                    $retryAfter = $headers['Retry-After'] ?? 60;

                    return response()->json([
                        'message' => "Verification code request limit reached. Please wait {$retryAfter} seconds before requesting a new code.",
                        'retry_after' => (int) $retryAfter,
                    ], 429, $headers);
                });
        });

        // 4. OTP Verification Attempts limiter (prevents code brute-forcing)
        RateLimiter::for('otp-verify', function (Request $request) {
            $email = strtolower(trim($request->input('email') ?: ''));
            $maxAttempts = (int) env('RATE_LIMIT_OTP_VERIFY', 10);

            return Limit::perMinute($maxAttempts)
                ->by($request->ip().'|'.$email)
                ->response(function (Request $request, array $headers) {
                    $retryAfter = $headers['Retry-After'] ?? 60;

                    return response()->json([
                        'message' => "Too many verification attempts. Please wait {$retryAfter} seconds before trying again.",
                        'retry_after' => (int) $retryAfter,
                    ], 429, $headers);
                });
        });

        // 5. Attendance Action limiter (prevents double check-ins and hammering)
        RateLimiter::for('attendance', function (Request $request) {
            $user = $request->user();
            $maxAttempts = (int) env('RATE_LIMIT_ATTENDANCE', 15);

            return Limit::perMinute($maxAttempts)
                ->by($user ? $user->id : $request->ip())
                ->response(function (Request $request, array $headers) {
                    $retryAfter = $headers['Retry-After'] ?? 60;

                    return response()->json([
                        'message' => "Attendance action rate limit reached. Please wait {$retryAfter} seconds before trying again.",
                        'retry_after' => (int) $retryAfter,
                    ], 429, $headers);
                });
        });

        // 6. Push Notification Broadcast limiter (prevents alert spamming)
        RateLimiter::for('broadcast', function (Request $request) {
            $user = $request->user();
            $maxAttempts = (int) env('RATE_LIMIT_BROADCAST', 5);

            return Limit::perMinute($maxAttempts)
                ->by($user ? $user->id : $request->ip())
                ->response(function (Request $request, array $headers) {
                    $retryAfter = $headers['Retry-After'] ?? 60;

                    return response()->json([
                        'message' => "Broadcast dispatch limit reached. Please wait {$retryAfter} seconds before sending another broadcast.",
                        'retry_after' => (int) $retryAfter,
                    ], 429, $headers);
                });
        });

        // 7. Mobile Money Payment Checkout limiter (prevents duplicate pushes)
        RateLimiter::for('payment', function (Request $request) {
            $user = $request->user();
            $maxAttempts = (int) env('RATE_LIMIT_PAYMENT', 5);

            return Limit::perMinute($maxAttempts)
                ->by($user ? $user->id : $request->ip())
                ->response(function (Request $request, array $headers) {
                    $retryAfter = $headers['Retry-After'] ?? 60;

                    return response()->json([
                        'message' => "Payment initiation limit reached. Please wait {$retryAfter} seconds before trying again.",
                        'retry_after' => (int) $retryAfter,
                    ], 429, $headers);
                });
        });

        // 8. Promo Code Redemption limiter (prevents voucher dictionary attacks)
        RateLimiter::for('promo', function (Request $request) {
            $user = $request->user();
            $maxAttempts = (int) env('RATE_LIMIT_PROMO', 6);

            return Limit::perMinute($maxAttempts)
                ->by($user ? ($user->org_id ?: $user->id) : $request->ip())
                ->response(function (Request $request, array $headers) {
                    $retryAfter = $headers['Retry-After'] ?? 60;

                    return response()->json([
                        'message' => "Too many promo code attempts. Please wait {$retryAfter} seconds before trying again.",
                        'retry_after' => (int) $retryAfter,
                    ], 429, $headers);
                });
        });

        // 9. Report Export limiter (protects server CPU/memory against heavy PDF rendering)
        RateLimiter::for('reports', function (Request $request) {
            $user = $request->user();
            $maxAttempts = (int) env('RATE_LIMIT_REPORTS', 20);

            return Limit::perMinute($maxAttempts)
                ->by($user ? $user->id : $request->ip())
                ->response(function (Request $request, array $headers) {
                    $retryAfter = $headers['Retry-After'] ?? 60;

                    return response()->json([
                        'message' => "Report generation limit reached. Please wait {$retryAfter} seconds before exporting again.",
                        'retry_after' => (int) $retryAfter,
                    ], 429, $headers);
                });
        });

        // 10. Client Error Ingestion limiter (prevents crash report floods)
        RateLimiter::for('errors', function (Request $request) {
            $maxAttempts = (int) env('RATE_LIMIT_ERRORS', 60);

            return Limit::perMinute($maxAttempts)
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    $retryAfter = $headers['Retry-After'] ?? 60;

                    return response()->json([
                        'message' => 'Error report submission limit reached.',
                        'retry_after' => (int) $retryAfter,
                    ], 429, $headers);
                });
        });
    }
}
