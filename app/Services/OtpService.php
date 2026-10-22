<?php

namespace App\Services;

use App\Mail\OneTimePasscode;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OtpService
{
    public const TTL_MINUTES = 15;

    public const MAX_ATTEMPTS = 5;

    public const MAX_EMAILS_PER_HOUR = 5;

    public static function isAdmin(User $user): bool
    {
        return in_array($user->role, ['admin', 'org_admin', 'superadmin', 'minor_admin', 'sysadmin'], true);
    }

    /**
     * Generate and email a one-time code for a specific action.
     */
    public static function send(User $user, string $purpose, string $label): bool
    {
        $code = (string) random_int(100000, 999999);

        DB::table('otp_codes')->updateOrInsert(
            ['user_id' => $user->id, 'purpose' => $purpose],
            [
                'code' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(self::TTL_MINUTES),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return EmailService::send(
            $user->email,
            new OneTimePasscode($code, $user->name, $label, self::TTL_MINUTES)
        );
    }

    /**
     * Validate a code, consuming it on success.
     */
    public static function verify(User $user, string $purpose, string $code): bool
    {
        $record = DB::table('otp_codes')
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->first();

        if (! $record) {
            return false;
        }

        if (Carbon::parse($record->expires_at)->isPast()) {
            DB::table('otp_codes')->where('id', $record->id)->delete();

            return false;
        }

        if ((int) $record->attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        if (! Hash::check($code, $record->code)) {
            DB::table('otp_codes')->where('id', $record->id)->increment('attempts', 1, [
                'updated_at' => now(),
            ]);

            return false;
        }

        DB::table('otp_codes')->where('id', $record->id)->delete();

        return true;
    }
}
