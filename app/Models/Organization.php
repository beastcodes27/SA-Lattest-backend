<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'contact_email',
    'contact_phone',
    'address',
    'website',
    'tin',
    'employee_id_prefix',
    'plan',
    'status',
    'trial_started_at',
    'trial_ends_at',
    'subscription_status',
    'canceled_at',
    'discount_percent',
])]
class Organization extends Model
{
    protected function casts(): array
    {
        return [
            'trial_started_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'canceled_at' => 'datetime',
            'discount_percent' => 'integer',
        ];
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class, 'org_id');
    }

    public function startTrial(int $days = 30): void
    {
        $this->forceFill([
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addDays($days),
        ])->save();
    }

    public function trialDaysLeft(): ?int
    {
        if (! $this->trial_ends_at) {
            return null;
        }

        $days = (int) ceil(now()->diffInMinutes($this->trial_ends_at, false) / 1440);

        return max(0, $days);
    }

    public function onTrial(): bool
    {
        return $this->trial_ends_at !== null && $this->trialDaysLeft() > 0;
    }

    public function isAccessible(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->subscription_status === 'canceled') {
            return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
        }

        if ($this->subscriptionActive()) {
            return true;
        }

        if ($this->trial_ends_at === null) {
            return true;
        }

        return $this->trial_ends_at->isFuture();
    }

    public function subscriptionActive(): bool
    {
        return $this->subscription_status === 'active';
    }


    public function activeBranches(): HasMany
    {
        return $this->hasMany(Branch::class, 'org_id')->where('active', true);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'org_id');
    }
}
