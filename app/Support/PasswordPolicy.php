<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

class PasswordPolicy
{
    public static function minLength(): int
    {
        return max(8, (int) config('auth.password_policy.min_length', 12));
    }

    public static function rule(): Password
    {
        $rule = Password::min(self::minLength());

        if (config('auth.password_policy.check_breaches', true)) {
            $rule = $rule->uncompromised();
        }

        return $rule;
    }
}
