<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Package;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobilePaymentSubscriptionTest extends TestCase
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
            'name' => 'Tech Solutions Ltd',
            'contact_email' => 'admin@techsolutions.co.tz',
            'plan' => 'starter',
            'status' => 'active',
        ]);

        $this->org->startTrial(30);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@techsolutions.co.tz',
            'employee_id' => 'TECH-001',
            'phone' => '+255 754 000 111',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'org_id' => $this->org->id,
            'active' => true,
        ]);
    }

    public function test_admin_can_cancel_free_trial_and_unlock_purchasing(): void
    {
        Sanctum::actingAs($this->admin);

        $this->assertTrue($this->org->onTrial());

        $response = $this->postJson('/api/admin/subscription/cancel-trial');

        $response->assertOk()
            ->assertJsonPath('subscription.status', 'canceled')
            ->assertJsonPath('subscription.can_cancel_trial', false);

        $this->assertFalse($this->org->fresh()->onTrial());
        $this->assertNotNull($this->org->fresh()->canceled_at);
    }

    public function test_admin_cannot_cancel_trial_when_not_on_trial(): void
    {
        Sanctum::actingAs($this->admin);

        $this->org->forceFill(['trial_ends_at' => null, 'subscription_status' => 'active'])->save();

        $response = $this->postJson('/api/admin/subscription/cancel-trial');

        $response->assertStatus(422);
    }

    public function test_admin_can_initiate_mobile_payment(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/admin/subscription/payments/initiate', [
            'plan' => 'business',
            'billing_cycle' => 'monthly',
            'mobile_provider' => 'mpesa',
            'phone_number' => '0754123456',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'payment' => [
                    'id',
                    'reference',
                    'plan',
                    'amount',
                    'formatted_amount',
                    'mobile_provider',
                    'phone_number',
                    'status',
                ],
                'instructions' => [
                    'provider',
                    'ussd_code',
                    'steps',
                ],
            ]);

        $this->assertDatabaseHas('payments', [
            'organization_id' => $this->org->id,
            'plan' => 'business',
            'mobile_provider' => 'mpesa',
            'status' => 'pending',
            'amount' => 120000,
        ]);
    }

    public function test_admin_can_verify_and_activate_subscription_via_mobile_payment(): void
    {
        Sanctum::actingAs($this->admin);

        $payment = Payment::create([
            'organization_id' => $this->org->id,
            'user_id' => $this->admin->id,
            'plan' => 'business',
            'billing_cycle' => 'monthly',
            'amount' => 120000,
            'original_amount' => 120000,
            'discount_amount' => 0,
            'currency' => 'TZS',
            'mobile_provider' => 'tigopesa',
            'phone_number' => '0655123456',
            'reference' => 'SA-PAY-TEST-01',
            'ussd_code' => '*150*01#',
            'status' => Payment::STATUS_PENDING,
        ]);

        $response = $this->postJson("/api/admin/subscription/payments/{$payment->id}/verify");

        $response->assertOk()
            ->assertJsonPath('payment.status', 'completed')
            ->assertJsonPath('subscription.status', 'active')
            ->assertJsonPath('subscription.plan', 'business');

        $this->assertTrue($payment->fresh()->isCompleted());
        $this->assertEquals('business', $this->org->fresh()->plan);
        $this->assertEquals('active', $this->org->fresh()->subscription_status);
        $this->assertNull($this->org->fresh()->trial_ends_at);
    }

    public function test_admin_can_simulate_payment_and_view_history(): void
    {
        Sanctum::actingAs($this->admin);

        $payment = Payment::create([
            'organization_id' => $this->org->id,
            'user_id' => $this->admin->id,
            'plan' => 'starter',
            'billing_cycle' => 'annual',
            'amount' => 432000,
            'original_amount' => 432000,
            'discount_amount' => 0,
            'currency' => 'TZS',
            'mobile_provider' => 'airtelmoney',
            'phone_number' => '0688123456',
            'reference' => 'SA-PAY-TEST-02',
            'ussd_code' => '*150*60#',
            'status' => Payment::STATUS_PENDING,
        ]);

        $simulateResponse = $this->postJson("/api/admin/subscription/payments/{$payment->id}/simulate");
        $simulateResponse->assertOk()->assertJsonPath('payment.status', 'completed');

        $historyResponse = $this->getJson('/api/admin/subscription/payments');
        $historyResponse->assertOk()
            ->assertJsonStructure([
                'payments' => [
                    'data' => [
                        '*' => ['id', 'reference', 'plan', 'amount', 'mobile_provider', 'status'],
                    ],
                ],
                'total_spent',
                'total_spent_formatted',
            ]);
    }

    public function test_cancelling_subscription_locks_management_features_until_subscribed_and_allows_subscription_routes(): void
    {
        Sanctum::actingAs($this->admin);

        // Cancel free trial
        $cancelRes = $this->postJson('/api/admin/subscription/cancel-trial');
        $cancelRes->assertOk();

        $this->assertFalse($this->org->fresh()->isAccessible());

        // Management routes (e.g. employees, stats) should return 403 SUBSCRIPTION_REQUIRED
        $employeesRes = $this->getJson('/api/admin/employees');
        $employeesRes->assertStatus(403)
            ->assertJsonPath('code', 'SUBSCRIPTION_REQUIRED')
            ->assertJsonPath('accessible', false);

        $statsRes = $this->getJson('/api/admin/stats');
        $statsRes->assertStatus(403)
            ->assertJsonPath('code', 'SUBSCRIPTION_REQUIRED');

        // But subscription details and payment initiation MUST still be accessible so admin can subscribe!
        $subRes = $this->getJson('/api/admin/subscription');
        $subRes->assertOk()
            ->assertJsonPath('subscription.status', 'canceled')
            ->assertJsonPath('subscription.accessible', false);

        // Initiate payment to subscribe
        $payRes = $this->postJson('/api/admin/subscription/payments/initiate', [
            'plan' => 'business',
            'billing_cycle' => 'monthly',
            'mobile_provider' => 'mpesa',
            'phone_number' => '0754123456',
        ]);
        $payRes->assertStatus(201);
        $paymentId = $payRes->json('payment.id');

        // Complete/verify payment
        $verifyRes = $this->postJson("/api/admin/subscription/payments/{$paymentId}/verify");
        $verifyRes->assertOk()
            ->assertJsonPath('payment.status', 'completed')
            ->assertJsonPath('subscription.status', 'active');

        // Now management access is restored and unlocked!
        $this->assertTrue($this->org->fresh()->isAccessible());
        $employeesRestored = $this->getJson('/api/admin/employees');
        $employeesRestored->assertOk();
    }
}
