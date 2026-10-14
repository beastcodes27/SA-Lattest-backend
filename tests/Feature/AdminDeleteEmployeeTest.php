<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminDeleteEmployeeTest extends TestCase
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

        $this->org = Organization::create([
            'name' => 'Apex Logistics Tanzania',
            'contact_email' => 'admin@apexlogistics.tz',
            'plan' => 'starter',
            'status' => 'active',
        ]);
        $this->org->forceFill(['subscription_status' => 'active'])->save();

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

    public function test_admin_can_delete_an_employee_but_keeps_attendance_history(): void
    {
        Attendance::create([
            'user_id' => $this->employee->id,
            'branch_id' => $this->branch->id,
            'type' => 'in',
            'lat' => -6.7924,
            'lng' => 39.2083,
            'occurred_at' => now(),
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->deleteJson("/api/admin/employees/{$this->employee->id}");

        $response->assertOk()
            ->assertJsonPath('id', $this->employee->id);

        // Account is soft-deleted but removed from active staff lists.
        $this->assertSoftDeleted('users', ['id' => $this->employee->id]);
        $this->assertCount(0, $this->org->fresh()->users()->where('role', 'employee')->get());

        // Attendance history is preserved for reporting.
        $this->assertDatabaseHas('attendances', ['user_id' => $this->employee->id]);
    }

    public function test_admin_cannot_delete_an_employee_from_another_organization(): void
    {
        $otherOrg = Organization::create([
            'name' => 'Coastal Retailers',
            'contact_email' => 'admin@coastal.tz',
            'plan' => 'starter',
            'status' => 'active',
        ]);

        $outsider = User::create([
            'name' => 'Zawadi Moto',
            'email' => 'zawadi@coastal.tz',
            'employee_id' => 'CRT-001',
            'password' => bcrypt('password123'),
            'role' => 'employee',
            'org_id' => $otherOrg->id,
            'active' => true,
            'must_change_password' => false,
        ]);

        Sanctum::actingAs($this->admin);

        $this->deleteJson("/api/admin/employees/{$outsider->id}")->assertStatus(404);

        $this->assertDatabaseHas('users', ['id' => $outsider->id]);
    }

    public function test_admin_cannot_delete_another_admin_account(): void
    {
        $secondAdmin = User::create([
            'name' => 'Juma Ally',
            'email' => 'juma@apexlogistics.tz',
            'employee_id' => 'APX-ADM2',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'org_id' => $this->org->id,
            'active' => true,
            'must_change_password' => false,
        ]);

        Sanctum::actingAs($this->admin);

        $this->deleteJson("/api/admin/employees/{$secondAdmin->id}")->assertStatus(404);

        $this->assertDatabaseHas('users', ['id' => $secondAdmin->id]);
    }
}
