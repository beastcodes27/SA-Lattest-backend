<?php

namespace Tests\Feature;

use App\Mail\OneTimePasscode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
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

    private function issueOtp(User $user, string $code = '123456'): void
    {
        DB::table('otp_codes')->updateOrInsert(
            ['user_id' => $user->id, 'purpose' => 'password_change'],
            [
                'code' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(15),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function test_system_admin_can_change_own_password(): void
    {
        $admin = $this->systemAdmin();
        $this->issueOtp($admin);

        $response = $this->actingAs($admin)->postJson('/portal/api/password', [
            'current_password' => 'OldPassword@123',
            'new_password' => 'NewPassword@456',
            'new_password_confirmation' => 'NewPassword@456',
            'otp' => '123456',
        ]);

        $response->assertOk()->assertJson(['message' => 'Password changed successfully.']);
        $this->assertTrue(Hash::check('NewPassword@456', $admin->fresh()->password));
    }

    public function test_change_password_rejects_incorrect_current_password(): void
    {
        $admin = $this->systemAdmin();
        $this->issueOtp($admin);

        $this->actingAs($admin)->postJson('/portal/api/password', [
            'current_password' => 'WrongPassword@123',
            'new_password' => 'NewPassword@456',
            'new_password_confirmation' => 'NewPassword@456',
            'otp' => '123456',
        ])->assertStatus(422);

        $this->assertTrue(Hash::check('OldPassword@123', $admin->fresh()->password));
    }

    public function test_change_password_requires_matching_confirmation(): void
    {
        $admin = $this->systemAdmin();
        $this->issueOtp($admin);

        $this->actingAs($admin)->postJson('/portal/api/password', [
            'current_password' => 'OldPassword@123',
            'new_password' => 'NewPassword@456',
            'new_password_confirmation' => 'DifferentPassword@456',
            'otp' => '123456',
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

    public function test_change_password_rejects_invalid_otp(): void
    {
        $admin = $this->systemAdmin();
        $this->issueOtp($admin);

        $this->actingAs($admin)->postJson('/portal/api/password', [
            'current_password' => 'OldPassword@123',
            'new_password' => 'NewPassword@456',
            'new_password_confirmation' => 'NewPassword@456',
            'otp' => '000000',
        ])->assertStatus(422);

        $this->assertTrue(Hash::check('OldPassword@123', $admin->fresh()->password));
    }

    public function test_api_admin_password_change_requires_otp(): void
    {
        $admin = $this->systemAdmin();
        Sanctum::actingAs($admin);

        $this->putJson('/api/me/password', [
            'current_password' => 'OldPassword@123',
            'new_password' => 'NewPassword@456',
        ])->assertStatus(422);

        $this->issueOtp($admin);

        $this->putJson('/api/me/password', [
            'current_password' => 'OldPassword@123',
            'new_password' => 'NewPassword@456',
            'otp' => '123456',
        ])->assertOk();

        $this->assertTrue(Hash::check('NewPassword@456', $admin->fresh()->password));
    }

    public function test_api_employee_password_change_does_not_require_otp(): void
    {
        $employee = $this->systemAdmin(['role' => 'employee', 'employee_id' => 'EMP-001', 'email' => 'employee@example.com']);
        Sanctum::actingAs($employee);

        $this->putJson('/api/me/password', [
            'current_password' => 'OldPassword@123',
            'new_password' => 'NewPassword@456',
        ])->assertOk();

        $this->assertTrue(Hash::check('NewPassword@456', $employee->fresh()->password));
    }

    public function test_admin_can_request_a_password_change_code_by_email(): void
    {
        Mail::fake();
        $admin = $this->systemAdmin();

        $this->actingAs($admin)
            ->postJson('/portal/api/password/send-code')
            ->assertOk();

        Mail::assertSent(OneTimePasscode::class, function (OneTimePasscode $mail) use ($admin) {
            return $mail->hasTo($admin->email) && $mail->purpose === 'password change';
        });
    }
}
