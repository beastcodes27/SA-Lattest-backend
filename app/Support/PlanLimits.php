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

    private const FALLBACK_MONTHLY_PRICES = [
        'starter' => 45000,
        'business' => 120000,
        'enterprise' => 350000,
    ];

    private const FALLBACK_ANNUAL_PRICES = [
        'starter' => 432000, // 20% discount on 12 months (540,000 -> 432,000)
        'business' => 1152000, // 20% discount on 12 months (1,440,000 -> 1,152,000)
        'enterprise' => 3360000, // 20% discount on 12 months (4,200,000 -> 3,360,000)
    ];

    private static ?Collection $cache = null;

    private static function packages(): Collection
    {
        if (self::$cache === null) {
            self::$cache = Package::all()->keyBy('code');
        }

        return self::$cache;
    }

    public static function clearCache(): void
    {
        self::$cache = null;
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

    public static function monthlyPrice(?string $code): int
    {
        $package = self::package($code);

        return ($package && $package->monthly_price > 0)
            ? $package->monthly_price
            : (self::FALLBACK_MONTHLY_PRICES[$code ?? 'starter'] ?? 45000);
    }

    public static function annualPrice(?string $code): int
    {
        $package = self::package($code);

        return ($package && $package->annual_price > 0)
            ? $package->annual_price
            : (self::FALLBACK_ANNUAL_PRICES[$code ?? 'starter'] ?? 432000);
    }

    public static function calculateAmount(string $code, string $billingCycle = 'monthly', int $discountPercent = 0): array
    {
        $package = self::package($code);
        if ($package) {
            return $package->calculateAmount($billingCycle, $discountPercent);
        }

        $isAnnual = strtolower($billingCycle) === 'annual';
        $base = $isAnnual ? self::annualPrice($code) : self::monthlyPrice($code);
        $discountPercent = max(0, min(100, $discountPercent));
        $discountAmount = (int) round(($base * $discountPercent) / 100);
        $finalAmount = max(0, $base - $discountAmount);

        return [
            'original_amount' => $base,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount,
            'currency' => 'TZS',
            'billing_cycle' => $isAnnual ? 'annual' : 'monthly',
        ];
    }

    public static function activeCodes(): array
    {
        return Package::query()->where('active', true)->orderBy('position')->pluck('code')->all();
    }
}
