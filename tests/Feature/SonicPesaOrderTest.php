<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Package;
use App\Models\Payment;
use App\Models\User;
use App\Services\SonicPesaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SonicPesaOrderTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Package::updateOrCreate(['code' => 'business'], [
            'name' => 'Business',
            'monthly_price' => 120000,
            'annual_price' => 1152000,
            'currency' => 'TZS',
            'employee_limit' => 500,
            'branch_limit' => 5,
            'active' => true,
        ]);

        $this->org = Organization::create([
            'name' => 'Acme Corporation',
            'contact_email' => 'admin@acmecorp.tz',
            'plan' => 'starter',
            'status' => 'active',
        ]);

        $this->admin = User::create([
            'name' => 'Jane Doe',
            'email' => 'jane@acmecorp.tz',
            'employee_id' => 'ACME-001',
            'phone' => '0754123456',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'org_id' => $this->org->id,
            'active' => true,
        ]);
    }

    public function test_phone_number_normalization_for_sonicpesa(): void
    {
        $this->assertEquals('255754123456', SonicPesaService::normalizePhone('0754123456'));
        $this->assertEquals('255754123456', SonicPesaService::normalizePhone('+255754123456'));
        $this->assertEquals('255754123456', SonicPesaService::normalizePhone('255754123456'));
        $this->assertEquals('255754123456', SonicPesaService::normalizePhone(' 0754-123-456 '));
    }

    public function test_payment_initiation_creates_sonicpesa_gateway_payment(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/admin/subscription/payments/initiate', [
            'plan' => 'business',
            'billing_cycle' => 'monthly',
            'mobile_provider' => 'mpesa',
            'phone_number' => '0754123456',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'payment' => [
                    'id',
                    'reference',
                    'gateway',
                    'plan',
                    'amount',
                    'currency',
                    'mobile_provider',
                    'phone_number',
                    'status',
                ],
                'instructions',
                'gateway',
            ])
            ->assertJsonPath('payment.gateway', 'sonicpesa')
            ->assertJsonPath('payment.phone_number', '0754123456')
            ->assertJsonPath('payment.status', 'pending');

        $this->assertDatabaseHas('payments', [
            'organization_id' => $this->org->id,
            'gateway' => 'sonicpesa',
            'plan' => 'business',
            'mobile_provider' => 'mpesa',
            'phone_number' => '0754123456',
            'status' => 'pending',
        ]);
    }

    public function test_sonicpesa_service_dispatches_create_order_request(): void
    {
        Http::fake([
            'https://api.sonicpesa.com/api/v1/payment/create_order' => Http::response([
                'status' => 'success',
                'order_id' => 'SP-TEST-9988',
                'checkout_url' => 'https://checkout.sonicpesa.com/pay/SP-TEST-9988',
                'qr_code' => 'data:image/png;base64,mockqr',
                'message' => 'Payment initiated successfully',
            ], 200),
        ]);

        $service = new SonicPesaService(
            apiKey: 'sp_live_key_mock123',
            apiSecret: 'sp_live_secret_mock456',
            baseUrl: 'https://api.sonicpesa.com',
            isSandbox: false
        );

        $result = $service->createOrder([
            'order_id' => 'SP-TEST-9988',
            'amount' => 120000,
            'buyer_phone' => '0754123456',
            'buyer_name' => 'Jane Doe',
            'buyer_email' => 'jane@acmecorp.tz',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('SP-TEST-9988', $result['order_id']);
        $this->assertEquals('https://checkout.sonicpesa.com/pay/SP-TEST-9988', $result['checkout_url']);
        $this->assertEquals('data:image/png;base64,mockqr', $result['qr_code']);
    }
}
