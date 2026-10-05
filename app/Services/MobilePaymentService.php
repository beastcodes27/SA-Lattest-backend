<?php

namespace App\Services;

use App\Models\Payment;
use InvalidArgumentException;

class MobilePaymentService
{
    public const PROVIDER_MPESA = 'mpesa';
    public const PROVIDER_TIGOPESA = 'tigopesa';
    public const PROVIDER_AIRTELMONEY = 'airtelmoney';
    public const PROVIDER_HALOPESA = 'halopesa';

    public const PROVIDER_NAMES = [
        self::PROVIDER_MPESA => 'M-Pesa (Vodacom)',
        self::PROVIDER_TIGOPESA => 'Tigo Pesa (Yas)',
        self::PROVIDER_AIRTELMONEY => 'Airtel Money',
        self::PROVIDER_HALOPESA => 'HaloPesa (Halotel)',
    ];

    public const PROVIDER_USSD = [
        self::PROVIDER_MPESA => '*150*00#',
        self::PROVIDER_TIGOPESA => '*150*01#',
        self::PROVIDER_AIRTELMONEY => '*150*60#',
        self::PROVIDER_HALOPESA => '*150*88#',
    ];

    public static function normalizePhoneNumber(string $phone): string
    {
        $cleaned = preg_replace('/[^\d+]/', '', trim($phone));
        if (str_starts_with($cleaned, '+255')) {
            $cleaned = '0' . substr($cleaned, 4);
        } elseif (str_starts_with($cleaned, '255')) {
            $cleaned = '0' . substr($cleaned, 3);
        }

        return $cleaned;
    }

    public static function detectProvider(string $phone): ?string
    {
        $normalized = self::normalizePhoneNumber($phone);
        if (strlen($normalized) < 3) {
            return null;
        }

        $prefix = substr($normalized, 0, 3);

        if (in_array($prefix, ['074', '075', '076'], true)) {
            return self::PROVIDER_MPESA;
        }

        if (in_array($prefix, ['065', '067', '071'], true)) {
            return self::PROVIDER_TIGOPESA;
        }

        if (in_array($prefix, ['068', '069', '078'], true)) {
            return self::PROVIDER_AIRTELMONEY;
        }

        if (in_array($prefix, ['061', '062'], true)) {
            return self::PROVIDER_HALOPESA;
        }

        return null;
    }

    public static function validateProviderPhone(string $provider, string $phone): bool
    {
        $detected = self::detectProvider($phone);
        if (! $detected) {
            return false;
        }

        return $detected === strtolower($provider);
    }

    public static function generateReference(): string
    {
        return 'SA-PAY-' . strtoupper(bin2hex(random_bytes(4))) . '-' . date('d');
    }

    public static function getInstructions(string $provider, int $amount, string $phone, string $reference): array
    {
        $providerKey = strtolower($provider);
        $providerName = self::PROVIDER_NAMES[$providerKey] ?? ucfirst($provider);
        $ussd = self::PROVIDER_USSD[$providerKey] ?? '*150*00#';
        $formattedAmount = 'TZS ' . number_format($amount);

        return [
            'provider' => $providerKey,
            'provider_name' => $providerName,
            'ussd_code' => $ussd,
            'amount' => $amount,
            'formatted_amount' => $formattedAmount,
            'phone_number' => $phone,
            'reference' => $reference,
            'steps' => [
                "1. A payment prompt of {$formattedAmount} has been sent to {$phone}.",
                "2. Please check your phone and enter your {$providerName} PIN to confirm.",
                "3. If prompt is delayed, dial {$ussd}, enter Business Number 555222, Reference {$reference}.",
                "4. Your SmartAttend subscription activates automatically upon approval.",
            ],
            'estimated_seconds' => 45,
        ];
    }
}
