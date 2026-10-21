<?php

namespace App\Services;

use App\Mail\OneTimePasscode;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmailVerificationService
{
    public const CODE_TTL_MINUTES = 15;

    public const MAX_ATTEMPTS = 5;

    public static function normalize(string $email): string
    {
        return strtolower(trim($email));
    }

    /**
     * Generate a new six-digit code for the address and email it.
     */
    public static function send(string $email, ?string $name = null): bool
    {
        $email = self::normalize($email);
        $code = (string) random_int(100000, 999999);

        DB::table('email_verification_codes')->updateOrInsert(
            ['email' => $email],
            [
                'code' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return EmailService::send(
            $email,
            new OneTimePasscode($code, $name, 'email verification', self::CODE_TTL_MINUTES)
        );
    }

    /**
     * Validate a code without consuming it. Wrong guesses count towards the
     * attempt cap; successful checks leave the code usable for registration.
     */
    public static function check(string $email, string $code): bool
    {
        $email = self::normalize($email);
        $record = DB::table('email_verification_codes')->where('email', $email)->first();

        if (! $record) {
            return false;
        }

        if (Carbon::parse($record->expires_at)->isPast()) {
            DB::table('email_verification_codes')->where('email', $email)->delete();

            return false;
        }

        if ((int) $record->attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        if (! Hash::check($code, $record->code)) {
            DB::table('email_verification_codes')->where('email', $email)->increment('attempts', 1, [
                'updated_at' => now(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Remove the code after it has been successfully used.
     */
    public static function consume(string $email): void
    {
        DB::table('email_verification_codes')->where('email', self::normalize($email))->delete();
    }
}
