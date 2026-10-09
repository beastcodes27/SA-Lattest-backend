<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Whether SMS sending is enabled and configured.
     */
    public static function isConfigured(): bool
    {
        return (bool) config('services.sms.enabled') && ! empty(config('services.sms.api_key'));
    }

    /**
     * Normalize a Tanzanian phone number to Textify's 255… international format.
     */
    public static function normalizePhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $cleaned = preg_replace('/[^\d+]/', '', trim($phone));

        if (str_starts_with($cleaned, '+')) {
            $cleaned = substr($cleaned, 1);
        }

        if (str_starts_with($cleaned, '0')) {
            return '255'.substr($cleaned, 1);
        }

        if (str_starts_with($cleaned, '255')) {
            return $cleaned;
        }

        return '255'.$cleaned;
    }

    /**
     * Append the configured support contact to a message, when set.
     */
    public static function withSupport(string $message): string
    {
        $contact = trim((string) config('services.sms.support_contact'));

        if ($contact === '') {
            return $message;
        }

        return rtrim($message, ' ')." For support, contact {$contact}.";
    }

    /**
     * Send a single SMS via Textify Africa. Returns true on success.
     */
    public static function send(?string $phone, string $message): bool
    {
        $to = self::normalizePhone($phone);
        $message = trim($message);

        if (! $to || $message === '') {
            return false;
        }

        if (! self::isConfigured()) {
            Log::info('SMS not configured; skipping send.', ['to' => $to, 'message' => $message]);

            return false;
        }

        try {
            $response = Http::timeout((int) config('services.sms.timeout', 15))
                ->withToken((string) config('services.sms.api_key'))
                ->acceptJson()
                ->post(config('services.sms.api_url').'/messages', [
                    'sender_name' => (string) config('services.sms.sender_name'),
                    'is_scheduled' => false,
                    'messages' => [
                        ['receiver' => $to, 'content' => $message],
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('SMS send failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'to' => $to,
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('SMS send exception: '.$e->getMessage(), ['to' => $to]);

            return false;
        }
    }

    /**
     * Send the same SMS to a collection of users that have phone numbers.
     */
    public static function sendMany($users, string $message): int
    {
        $sent = 0;

        foreach ($users as $user) {
            $phone = $user->phone ?? null;
            if ($phone && self::send($phone, $message)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Queue a single SMS to run after the HTTP response is sent.
     */
    public static function sendLater(?string $phone, string $message): void
    {
        $normalized = self::normalizePhone($phone);

        if (! $normalized) {
            return;
        }

        app()->terminating(fn () => self::send($normalized, $message));
    }

    /**
     * Queue the SMS to run after the HTTP response is sent, so slow gateways
     * never delay (or time out) the user's request.
     */
    public static function sendManyLater($users, string $message): void
    {
        $phones = collect($users)
            ->map(fn ($user) => $user->phone ?? null)
            ->filter()
            ->values()
            ->all();

        if (empty($phones)) {
            return;
        }

        app()->terminating(function () use ($phones, $message) {
            foreach ($phones as $phone) {
                self::send($phone, $message);
            }
        });
    }

    /**
     * Notify an organization's admins by SMS, falling back to the organization
     * contact phone when an admin has no number on file.
     */
    public static function notifyOrgAdmins($org, string $message, bool $defer = true): int
    {
        $phones = $org->users()
            ->where('role', 'admin')
            ->where('active', true)
            ->pluck('phone')
            ->filter()
            ->values()
            ->all();

        if (empty($phones) && ! empty($org->contact_phone)) {
            $phones = [$org->contact_phone];
        }

        if (empty($phones)) {
            return 0;
        }

        $worker = function () use ($phones, $message) {
            $sent = 0;
            foreach ($phones as $phone) {
                if (self::send($phone, $message)) {
                    $sent++;
                }
            }

            return $sent;
        };

        if ($defer) {
            app()->terminating(fn () => $worker());

            return count($phones);
        }

        return $worker();
    }
}
