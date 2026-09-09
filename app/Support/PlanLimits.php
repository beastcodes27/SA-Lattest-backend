<?php

namespace App\Support;

use App\Models\Package;
use Illuminate\Support\Collection;

class PlanLimits
{
    private const FALLBACK_EMPLOYEES = [
        'starter' => 50,
        'business' => 500,
        'enterprise' => null,
    ];

    private const FALLBACK_BRANCHES = [
        'starter' => 1,
        'business' => 5,
        'enterprise' => null,
    ];

    private static ?Collection $cache = null;

    private static function packages(): Collection
    {
        if (self::$cache === null) {
            self::$cache = Package::all()->keyBy('code');
        }

        return self::$cache;
    }

    public static function package(?string $code): ?Package
    {
        return $code ? self::packages()->get($code) : null;
    }

    public static function employeeLimit(?string $code): ?int
    {
        $package = self::package($code);

        return $package ? $package->employee_limit : (self::FALLBACK_EMPLOYEES[$code ?? 'starter'] ?? null);
    }

    public static function branchLimit(?string $code): ?int
    {
        $package = self::package($code);

        return $package ? $package->branch_limit : (self::FALLBACK_BRANCHES[$code ?? 'starter'] ?? null);
    }

    public static function activeCodes(): array
    {
        return Package::query()->where('active', true)->orderBy('position')->pluck('code')->all();
    }
}
