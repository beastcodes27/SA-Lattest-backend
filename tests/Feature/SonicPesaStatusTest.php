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

class SonicPesaStatusTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Package::updateOrCreate(['code' => 'starter'], [
            'name' => 'Starter',
            'monthly_price' => 45000,
            'annual_price' => 432000,
            'currency' => 'TZS',
            'employee_limit' => 50,
            'branch_limit' => 1,
            'active' => true,
        ]);

        $this->org = Organization::create([
            'name' => 'Kilimanjaro Holdings Ltd',
            'contact_email' => 'admin@kiliholdings.co.tz',
            'plan' => 'starter',
            'status' => 'active',
        ]);

        $this->admin = User::create([
            'name' => 'Kili Admin',
            'email' => 'admin@kiliholdings.co.tz',
            'employee_id' => 'KILI-001',
            'phone' => '0784112233',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'org_id' => $this->org->id,
            'active' => true,
        ]);
    }

    public function test_get_order_status_completed(): void
    {
        Http::fake([
            'https://api.sonicpesa.com/api/v1/payment/order_status/ORD-999' => Http::response([
                'status' => 'completed',
                'transaction_id' => 'SP-TXN-101010',
                'amount' => 45000,
                'currency' => 'TZS',
            ], 200),
        ]);

        $service = new SonicPesaService(
            apiKey: 'sp_test_key_abc',
            baseUrl: 'https://api.sonicpesa.com',
            isSandbox: false
        );

        $result = $service->getOrderStatus('ORD-999');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['is_completed']);
        $this->assertFalse($result['is_pending']);
        $this->assertFalse($result['is_failed']);
        $this->assertEquals(Payment::STATUS_COMPLETED, $result['status']);
        $this->assertEquals('SP-TXN-101010', $result['external_transaction_id']);
    }

    public function test_get_order_status_failed(): void
    {
        Http::fake([
            'https://api.sonicpesa.com/api/v1/payment/order_status/ORD-888' => Http::response([
                'status' => 'failed',
                'message' => 'Buyer rejected push notification',
            ], 200),
        ]);

        $service = new SonicPesaService(
            apiKey: 'sp_test_key_abc',
            baseUrl: 'https://api.sonicpesa.com',
            isSandbox: false
        );

        $result = $service->getOrderStatus('ORD-888');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['is_failed']);
        $this->assertFalse($result['is_completed']);
        $this->assertEquals(Payment::STATUS_FAILED, $result['status']);
    }

    public function test_diagnostics_masks_sensitive_api_key(): void
    {
        $service = new SonicPesaService(
            apiKey: 'sp_live_secret_1234567890abcdef',
            apiSecret: 'super_secret',
            baseUrl: 'https://api.sonicpesa.com',
            isSandbox: false
        );

        $diag = $service->getDiagnostics();

        $this->assertTrue($diag['configured']);
        $this->assertFalse($diag['sandbox']);
        $this->assertEquals('sonicpesa', $diag['gateway']);
        $this->assertEquals('sp_l****cdef', $diag['api_key_masked']);
        $this->assertTrue($diag['has_api_secret']);
    }

    public function test_verify_endpoint_checks_sonicpesa_and_activates_subscription(): void
    {
        Sanctum::actingAs($this->admin);

        $payment = Payment::create([
            'organization_id' => $this->org->id,
            'user_id' => $this->admin->id,
            'plan' => 'starter',
            'billing_cycle' => 'monthly',
            'amount' => 45000,
            'original_amount' => 45000,
            'discount_amount' => 0,
            'currency' => 'TZS',
            'gateway' => 'sonicpesa',
            'mobile_provider' => 'tigopesa',
            'phone_number' => '0784112233',
            'reference' => 'SA-REF-777',
            'sonicpesa_order_id' => 'SP-ORD-777',
            'status' => Payment::STATUS_PENDING,
        ]);

        $response = $this->postJson("/api/admin/subscription/payments/{$payment->id}/verify");

        $response->assertOk()
            ->assertJsonPath('payment.status', Payment::STATUS_COMPLETED)
            ->assertJsonPath('subscription.status', 'active');

        $payment->refresh();
        $this->assertEquals(Payment::STATUS_COMPLETED, $payment->status);
    }
}
