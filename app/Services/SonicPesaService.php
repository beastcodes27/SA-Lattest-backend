<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SonicPesaService
{
    protected string $apiKey;
    protected ?string $apiSecret;
    protected string $baseUrl;
    protected ?string $webhookSecret;
    protected bool $isSandbox;
    protected int $timeout;

    public function __construct(
        ?string $apiKey = null,
        ?string $apiSecret = null,
        ?string $baseUrl = null,
        ?string $webhookSecret = null,
        ?bool $isSandbox = null,
        ?int $timeout = null
    ) {
        $this->apiKey = (string) ($apiKey ?? config('services.sonicpesa.api_key', ''));
        $this->apiSecret = $apiSecret ?? config('services.sonicpesa.api_secret');
        $this->baseUrl = rtrim((string) ($baseUrl ?? config('services.sonicpesa.base_url', 'https://api.sonicpesa.com')), '/');
        $this->webhookSecret = $webhookSecret ?? config('services.sonicpesa.webhook_secret') ?? $this->apiSecret;
        $this->isSandbox = $isSandbox ?? (bool) config('services.sonicpesa.sandbox', true);
        $this->timeout = $timeout ?? (int) config('services.sonicpesa.timeout', 30);
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiKey);
    }

    public function isSandbox(): bool
    {
        return $this->isSandbox;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    protected function client(): PendingRequest
    {
        $headers = [
            'X-API-KEY' => $this->apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if (! empty($this->apiSecret)) {
            $headers['X-API-SECRET'] = $this->apiSecret;
        }

        return Http::baseUrl($this->baseUrl)
            ->withHeaders($headers)
            ->timeout($this->timeout);
    }

    public static function normalizePhone(string $rawPhone): string
    {
        $cleaned = preg_replace('/[^\d+]/', '', trim($rawPhone));
        if (str_starts_with($cleaned, '+255')) {
            return substr($cleaned, 1);
        }
        if (str_starts_with($cleaned, '255')) {
            return $cleaned;
        }
        if (str_starts_with($cleaned, '0')) {
            return '255' . substr($cleaned, 1);
        }

        return '255' . $cleaned;
    }

    public static function formatPhoneForDisplay(string $phone): string
    {
        $normalized = self::normalizePhone($phone);
        if (str_starts_with($normalized, '255') && strlen($normalized) === 12) {
            return '0' . substr($normalized, 3);
        }

        return $normalized;
    }
}
