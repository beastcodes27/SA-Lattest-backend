<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\Promo;
use App\Services\SmsService;
use Illuminate\Console\Command;

class RemindOffers extends Command
{
    protected $signature = 'offers:remind {--days=7}';

    protected $description = 'SMS organization admins about offers/promos that are ending soon.';

    public function handle(): int
    {
        $now = now();
        $threshold = $now->copy()->addDays(max(1, (int) $this->option('days')));

        $promos = Promo::query()
            ->where('active', true)
            ->whereNotNull('ends_at')
            ->where('ends_at', '>', $now)
            ->where('ends_at', '<=', $threshold)
            ->orderBy('ends_at')
            ->get();

        if ($promos->isEmpty()) {
            $this->info('No offers ending soon.');

            return self::SUCCESS;
        }

        // Target organizations that are not on a paid plan (they can benefit from an offer).
        $orgs = Organization::query()
            ->where('status', 'active')
            ->where(function ($q) {
                $q->where('subscription_status', '!=', 'active')
                    ->orWhereNull('subscription_status');
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('offer_reminder_sent_at')
                    ->orWhere('offer_reminder_sent_at', '<', $now->copy()->subDays(7));
            })
            ->with(['users' => fn ($q) => $q->where('role', 'admin')->where('active', true)])
            ->get();

        $sent = 0;
        $offer = $promos->first();

        foreach ($orgs as $org) {
            $message = "SmartAttend offer: use code {$offer->code} - {$offer->label()}. Ends {$offer->ends_at->toDateString()}. Apply it before it expires.";

            $sent += SmsService::sendMany($org->users, $message);

            $org->forceFill(['offer_reminder_sent_at' => $now])->save();
        }

        $this->info("Offer reminders sent: {$sent} across {$orgs->count()} organization(s) for {$promos->count()} offer(s).");

        return self::SUCCESS;
    }
}
