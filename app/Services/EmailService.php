<?php

namespace App\Services;

use App\Mail\PaymentReceipt;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    /**
     * Whether outbound email is enabled for this installation.
     */
    public static function isConfigured(): bool
    {
        return (bool) config('services.email.enabled', true);
    }

    /**
     * Send a mailable to a single address. Failures are logged instead of
     * interrupting the request that triggered them.
     */
    public static function send(?string $email, Mailable $mailable): bool
    {
        $email = strtolower(trim((string) $email));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (! self::isConfigured()) {
            Log::info('Email not enabled; skipping send.', ['to' => $email, 'mailable' => $mailable::class]);

            return false;
        }

        try {
            Mail::to($email)->send($mailable);

            return true;
        } catch (\Throwable $e) {
            Log::error('Email send failed: '.$e->getMessage(), [
                'to' => $email,
                'mailable' => $mailable::class,
            ]);

            return false;
        }
    }

    /**
     * Email every active system administrator, including the configured
     * monitoring address when one is set.
     */
    public static function sendToSystemAdmins(Mailable $mailable): int
    {
        $addresses = User::query()
            ->whereIn('role', ['superadmin', 'minor_admin', 'sysadmin'])
            ->where('active', true)
            ->pluck('email')
            ->push(config('services.email.admin_address'))
            ->filter()
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->unique()
            ->values();

        $sent = 0;

        foreach ($addresses as $address) {
            if (self::send($address, clone $mailable)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Email an organization's active admins, falling back to the contact
     * email on the organization record.
     */
    public static function sendToOrganizationAdmins(Organization $organization, Mailable $mailable): int
    {
        $addresses = $organization->users()
            ->where('role', 'admin')
            ->where('active', true)
            ->pluck('email')
            ->push($organization->contact_email)
            ->filter()
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->unique()
            ->values();

        $sent = 0;

        foreach ($addresses as $address) {
            if (self::send($address, clone $mailable)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Email an arbitrary collection of users that have an email address.
     */
    public static function sendToUsers($users, Mailable $mailable): int
    {
        $addresses = collect($users)
            ->pluck('email')
            ->filter()
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->unique()
            ->values();

        $sent = 0;

        foreach ($addresses as $address) {
            if (self::send($address, clone $mailable)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Send one message to many recipients in BCC chunks. Used for
     * announcements and promotions where personalisation is not required.
     */
    public static function sendBulk(iterable $emails, Mailable $mailable, int $chunkSize = 50): int
    {
        if (! self::isConfigured()) {
            return 0;
        }

        $addresses = collect($emails)
            ->filter(fn ($email) => filter_var((string) $email, FILTER_VALIDATE_EMAIL))
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->unique()
            ->values();

        if ($addresses->isEmpty()) {
            return 0;
        }

        $from = (string) config('mail.from.address');

        if (! filter_var($from, FILTER_VALIDATE_EMAIL)) {
            return self::sendToUsers($addresses->map(fn ($email) => (object) ['email' => $email]), $mailable);
        }

        $sent = 0;

        foreach ($addresses->chunk($chunkSize) as $chunk) {
            try {
                Mail::to($from)->bcc($chunk->all())->send(clone $mailable);
                $sent += $chunk->count();
            } catch (\Throwable $e) {
                Log::error('Bulk email send failed: '.$e->getMessage(), [
                    'recipients' => $chunk->count(),
                    'mailable' => $mailable::class,
                ]);
            }
        }

        return $sent;
    }

    /**
     * Email a payment receipt exactly once per completed payment.
     */
    public static function sendPaymentReceipt(Payment $payment): bool
    {
        if (! empty($payment->metadata['receipt_email_sent_at'])) {
            return false;
        }

        $payment->loadMissing(['organization', 'user']);

        $addresses = collect([$payment->user?->email, $payment->organization?->contact_email])
            ->merge(
                $payment->organization
                    ? $payment->organization->users()
                        ->where('role', 'admin')
                        ->where('active', true)
                        ->pluck('email')
                    : []
            )
            ->filter()
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->unique()
            ->values();

        $mailable = new PaymentReceipt($payment);
        $sent = false;

        foreach ($addresses as $address) {
            if (self::send($address, clone $mailable)) {
                $sent = true;
            }
        }

        if ($sent) {
            $metadata = $payment->metadata ?? [];
            $metadata['receipt_email_sent_at'] = now()->toIso8601String();

            $payment->forceFill(['metadata' => $metadata])->saveQuietly();
        }

        return $sent;
    }
}
