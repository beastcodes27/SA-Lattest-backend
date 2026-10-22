<?php

namespace Tests\Feature;

use App\Mail\SystemAdminLoginAlert;
use App\Mail\SystemAdminWelcome;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PortalAdminManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    private function systemAdmin(string $role = 'superadmin', array $attributes = []): User
    {
        return User::create([
            'name' => 'System Admin',
            'email' => 'sysadmin@example.com',
            'employee_id' => 'SYS-ADMIN1',
            'password' => 'password123',
            'role' => $role,
            'active' => true,
            ...$attributes,
        ]);
    }

    public function test_system_admin_login_sends_security_alert_email(): void
    {
        $admin = $this->systemAdmin();

        $this->post('/portal/login', [
            'employee_id' => $admin->employee_id,
            'password' => 'password123',
        ])->assertRedirect('/portal/dashboard');

        Mail::assertSent(SystemAdminLoginAlert::class, function (SystemAdminLoginAlert $mail) use ($admin) {
            return $mail->hasTo($admin->email) && $mail->user->is($admin);
        });
    }

    public function test_minor_admin_can_access_the_portal(): void
    {
        $admin = $this->systemAdmin('minor_admin', [
            'email' => 'minor@example.com',
            'employee_id' => 'SYS-MINOR1',
        ]);

        $this->post('/portal/login', [
            'employee_id' => $admin->employee_id,
            'password' => 'password123',
        ])->assertRedirect('/portal/dashboard');

        $this->get('/portal/dashboard')->assertOk();
    }

    public function test_superadmin_can_create_and_manage_another_admin(): void
    {
        $superadmin = $this->systemAdmin();

        $this->actingAs($superadmin)->postJson('/portal/api/admins', [
            'name' => 'Monitoring Admin',
            'email' => 'monitor@example.com',
            'employee_id' => 'SYS-MON001',
            'password' => 'password123',
            'role' => 'minor_admin',
        ])->assertCreated();

        $created = User::where('employee_id', 'SYS-MON001')->firstOrFail();
        $this->assertSame('minor_admin', $created->role);
        $this->assertTrue((bool) $created->must_change_password);

        Mail::assertSent(SystemAdminWelcome::class, function (SystemAdminWelcome $mail) use ($created) {
            return $mail->hasTo($created->email)
                && $mail->admin->is($created)
                && $mail->temporaryPassword === 'password123';
        });

        $this->actingAs($superadmin)
            ->patchJson("/portal/api/admins/{$created->id}")
            ->assertOk();

        $this->assertFalse($created->fresh()->active);

        $this->actingAs($superadmin)
            ->postJson("/portal/api/admins/{$created->id}/reset-password", ['password' => 'NewPassword@123'])
            ->assertOk();

        $this->assertTrue(Hash::check('NewPassword@123', $created->fresh()->password));

        $this->actingAs($superadmin)
            ->deleteJson("/portal/api/admins/{$created->id}")
            ->assertOk();

        $this->assertSoftDeleted('users', ['id' => $created->id]);
    }

    public function test_minor_admin_cannot_create_a_superadmin(): void
    {
        $minor = $this->systemAdmin('minor_admin', [
            'email' => 'minor@example.com',
            'employee_id' => 'SYS-MINOR1',
        ]);

        $this->actingAs($minor)->postJson('/portal/api/admins', [
            'name' => 'Escalated Admin',
            'email' => 'escalated@example.com',
            'employee_id' => 'SYS-ESC001',
            'password' => 'password123',
            'role' => 'superadmin',
        ])->assertStatus(403);
    }

    public function test_minor_admin_cannot_create_another_admin(): void
    {
        $minor = $this->systemAdmin('minor_admin', [
            'email' => 'minor@example.com',
            'employee_id' => 'SYS-MINOR1',
        ]);

        $this->actingAs($minor)->postJson('/portal/api/admins', [
            'name' => 'Support Admin',
            'email' => 'support@example.com',
            'employee_id' => 'SYS-SUP001',
            'password' => 'password123',
            'role' => 'minor_admin',
        ])->assertStatus(403);
    }

    public function test_minor_admin_cannot_list_or_manage_administrators(): void
    {
        $minor = $this->systemAdmin('minor_admin', [
            'email' => 'minor@example.com',
            'employee_id' => 'SYS-MINOR1',
        ]);

        $peer = $this->systemAdmin('minor_admin', [
            'name' => 'Peer Admin',
            'email' => 'peer@example.com',
            'employee_id' => 'SYS-PEER1',
        ]);

        $this->actingAs($minor)->getJson('/portal/api/admins')->assertStatus(403);
        $this->actingAs($minor)->patchJson("/portal/api/admins/{$peer->id}")->assertStatus(403);
        $this->actingAs($minor)->postJson("/portal/api/admins/{$peer->id}/reset-password", ['password' => 'newpassword'])->assertStatus(403);
        $this->actingAs($minor)->deleteJson("/portal/api/admins/{$peer->id}")->assertStatus(403);
    }
}
