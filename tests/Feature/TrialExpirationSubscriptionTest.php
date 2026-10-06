<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\Package;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TrialExpirationSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
    private Branch $branch;
    private User $admin;
    private User $employee;

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
            'name' => 'Safari Horizons Tanzania',
            'contact_email' => 'director@safarihorizons.tz',
            'plan' => 'starter',
            'status' => 'active',
            'trial_started_at' => now()->subDays(30),
            'trial_ends_at' => now()->subDay(), // Expired free trial
        ]);

        $this->branch = Branch::create([
            'org_id' => $this->org->id,
            'name' => 'Arusha Operations Hub',
            'lat' => -3.3869,
            'lng' => 36.6830,
            'radius_meters' => 100,
            'active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Grace Mushi',
            'email' => 'grace@safarihorizons.tz',
            'employee_id' => 'SAF-ADM-01',
            'phone' => '0754112233',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'org_id' => $this->org->id,
            'active' => true,
            'must_change_password' => false,
        ]);

        $this->employee = User::create([
            'name' => 'Juma Athumani',
            'email' => 'juma@safarihorizons.tz',
            'employee_id' => 'SAF-001',
            'phone' => '0788112233',
            'password' => bcrypt('password123'),
            'role' => 'employee',
            'org_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'active' => true,
            'must_change_password' => false,
        ]);
    }

    public function test_admin_and_employee_can_login_after_trial_expires(): void
    {
        $this->assertFalse($this->org->fresh()->isAccessible());

        // Admin login
        $adminLoginRes = $this->postJson('/api/auth/login', [
            'employee_id' => 'SAF-ADM-01',
            'password' => 'password123',
        ]);

        $adminLoginRes->assertStatus(200)
            ->assertJsonStructure(['token', 'user'])
            ->assertJsonPath('user.org.accessible', false)
            ->assertJsonPath('user.org.on_trial', false);

        // Employee login
        $empLoginRes = $this->postJson('/api/auth/login', [
            'employee_id' => 'SAF-001',
            'password' => 'password123',
        ]);

        $empLoginRes->assertStatus(200)
            ->assertJsonStructure(['token', 'user'])
            ->assertJsonPath('user.org.accessible', false);
    }

    public function test_expired_trial_gates_management_routes_while_leaving_subscription_open(): void
    {
        Sanctum::actingAs($this->admin);

        // Management route is locked
        $employeesRes = $this->getJson('/api/admin/employees');
        $employeesRes->assertStatus(403)
            ->assertJsonPath('code', 'SUBSCRIPTION_REQUIRED')
            ->assertJsonPath('accessible', false);

        // Subscription details route is accessible and shows trial_expired
        $subRes = $this->getJson('/api/admin/subscription');
        $subRes->assertStatus(200)
            ->assertJsonPath('subscription.accessible', false)
            ->assertJsonPath('subscription.trial_expired', true)
            ->assertJsonPath('subscription.requires_payment', true)
            ->assertJsonStructure([
                'subscription' => [
                    'packages',
                    'mobile_payment_providers',
                    'payment_prompt',
                ],
            ]);
    }

    public function test_mobile_payment_simulation_activates_subscription_and_unlocks_features(): void
    {
        Sanctum::actingAs($this->admin);

        // 1. Initiate mobile payment order for business plan
        $initiateRes = $this->postJson('/api/admin/subscription/payments/initiate', [
            'plan' => 'business',
            'billing_cycle' => 'monthly',
            'mobile_provider' => 'mpesa',
            'phone_number' => '0754112233',
        ]);

        $initiateRes->assertStatus(201)
            ->assertJsonPath('payment.plan', 'business')
            ->assertJsonPath('payment.status', 'pending');

        $paymentId = $initiateRes->json('payment.id');

        // 2. Simulate successful mobile payment
        $simulateRes = $this->postJson("/api/admin/subscription/payments/{$paymentId}/simulate");
        $simulateRes->assertStatus(200)
            ->assertJsonPath('payment.status', 'completed')
            ->assertJsonPath('subscription.accessible', true)
            ->assertJsonPath('subscription.status', 'active');

        // 3. Organization is now active and accessible
        $freshOrg = $this->org->fresh();
        $this->assertTrue($freshOrg->isAccessible());
        $this->assertEquals('active', $freshOrg->subscription_status);
        $this->assertEquals('business', $freshOrg->plan);

        // 4. Management routes are now fully unlocked
        $employeesRes = $this->getJson('/api/admin/employees');
        $employeesRes->assertStatus(200)
            ->assertJsonStructure(['employees']);

        // 5. Employee check-in is now allowed
        Sanctum::actingAs($this->employee);
        $toggleRes = $this->postJson('/api/attendance/toggle', [
            'lat' => -3.3869,
            'lng' => 36.6830,
        ]);
        $toggleRes->assertStatus(200)
            ->assertJsonPath('is_checked_in', true);
    }
}
