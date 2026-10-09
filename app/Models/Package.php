<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'code',
    'name',
    'tagline',
    'price_label',
    'monthly_price',
    'annual_price',
    'currency',
    'features',
    'employee_limit',
    'branch_limit',
    'active',
    'position',
])]
class Package extends Model
{
    protected function casts(): array
    {
        return [
            'monthly_price' => 'integer',
            'annual_price' => 'integer',
            'features' => 'array',
            'active' => 'boolean',
        ];
    }

    public function isUnlimitedEmployee(): bool
    {
        return $this->employee_limit === null;
    }

    public function isUnlimitedBranch(): bool
    {
        return $this->branch_limit === null;
    }

    public function getFormattedMonthlyPriceAttribute(): string
    {
        return 'TZS '.number_format($this->resolvedMonthlyPrice());
    }

    public function getFormattedAnnualPriceAttribute(): string
    {
        return 'TZS '.number_format($this->resolvedAnnualPrice());
    }

    /**
     * Monthly price, falling back to a number parsed from the price label
     * (so admins who only set a label still get correct pricing).
     */
    public function resolvedMonthlyPrice(): int
    {
        if ((int) $this->monthly_price > 0) {
            return (int) $this->monthly_price;
        }

        $digits = preg_replace('/[^0-9]/', '', (string) ($this->price_label ?? ''));

        return $digits !== '' ? (int) $digits : 0;
    }

    /**
     * Annual price, falling back to the monthly price with the standard
     * 20% annual discount when no explicit annual value is configured.
     */
    public function resolvedAnnualPrice(): int
    {
        if ((int) $this->annual_price > 0) {
            return (int) $this->annual_price;
        }

        $monthly = $this->resolvedMonthlyPrice();

        return $monthly > 0 ? (int) round($monthly * 12 * 0.80) : 0;
    }

    public function calculateAmount(string $billingCycle = 'monthly', int $discountPercent = 0): array
    {
        $isAnnual = strtolower($billingCycle) === 'annual';
        $base = $isAnnual ? $this->resolvedAnnualPrice() : $this->resolvedMonthlyPrice();

        $discountPercent = max(0, min(100, $discountPercent));
        $discountAmount = (int) round(($base * $discountPercent) / 100);
        $finalAmount = max(0, $base - $discountAmount);

        return [
            'original_amount' => $base,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount,
            'currency' => $this->currency ?: 'TZS',
            'billing_cycle' => $isAnnual ? 'annual' : 'monthly',
        ];
    }
}
