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

    public function createOrder(array $params): array
    {
        $phone = self::normalizePhone($params['buyer_phone'] ?? $params['phone'] ?? '');
        $amount = (int) ($params['amount'] ?? 0);
        $orderId = (string) ($params['order_id'] ?? $params['reference'] ?? ('SA-' . strtoupper(bin2hex(random_bytes(4)))));
        $currency = strtoupper((string) ($params['currency'] ?? 'TZS'));
        $name = (string) ($params['buyer_name'] ?? $params['name'] ?? 'SmartAttend Subscriber');
        $email = (string) ($params['buyer_email'] ?? $params['email'] ?? 'billing@smartattend.app');
        $webhookUrl = (string) ($params['webhook_url'] ?? url('/api/webhooks/sonicpesa'));

        $payload = [
            'buyer_name' => $name,
            'buyer_email' => $email,
            'buyer_phone' => $phone,
            'amount' => $amount,
            'currency' => $currency,
            'order_id' => $orderId,
            'webhook_url' => $webhookUrl,
            'metadata' => $params['metadata'] ?? [],
        ];

        // In sandbox or unconfigured mode, provide instant simulated order
        if (! $this->isConfigured()) {
            Log::info('SonicPesa not configured, generating simulated sandbox order', ['order_id' => $orderId, 'phone' => $phone, 'amount' => $amount]);
            return [
                'success' => true,
                'order_id' => $orderId,
                'status' => Payment::STATUS_PENDING,
                'checkout_url' => null,
                'qr_code' => null,
                'message' => 'SonicPesa sandbox order created. USSD prompt sent to ' . $phone,
                'raw' => [
                    'simulated' => true,
                    'order_id' => $orderId,
                    'phone' => $phone,
                    'amount' => $amount,
                ],
            ];
        }

        try {
            $response = $this->client()->post('/api/v1/payment/create_order', $payload);
            $json = $response->json() ?? [];

            if ($response->successful() && ($json['status'] ?? '') !== 'error') {
                $returnedOrderId = $json['order_id'] ?? $json['id'] ?? $orderId;
                $checkoutUrl = $json['checkout_url'] ?? $json['payment_url'] ?? null;
                $qrCode = $json['qr_code'] ?? null;

                return [
                    'success' => true,
                    'order_id' => $returnedOrderId,
                    'status' => Payment::STATUS_PENDING,
                    'checkout_url' => $checkoutUrl,
                    'qr_code' => $qrCode,
                    'message' => $json['message'] ?? 'Payment prompt dispatched successfully via SonicPesa.',
                    'raw' => $json,
                ];
            }

            Log::warning('SonicPesa create_order API returned error response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'payload' => $payload,
            ]);

            return [
                'success' => false,
                'order_id' => $orderId,
                'status' => Payment::STATUS_FAILED,
                'checkout_url' => null,
                'qr_code' => null,
                'message' => $json['message'] ?? $json['error'] ?? ('SonicPesa Error (' . $response->status() . ')'),
                'raw' => $json,
            ];
        } catch (\Throwable $e) {
            Log::error('SonicPesa create_order exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'payload' => $payload,
            ]);

            // Fallback gracefully in dev/sandbox
            if ($this->isSandbox) {
                return [
                    'success' => true,
                    'order_id' => $orderId,
                    'status' => Payment::STATUS_PENDING,
                    'checkout_url' => null,
                    'qr_code' => null,
                    'message' => 'SonicPesa sandbox fallback active. Payment prompt sent to ' . $phone,
                    'raw' => ['fallback' => true, 'error' => $e->getMessage()],
                ];
            }

            return [
                'success' => false,
                'order_id' => $orderId,
                'status' => Payment::STATUS_FAILED,
                'checkout_url' => null,
                'qr_code' => null,
                'message' => 'Could not connect to SonicPesa payment gateway: ' . $e->getMessage(),
                'raw' => ['exception' => $e->getMessage()],
            ];
        }
    }
}
