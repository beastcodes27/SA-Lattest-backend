<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmployeeFirstLoginPasswordTest extends TestCase
{
    use RefreshDatabase;

    private const ORG_DEFAULT_PASSWORD = 'OrgDefault#2026';

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

        $this->org = Organization::create([
            'name' => 'Apex Logistics Tanzania',
            'contact_email' => 'admin@apexlogistics.tz',
            'plan' => 'starter',
            'status' => 'active',
        ]);
        $this->org->forceFill([
            'subscription_status' => 'active',
            'default_employee_password' => self::ORG_DEFAULT_PASSWORD,
        ])->save();

        $this->branch = Branch::create([
            'org_id' => $this->org->id,
            'name' => 'Main Office Dar es Salaam',
            'lat' => -6.7924,
            'lng' => 39.2083,
            'radius_meters' => 100,
            'active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Amina Salum',
            'email' => 'amina@apexlogistics.tz',
            'employee_id' => 'APX-ADM',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'org_id' => $this->org->id,
            'active' => true,
            'must_change_password' => false,
        ]);

        $this->employee = User::create([
            'name' => 'Baraka Juma',
            'email' => null,
            'employee_id' => 'APX-001',
            'password' => bcrypt(self::ORG_DEFAULT_PASSWORD),
            'role' => 'employee',
            'org_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'active' => true,
            'must_change_password' => true,
        ]);
    }

    public function test_admin_reset_sets_employee_password_to_organization_default(): void
    {
        $this->employee->forceFill([
            'password' => 'SomeOldPassword1',
            'must_change_password' => false,
        ])->save();
        $this->employee->createToken('employee-device');

        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/admin/employees/{$this->employee->id}/reset-password");

        $response->assertOk()
            ->assertJsonMissingPath('default_password');

        $employee = $this->employee->fresh();
        $this->assertTrue(Hash::check(self::ORG_DEFAULT_PASSWORD, $employee->password));
        $this->assertTrue($employee->must_change_password);
        $this->assertSame(0, $employee->tokens()->count());
    }

    public function test_first_login_requires_email_and_phone(): void
    {
        Sanctum::actingAs($this->employee);

        $this->putJson('/api/me/password/force', [
            'new_password' => 'MyVeryOwnPass1',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'phone']);
    }

    public function test_first_login_rejects_the_organization_default_password(): void
    {
        Sanctum::actingAs($this->employee);

        $this->putJson('/api/me/password/force', [
            'new_password' => self::ORG_DEFAULT_PASSWORD,
            'email' => 'baraka@apexlogistics.tz',
            'phone' => '0755112233',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'New password cannot be the organization default or temporary password. Please choose a unique password.');
    }

    public function test_first_login_requires_only_password_when_contact_details_exist(): void
    {
        $this->employee->forceFill([
            'email' => 'baraka@apexlogistics.tz',
            'phone' => '0755112233',
        ])->save();

        Sanctum::actingAs($this->employee);

        $this->putJson('/api/me/password/force', [
            'new_password' => 'MyVeryOwnPass1',
        ])->assertOk();

        $employee = $this->employee->fresh();
        $this->assertTrue(Hash::check('MyVeryOwnPass1', $employee->password));
        $this->assertFalse($employee->must_change_password);
        $this->assertSame('baraka@apexlogistics.tz', $employee->email);
        $this->assertSame('0755112233', $employee->phone);
    }

    public function test_first_login_saves_contact_details_and_new_password(): void
    {
        Sanctum::actingAs($this->employee);

        $this->putJson('/api/me/password/force', [
            'new_password' => 'MyVeryOwnPass1',
            'email' => 'baraka@apexlogistics.tz',
            'phone' => '0755112233',
        ])->assertOk();

        $employee = $this->employee->fresh();
        $this->assertTrue(Hash::check('MyVeryOwnPass1', $employee->password));
        $this->assertFalse($employee->must_change_password);
        $this->assertSame('baraka@apexlogistics.tz', $employee->email);
        $this->assertSame('0755112233', $employee->phone);
    }
}
