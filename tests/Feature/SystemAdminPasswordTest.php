<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SystemAdminPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function systemAdmin(array $attributes = []): User
    {
        return User::create([
            'name' => 'System Admin',
            'email' => 'sysadmin@example.com',
            'employee_id' => 'SYS-ADMIN1',
            'password' => 'OldPassword@123',
            'role' => 'superadmin',
            'active' => true,
            ...$attributes,
        ]);
    }

    public function test_system_admin_can_change_own_password(): void
    {
        $admin = $this->systemAdmin();

        $response = $this->actingAs($admin)->postJson('/portal/api/password', [
            'current_password' => 'OldPassword@123',
            'new_password' => 'NewPassword@456',
            'new_password_confirmation' => 'NewPassword@456',
        ]);

        $response->assertOk()->assertJson(['message' => 'Password changed successfully.']);
        $this->assertTrue(Hash::check('NewPassword@456', $admin->fresh()->password));
    }

    public function test_change_password_rejects_incorrect_current_password(): void
    {
        $admin = $this->systemAdmin();

        $this->actingAs($admin)->postJson('/portal/api/password', [
            'current_password' => 'WrongPassword@123',
            'new_password' => 'NewPassword@456',
            'new_password_confirmation' => 'NewPassword@456',
        ])->assertStatus(422);

        $this->assertTrue(Hash::check('OldPassword@123', $admin->fresh()->password));
    }

    public function test_change_password_requires_matching_confirmation(): void
    {
        $admin = $this->systemAdmin();

        $this->actingAs($admin)->postJson('/portal/api/password', [
            'current_password' => 'OldPassword@123',
            'new_password' => 'NewPassword@456',
            'new_password_confirmation' => 'DifferentPassword@456',
        ])->assertStatus(422);
    }

    public function test_non_system_admin_cannot_change_password_via_portal(): void
    {
        $employee = $this->systemAdmin(['role' => 'employee', 'employee_id' => 'EMP-001']);

        $this->actingAs($employee)->postJson('/portal/api/password', [
            'current_password' => 'OldPassword@123',
            'new_password' => 'NewPassword@456',
            'new_password_confirmation' => 'NewPassword@456',
        ])->assertUnauthorized();
    }
}
