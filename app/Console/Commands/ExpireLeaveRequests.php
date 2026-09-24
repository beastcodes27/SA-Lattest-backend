<?php

namespace App\Console\Commands;

use App\Models\PermissionRequest;
use App\Models\User;
use App\Services\ExpoPushService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireLeaveRequests extends Command
{
    protected $signature = 'leave:expire-pending';

    protected $description = 'Expire pending leave/permission requests that have passed their expiry deadline, and notify the employee.';

    public function handle(): int
    {
        $now = now();

        // ── 1. Auto-expire overdue pending requests ──────────────────────────
        $overdue = PermissionRequest::where('status', PermissionRequest::STATUS_PENDING)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now)
            ->with(['user', 'organization'])
            ->get();

        $expiredCount = 0;

        foreach ($overdue as $permission) {
            $permission->status = PermissionRequest::STATUS_EXPIRED;
            $permission->save();
            $expiredCount++;

            // Notify the employee that their request expired without a response
            try {
                if ($permission->user) {
                    ExpoPushService::notifyUser(
                        $permission->user,
                        'Leave Request Expired',
                        "Your {$permission->category_label} request ({$permission->start_date->format('M d')} – {$permission->end_date->format('M d')}) expired without a response. You may resubmit if still needed.",
                        'leave_expired',
                        [
                            'permission_id' => $permission->id,
                            'status'        => 'expired',
                        ],
                        null
                    );
                }
            } catch (\Throwable $e) {
                Log::warning("leave:expire-pending — failed to notify employee #{$permission->user_id}: {$e->getMessage()}");
            }
        }

        // ── 2. Send reminder to org admins for requests expiring in < 24 h ──
        $soonExpiring = PermissionRequest::where('status', PermissionRequest::STATUS_PENDING)
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', $now)
            ->where('expires_at', '<=', $now->copy()->addHours(PermissionRequest::REMINDER_HOURS_BEFORE))
            ->whereNull('reminder_sent_at')
            ->with(['user', 'organization'])
            ->get();

        $reminderCount = 0;

        foreach ($soonExpiring as $permission) {
            try {
                $orgAdmins = User::where('org_id', $permission->organization_id)
                    ->where('role', 'org_admin')
                    ->where('active', true)
                    ->get();

                if ($orgAdmins->isNotEmpty() && $permission->user) {
                    ExpoPushService::notifyUsers(
                        $orgAdmins,
                        '⏰ Pending Request Expiring Soon',
                        "{$permission->user->name}'s {$permission->category_label} request will expire in less than 24 hours with no action taken.",
                        'leave_reminder',
                        [
                            'permission_id' => $permission->id,
                            'employee_id'   => $permission->user_id,
                            'employee_name' => $permission->user->name,
                        ],
                        $permission->user
                    );
                }

                $permission->reminder_sent_at = $now;
                $permission->save();
                $reminderCount++;
            } catch (\Throwable $e) {
                Log::warning("leave:expire-pending — failed to send reminder for permission #{$permission->id}: {$e->getMessage()}");
            }
        }

        $this->info("Done. Expired: {$expiredCount}, Reminders sent: {$reminderCount}.");

        return self::SUCCESS;
    }
}
