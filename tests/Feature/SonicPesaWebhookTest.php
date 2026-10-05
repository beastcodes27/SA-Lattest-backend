<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Package;
use App\Models\Payment;
use App\Models\User;
use App\Services\SonicPesaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SonicPesaWebhookTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
    private User $admin;
    private Payment $payment;
    private string $webhookSecret = 'test_webhook_secret_key_12345';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.sonicpesa.webhook_secret' => $this->webhookSecret]);
        config(['services.sonicpesa.api_secret' => $this->webhookSecret]);

        Package::updateOrCreate(['code' => 'enterprise'], [
            'name' => 'Enterprise',
            'monthly_price' => 300000,
            'annual_price' => 2880000,
            'currency' => 'TZS',
            'employee_limit' => 2000,
            'branch_limit' => 20,
            'active' => true,
        ]);

        $this->org = Organization::create([
            'name' => 'Safari Logistics Ltd',
            'contact_email' => 'billing@safarilogistics.co.tz',
            'plan' => 'starter',
            'status' => 'active',
        ]);

        $this->admin = User::create([
            'name' => 'Director John',
            'email' => 'john@safarilogistics.co.tz',
            'employee_id' => 'SAF-001',
            'phone' => '0754999888',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'org_id' => $this->org->id,
            'active' => true,
        ]);

        $this->payment = Payment::create([
            'organization_id' => $this->org->id,
            'user_id' => $this->admin->id,
            'plan' => 'enterprise',
            'billing_cycle' => 'monthly',
            'amount' => 300000,
            'original_amount' => 300000,
            'discount_amount' => 0,
            'currency' => 'TZS',
            'gateway' => 'sonicpesa',
            'mobile_provider' => 'mpesa',
            'phone_number' => '0754999888',
            'reference' => 'SA-SONIC-TEST-001',
            'sonicpesa_order_id' => 'SP-ORD-554433',
            'status' => Payment::STATUS_PENDING,
        ]);
    }

    private function generateSignature(array $payload): string
    {
        return hash_hmac('sha256', json_encode($payload), $this->webhookSecret);
    }

    public function test_valid_webhook_marks_payment_completed_and_activates_subscription(): void
    {
        $payload = [
            'order_id' => 'SP-ORD-554433',
            'transaction_id' => 'TXN-VOD-987654321',
            'status' => 'completed',
            'amount' => 300000,
            'currency' => 'TZS',
            'buyer_phone' => '255754999888',
        ];

        $rawBody = json_encode($payload);
        $signature = hash_hmac('sha256', $rawBody, $this->webhookSecret);

        $response = $this->call(
            'POST',
            '/api/webhooks/sonicpesa',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_SONICPESA_SIGNATURE' => $signature,
            ],
            $rawBody
        );

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('order_id', 'SP-ORD-554433');

        $this->payment->refresh();
        $this->assertEquals(Payment::STATUS_COMPLETED, $this->payment->status);
        $this->assertEquals('TXN-VOD-987654321', $this->payment->external_transaction_id);
        $this->assertNotNull($this->payment->paid_at);

        $this->org->refresh();
        $this->assertEquals('enterprise', $this->org->plan);
        $this->assertEquals('active', $this->org->subscription_status);
    }

    public function test_webhook_idempotency_on_duplicate_notifications(): void
    {
        // Mark payment completed first
        $this->payment->markAsCompleted('TXN-PREV-111');

        $payload = [
            'order_id' => 'SP-ORD-554433',
            'transaction_id' => 'TXN-VOD-987654321',
            'status' => 'completed',
            'amount' => 300000,
        ];

        $rawBody = json_encode($payload);
        $signature = hash_hmac('sha256', $rawBody, $this->webhookSecret);

        $response = $this->call(
            'POST',
            '/api/webhooks/sonicpesa',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_SONICPESA_SIGNATURE' => $signature,
            ],
            $rawBody
        );

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Payment already completed previously');
    }

    public function test_webhook_rejects_invalid_hmac_signature(): void
    {
        $payload = [
            'order_id' => 'SP-ORD-554433',
            'status' => 'completed',
        ];

        $rawBody = json_encode($payload);

        $response = $this->call(
            'POST',
            '/api/webhooks/sonicpesa',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_SONICPESA_SIGNATURE' => 'invalid_forged_signature_hash',
            ],
            $rawBody
        );

        $response->assertStatus(401)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Invalid webhook signature');
    }

    public function test_webhook_handles_payment_failure_status(): void
    {
        $payload = [
            'order_id' => 'SP-ORD-554433',
            'status' => 'failed',
            'message' => 'Insufficient funds in M-Pesa account',
        ];

        $rawBody = json_encode($payload);
        $signature = hash_hmac('sha256', $rawBody, $this->webhookSecret);

        $response = $this->call(
            'POST',
            '/api/webhooks/sonicpesa',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_SONICPESA_SIGNATURE' => $signature,
            ],
            $rawBody
        );

        $response->assertOk()
            ->assertJsonPath('status', 'failed');

        $this->payment->refresh();
        $this->assertEquals(Payment::STATUS_FAILED, $this->payment->status);
        $this->assertStringContainsString('Insufficient funds', $this->payment->failure_reason);
    }
}
