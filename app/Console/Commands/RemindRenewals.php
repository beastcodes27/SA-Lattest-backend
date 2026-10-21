<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\SmsService;
use Illuminate\Console\Command;

class RemindRenewals extends Command
{
    protected $signature = 'subscriptions:remind-renewals {--days=7}';

    protected $description = 'SMS organization admins when their package is about to end.';

    public function handle(): int
    {
        $now = now();
        $threshold = $now->copy()->addDays(max(1, (int) $this->option('days')));

        $orgs = Organization::query()
            ->where('status', 'active')
            ->where(function ($q) use ($now, $threshold) {
                $q->where(function ($trial) use ($now, $threshold) {
                    $trial->whereNotNull('trial_ends_at')
                        ->where('trial_ends_at', '>', $now)
                        ->where('trial_ends_at', '<=', $threshold);
                })->orWhere(function ($sub) use ($now, $threshold) {
                    $sub->whereNotNull('subscription_ends_at')
                        ->where('subscription_ends_at', '>', $now)
                        ->where('subscription_ends_at', '<=', $threshold);
                });
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('renewal_reminder_sent_at')
                    ->orWhere('renewal_reminder_sent_at', '<', $now->copy()->subDays(3));
            })
            ->with(['users' => fn ($q) => $q->where('role', 'admin')->where('active', true)])
            ->get();

        $sent = 0;

        foreach ($orgs as $org) {
            $end = collect([$org->trial_ends_at, $org->subscription_ends_at])
                ->filter(fn ($date) => $date && $date->isFuture())
                ->sort()
                ->first();

            if (! $end) {
                continue;
            }

            $daysLeft = max(0, (int) ceil($now->diffInMinutes($end, false) / 1440));
            $message = SmsService::withSupport("Your package for {$org->name} ends in {$daysLeft} day(s) on {$end->toDateString()}. Renew now to keep employee check-ins running.");

            $sent += SmsService::notifyOrgAdmins($org, $message, false);

            $org->forceFill(['renewal_reminder_sent_at' => $now])->save();
        }

        $this->info("Renewal reminders sent: {$sent} across {$orgs->count()} organization(s).");

        return self::SUCCESS;
    }
}
