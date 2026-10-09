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
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', $now)
            ->where('trial_ends_at', '<=', $threshold)
            ->where(function ($q) use ($now) {
                $q->whereNull('renewal_reminder_sent_at')
                    ->orWhere('renewal_reminder_sent_at', '<', $now->copy()->subDays(3));
            })
            ->with(['users' => fn ($q) => $q->where('role', 'admin')->where('active', true)])
            ->get();

        $sent = 0;

        foreach ($orgs as $org) {
            $daysLeft = $org->trialDaysLeft() ?? 0;
            $message = "SmartAttend: Your package for {$org->name} ends in {$daysLeft} day(s) on {$org->trial_ends_at->toDateString()}. Renew now to keep employee check-ins running.";

            $sent += SmsService::sendMany($org->users, $message);

            $org->forceFill(['renewal_reminder_sent_at' => $now])->save();
        }

        $this->info("Renewal reminders sent: {$sent} across {$orgs->count()} organization(s).");

        return self::SUCCESS;
    }
}
