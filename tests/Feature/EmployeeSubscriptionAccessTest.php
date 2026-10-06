<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmployeeSubscriptionAccessTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
    private Branch $branch;
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

        $this->org = Organization::create([
            'name' => 'Apex Logistics Tanzania',
            'contact_email' => 'admin@apexlogistics.tz',
            'plan' => 'starter',
            'status' => 'active',
        ]);

        $this->branch = Branch::create([
            'org_id' => $this->org->id,
            'name' => 'Main Office Dar es Salaam',
            'lat' => -6.7924,
            'lng' => 39.2083,
            'radius_meters' => 100,
            'active' => true,
        ]);

        $this->employee = User::create([
            'name' => 'Baraka Juma',
            'email' => 'baraka@apexlogistics.tz',
            'employee_id' => 'APX-001',
            'phone' => '0755112233',
            'password' => bcrypt('password123'),
            'role' => 'employee',
            'org_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'active' => true,
            'must_change_password' => false,
        ]);
    }

    public function test_employee_checkin_is_rejected_when_organization_has_no_active_package(): void
    {
        // Cancel trial / subscription
        $this->org->forceFill([
            'subscription_status' => 'canceled',
            'trial_ends_at' => now()->subDay(),
            'canceled_at' => now(),
        ])->save();

        $this->assertFalse($this->org->fresh()->isAccessible());

        Sanctum::actingAs($this->employee);

        $response = $this->postJson('/api/attendance/toggle', [
            'lat' => -6.7924,
            'lng' => 39.2083,
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('code', 'SUBSCRIPTION_REQUIRED')
            ->assertJsonPath('accessible', false)
            ->assertJsonFragment([
                'message' => 'Attendance service is temporarily paused because your organization does not have an active package. Please contact your administrator or HR to renew the subscription.',
            ]);
    }

    public function test_employee_attendance_sync_is_rejected_when_subscription_is_inactive(): void
    {
        $this->org->forceFill([
            'subscription_status' => 'canceled',
            'trial_ends_at' => now()->subDay(),
        ])->save();

        Sanctum::actingAs($this->employee);

        $response = $this->postJson('/api/attendance/sync', [
            'records' => [
                [
                    'type' => 'in',
                    'lat' => -6.7924,
                    'lng' => 39.2083,
                    'occurred_at' => now()->toIso8601String(),
                ],
            ],
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('code', 'SUBSCRIPTION_REQUIRED')
            ->assertJsonPath('accessible', false);
    }
}
