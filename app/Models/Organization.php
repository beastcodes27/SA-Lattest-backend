<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable([
    'name',
    'contact_email',
    'contact_phone',
    'address',
    'website',
    'tin',
    'tin_document_path',
    'tin_document_name',
    'photos',
    'employee_id_prefix',
    'default_employee_password',
    'plan',
    'status',
    'trial_started_at',
    'trial_ends_at',
    'subscription_ends_at',
    'subscription_status',
    'canceled_at',
    'discount_percent',
    'paused_plan',
    'paused_remaining_seconds',
    'paused_was_trial',
    'renewal_reminder_sent_at',
    'offer_reminder_sent_at',
])]
class Organization extends Model
{
    protected function casts(): array
    {
        return [
            'trial_started_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'canceled_at' => 'datetime',
            'discount_percent' => 'integer',
            'paused_remaining_seconds' => 'integer',
            'paused_was_trial' => 'boolean',
            'photos' => 'array',
            'renewal_reminder_sent_at' => 'datetime',
            'offer_reminder_sent_at' => 'datetime',
        ];
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class, 'org_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'organization_id');
    }

    public function startTrial(int $days = 30): void
    {
        $this->forceFill([
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addDays($days),
        ])->save();
    }

    public function cancelTrial(): void
    {
        $this->capturePausedPeriod();

        $this->forceFill([
            'trial_ends_at' => now(),
            'subscription_status' => 'canceled',
            'canceled_at' => now(),
        ])->save();
    }

    public function activateSubscription(string $plan, string $billingCycle = 'monthly', ?Payment $payment = null): void
    {
        $this->forceFill([
            'plan' => $plan,
            'subscription_status' => 'active',
            'trial_ends_at' => null,
            'canceled_at' => null,
            'subscription_ends_at' => now()->addMonths($billingCycle === 'annual' ? 12 : 1),
            'paused_plan' => null,
            'paused_remaining_seconds' => 0,
            'paused_was_trial' => false,
        ])->save();
    }

    /**
     * Remember the package and the time left on the current period so that
     * reactivation can resume exactly where the subscription was paused.
     */
    public function capturePausedPeriod(): void
    {
        $wasTrial = $this->onTrial();
        $end = $wasTrial ? $this->trial_ends_at : $this->subscription_ends_at;
        $remaining = $end ? max(0, $end->getTimestamp() - now()->getTimestamp()) : 0;

        $this->forceFill([
            'paused_plan' => $this->plan,
            'paused_remaining_seconds' => $remaining,
            'paused_was_trial' => $wasTrial,
        ])->save();
    }

    /**
     * Restore the cancelled package and continue the remaining time that was
     * paused, instead of starting a fresh period.
     */
    public function resumeSubscription(): void
    {
        $plan = $this->paused_plan ?: $this->plan;
        $seconds = (int) $this->paused_remaining_seconds;
        $wasTrial = (bool) $this->paused_was_trial;

        $this->forceFill([
            'plan' => $plan,
            'subscription_status' => $wasTrial ? null : 'active',
            'canceled_at' => null,
            'trial_ends_at' => $wasTrial ? ($seconds > 0 ? now()->addSeconds($seconds) : now()) : null,
            'subscription_ends_at' => $wasTrial ? null : ($seconds > 0 ? now()->addSeconds($seconds) : now()->addMonth()),
            'paused_plan' => null,
            'paused_remaining_seconds' => 0,
            'paused_was_trial' => false,
        ])->save();
    }

    public function hasPausedPeriod(): bool
    {
        return $this->paused_plan !== null || (int) $this->paused_remaining_seconds > 0;
    }

    public function pausedDaysLeft(): ?int
    {
        if (! $this->hasPausedPeriod()) {
            return null;
        }

        return (int) ceil(((int) $this->paused_remaining_seconds) / 86400);
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

    /**
     * Whether this organization has already used its one-time free trial
     * (i.e. the trial was started at some point and is no longer active).
     */
    public function hasConsumedTrial(): bool
    {
        return $this->trial_started_at !== null && ! $this->onTrial();
    }

    public function isAccessible(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->subscription_status === 'canceled' || $this->subscription_status === 'expired') {
            return false;
        }

        if ($this->subscriptionActive()) {
            return true;
        }

        if ($this->trial_ends_at === null) {
            return false;
        }

        return $this->trial_ends_at->isFuture();
    }

    public function subscriptionActive(): bool
    {
        return $this->subscription_status === 'active'
            && ($this->subscription_ends_at === null || $this->subscription_ends_at->isFuture());
    }

    public function activeBranches(): HasMany
    {
        return $this->hasMany(Branch::class, 'org_id')->where('active', true);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'org_id');
    }

    public function attendances(): HasManyThrough
    {
        return $this->hasManyThrough(Attendance::class, User::class, 'org_id', 'user_id');
    }

    public function getTinDocumentUrlAttribute(): ?string
    {
        if (! $this->tin_document_path) {
            return null;
        }

        return url('storage/'.$this->tin_document_path);
    }

    public function permissionRequests(): HasMany
    {
        return $this->hasMany(PermissionRequest::class, 'organization_id');
    }
}
