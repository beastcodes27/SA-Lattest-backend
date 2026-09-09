<?php

namespace App\Support;

class PlanLimits
{
    public const EMPLOYEES = [
        'starter' => 50,
        'business' => 500,
        'enterprise' => null,
    ];

    public const BRANCHES = [
        'starter' => 1,
        'business' => 5,
        'enterprise' => null,
    ];

    public static function employeeLimit(?string $plan): ?int
    {
        return self::EMPLOYEES[$plan ?? 'starter'] ?? null;
    }

    public static function branchLimit(?string $plan): ?int
    {
        return self::BRANCHES[$plan ?? 'starter'] ?? null;
    }
}
