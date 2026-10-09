<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\ExpoPushService;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ApproveOrganization extends Command
{
    protected $signature = 'org:approve {organization : Organization ID or part of its name}';

    protected $description = 'Mark an organization as active so its users can sign in';

    public function handle(): int
    {
        $query = Organization::query();

        if (is_numeric($this->argument('organization'))) {
            $query->where('id', (int) $this->argument('organization'));
        } else {
            $query->where('name', 'like', '%'.$this->argument('organization').'%');
        }

        $orgs = $query->get();

        if ($orgs->isEmpty()) {
            $this->error('No organization found.');

            return self::FAILURE;
        }

        foreach ($orgs as $org) {
            $org->forceFill(['status' => 'active'])->save();
            if ($org->trial_started_at === null) {
                $org->startTrial(30);
            }

            try {
                $admins = $org->users()->where('role', 'admin')->where('active', true)->get();
                ExpoPushService::notifyUsers(
                    $admins,
                    'Organization Approved',
                    "{$org->name} has been approved. You can now sign in to SmartAttend and start using your free trial.",
                    'broadcast'
                );
                SmsService::notifyOrgAdmins(
                    $org,
                    SmsService::withSupport("SmartAttend: {$org->name} has been approved. You can sign in and start managing your organization and adding employees details. Thank you."),
                    false
                );
            } catch (\Throwable $e) {
                Log::warning('Failed to notify approved organization: '.$e->getMessage());
            }

            $this->info("Approved: #{$org->id} {$org->name} — free trial until {$org->fresh()->trial_ends_at?->toDateString()}");
        }

        return self::SUCCESS;
    }
}
