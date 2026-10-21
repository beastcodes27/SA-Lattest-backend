<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemPackageManagementTest extends TestCase
{
    use RefreshDatabase;

    private function systemAdmin(): User
    {
        return User::create([
            'name' => 'System Admin',
            'email' => 'sysadmin@example.com',
            'employee_id' => 'SYS-ADMIN1',
            'password' => 'password123',
            'role' => 'superadmin',
            'active' => true,
        ]);
    }

    public function test_system_admin_can_create_a_package(): void
    {
        $this->actingAs($this->systemAdmin())->postJson('/portal/api/packages', [
            'code' => 'premium',
            'name' => 'Premium',
            'tagline' => 'For large teams that need more',
            'price_label' => 'TZS 200,000 / mo',
            'monthly_price' => 200000,
            'annual_price' => 2000000,
            'currency' => 'TZS',
            'features' => ['Unlimited branches', 'Priority support'],
            'employee_limit' => null,
            'branch_limit' => null,
            'active' => true,
        ])->assertCreated()->assertJsonPath('package.code', 'premium');

        $this->assertDatabaseHas('packages', ['code' => 'premium', 'name' => 'Premium', 'active' => true]);
    }

    public function test_package_codes_must_be_unique(): void
    {
        $admin = $this->systemAdmin();

        $payload = [
            'code' => 'premium',
            'name' => 'Premium',
            'monthly_price' => 200000,
            'annual_price' => 2000000,
            'active' => true,
        ];

        $this->actingAs($admin)->postJson('/portal/api/packages', $payload)->assertCreated();
        $this->actingAs($admin)->postJson('/portal/api/packages', [...$payload, 'name' => 'Premium Plus'])->assertStatus(422);
    }
}
