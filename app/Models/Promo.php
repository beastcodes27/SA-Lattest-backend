<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'title',
    'description',
    'type',
    'value',
    'starts_at',
    'ends_at',
    'max_uses',
    'uses_count',
    'active',
])]
class Promo extends Model
{
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'active' => 'boolean',
        ];
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(PromoRedemption::class);
    }

    public function isRedeemable(): bool
    {
        if (! $this->active) {
            return false;
        }
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }
        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }
        if ($this->max_uses !== null && $this->uses_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function label(): string
    {
        return $this->type === 'trial_days'
            ? "Adds {$this->value} free trial day".($this->value === 1 ? '' : 's')
            : "{$this->value}% off on your package";
    }
}
