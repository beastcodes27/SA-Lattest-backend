<?php

namespace App\Console\Commands;

use App\Models\Organization;
use Illuminate\Console\Command;

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
            $this->info("Approved: #{$org->id} {$org->name} — free trial until {$org->fresh()->trial_ends_at?->toDateString()}");
        }

        return self::SUCCESS;
    }
}
