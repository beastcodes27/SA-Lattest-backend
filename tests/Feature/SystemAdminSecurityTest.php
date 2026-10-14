<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SystemAdminSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $rootSuperadmin;

    private User $superadmin;

    private User $minorAdmin;

    private User $orgAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootSuperadmin = User::create([
            'name' => 'Root Super Admin',
            'email' => 'root@smartattend.co.tz',
            'employee_id' => 'SYSTEM_SUPERADMIN',
            'password' => bcrypt('password123'),
            'role' => 'superadmin',
            'org_id' => null,
            'active' => true,
        ]);

        $this->superadmin = User::create([
            'name' => 'Platform Super',
            'email' => 'super@smartattend.co.tz',
            'employee_id' => 'SYS-0002',
            'password' => bcrypt('password123'),
            'role' => 'superadmin',
            'org_id' => null,
            'active' => true,
        ]);

        $this->minorAdmin = User::create([
            'name' => 'Support Minor',
            'email' => 'minor@smartattend.co.tz',
            'employee_id' => 'SYS-0003',
            'password' => bcrypt('password123'),
            'role' => 'minor_admin',
            'org_id' => null,
            'active' => true,
        ]);

        $org = Organization::create([
            'name' => 'Apex Logistics Tanzania',
            'contact_email' => 'admin@apex.tz',
            'plan' => 'starter',
            'status' => 'active',
        ]);

        $this->orgAdmin = User::create([
            'name' => 'Org Admin',
            'email' => 'admin@apex.tz',
            'employee_id' => 'APX-0001',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'org_id' => $org->id,
            'active' => true,
        ]);
    }

    public function test_minor_admin_cannot_create_a_superadmin(): void
    {
        Sanctum::actingAs($this->minorAdmin);

        $this->postJson('/api/system/minor-admins', [
            'name' => 'Escalated',
            'email' => 'escalated@smartattend.co.tz',
            'employee_id' => 'SYS-9999',
            'password' => 'password123',
            'role' => 'superadmin',
        ])->assertStatus(403);
    }

    public function test_minor_admin_cannot_manage_another_superadmin(): void
    {
        Sanctum::actingAs($this->minorAdmin);

        $this->postJson("/api/system/minor-admins/{$this->superadmin->id}/reset-password", [
            'password' => 'hijacked123',
        ])->assertStatus(403);

        $this->patchJson("/api/system/minor-admins/{$this->superadmin->id}")
            ->assertStatus(403);

        $this->deleteJson("/api/system/minor-admins/{$this->superadmin->id}")
            ->assertStatus(403);
    }

    public function test_root_superadmin_account_is_protected(): void
    {
        Sanctum::actingAs($this->superadmin);

        $this->postJson("/api/system/minor-admins/{$this->rootSuperadmin->id}/reset-password", [
            'password' => 'hijacked123',
        ])->assertStatus(422);

        $this->deleteJson("/api/system/minor-admins/{$this->rootSuperadmin->id}")
            ->assertStatus(422);

        $this->patchJson("/api/system/minor-admins/{$this->rootSuperadmin->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('users', ['id' => $this->rootSuperadmin->id, 'deleted_at' => null]);
    }

    public function test_system_admin_cannot_target_an_organization_user(): void
    {
        Sanctum::actingAs($this->superadmin);

        $this->deleteJson("/api/system/minor-admins/{$this->orgAdmin->id}")
            ->assertStatus(404);

        $this->postJson("/api/system/minor-admins/{$this->orgAdmin->id}/reset-password", [
            'password' => 'newpassword1',
        ])->assertStatus(404);

        $this->assertDatabaseHas('users', ['id' => $this->orgAdmin->id, 'deleted_at' => null]);
    }

    public function test_superadmin_can_create_a_minor_admin(): void
    {
        Sanctum::actingAs($this->superadmin);

        $this->postJson('/api/system/minor-admins', [
            'name' => 'New Support',
            'email' => 'support2@smartattend.co.tz',
            'employee_id' => 'SYS-0004',
            'password' => 'password123',
            'role' => 'minor_admin',
        ])->assertCreated();

        $this->assertDatabaseHas('users', [
            'employee_id' => 'SYS-0004',
            'role' => 'minor_admin',
        ]);
    }

    public function test_minor_admin_can_manage_another_minor_admin(): void
    {
        $peer = User::create([
            'name' => 'Peer Minor',
            'email' => 'peer@smartattend.co.tz',
            'employee_id' => 'SYS-0005',
            'password' => bcrypt('password123'),
            'role' => 'minor_admin',
            'org_id' => null,
            'active' => true,
        ]);

        Sanctum::actingAs($this->minorAdmin);

        $this->patchJson("/api/system/minor-admins/{$peer->id}")->assertOk();
        $this->assertFalse($peer->fresh()->active);
    }
}
