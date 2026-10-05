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
        $amount = $this->monthly_price ?: 0;
        return 'TZS '.number_format($amount);
    }

    public function getFormattedAnnualPriceAttribute(): string
    {
        $amount = $this->annual_price ?: 0;
        return 'TZS '.number_format($amount);
    }

    public function calculateAmount(string $billingCycle = 'monthly', int $discountPercent = 0): array
    {
        $isAnnual = strtolower($billingCycle) === 'annual';
        $base = $isAnnual
            ? ($this->annual_price ?: ($this->monthly_price ? $this->monthly_price * 12 : 0))
            : ($this->monthly_price ?: 0);

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
