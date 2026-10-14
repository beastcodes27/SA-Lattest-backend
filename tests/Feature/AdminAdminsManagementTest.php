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

class AdminAdminsManagementTest extends TestCase
{
    use RefreshDatabase;

    private const ORG_DEFAULT_PASSWORD = 'OrgDefault#2026';

    private Organization $org;

    private User $defaultAdmin;

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
            'employee_id_prefix' => 'APX',
            'default_employee_password' => self::ORG_DEFAULT_PASSWORD,
        ])->save();

        Branch::create([
            'org_id' => $this->org->id,
            'name' => 'Main Office Dar es Salaam',
            'lat' => -6.7924,
            'lng' => 39.2083,
            'radius_meters' => 100,
            'active' => true,
        ]);

        $this->defaultAdmin = User::create([
            'name' => 'Amina Salum',
            'email' => 'amina@apexlogistics.tz',
            'employee_id' => 'APX-0001',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'is_default_admin' => true,
            'org_id' => $this->org->id,
            'active' => true,
            'must_change_password' => false,
        ]);
    }

    public function test_admin_can_add_another_organization_admin(): void
    {
        Sanctum::actingAs($this->defaultAdmin);

        $response = $this->postJson('/api/admin/admins', [
            'name' => 'Juma Ally',
            'email' => 'juma@apexlogistics.tz',
        ]);

        $response->assertCreated()
            ->assertJsonPath('admin.name', 'Juma Ally')
            ->assertJsonPath('admin.is_default_admin', false);

        $created = User::where('email', 'juma@apexlogistics.tz')->first();
        $this->assertNotNull($created);
        $this->assertSame('admin', $created->role);
        $this->assertFalse($created->is_default_admin);
        $this->assertTrue($created->must_change_password);
        $this->assertTrue(Hash::check(self::ORG_DEFAULT_PASSWORD, $created->password));
    }

    public function test_default_admin_and_added_admins_are_listed(): void
    {
        User::create([
            'name' => 'Juma Ally',
            'email' => 'juma@apexlogistics.tz',
            'employee_id' => 'APX-0002',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'org_id' => $this->org->id,
            'active' => true,
            'must_change_password' => false,
        ]);

        Sanctum::actingAs($this->defaultAdmin);

        $response = $this->getJson('/api/admin/admins');

        $response->assertOk()
            ->assertJsonPath('admins.0.employee_id', 'APX-0001')
            ->assertJsonPath('admins.0.is_default_admin', true)
            ->assertJsonCount(2, 'admins');
    }

    public function test_default_admin_cannot_be_removed(): void
    {
        Sanctum::actingAs($this->defaultAdmin);

        $this->deleteJson("/api/admin/admins/{$this->defaultAdmin->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('users', [
            'id' => $this->defaultAdmin->id,
            'deleted_at' => null,
        ]);
    }

    public function test_added_admin_can_be_removed(): void
    {
        $added = User::create([
            'name' => 'Juma Ally',
            'email' => 'juma@apexlogistics.tz',
            'employee_id' => 'APX-0002',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'org_id' => $this->org->id,
            'active' => true,
            'must_change_password' => false,
        ]);

        Sanctum::actingAs($this->defaultAdmin);

        $this->deleteJson("/api/admin/admins/{$added->id}")->assertOk();

        $this->assertSoftDeleted('users', ['id' => $added->id]);
    }
}
