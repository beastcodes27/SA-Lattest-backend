<?php

namespace Tests\Feature;

use App\Mail\SystemAdminLoginAlert;
use App\Models\User;
use App\Support\TotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PortalMfaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->admin = User::create([
            'name' => 'System Admin',
            'email' => 'sysadmin@example.com',
            'employee_id' => 'SYS-ADMIN1',
            'password' => 'password1234',
            'role' => 'superadmin',
            'active' => true,
        ]);
    }

    public function test_admin_can_enable_mfa_and_sign_in_with_a_totp_code(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/portal/api/mfa/setup')
            ->assertOk()
            ->assertJsonStructure(['secret', 'otpauth_uri']);

        $secret = session('mfa_setup_secret');
        $this->assertNotEmpty($secret);

        $this->actingAs($this->admin)
            ->postJson('/portal/api/mfa/enable', ['code' => TotpService::code($secret)])
            ->assertOk();

        $this->admin->refresh();
        $this->assertTrue($this->admin->mfa_enabled);
        $this->assertSame($secret, $this->admin->mfa_secret);

        Auth::logout();
        $this->flushSession();

        $this->post('/portal/login', [
            'employee_id' => 'SYS-ADMIN1',
            'password' => 'password1234',
        ])->assertRedirect('/portal/mfa');

        $this->get('/portal/mfa')->assertOk();

        $this->post('/portal/mfa', [
            'code' => TotpService::code($secret),
        ])->assertRedirect('/portal/dashboard');

        $this->assertAuthenticatedAs($this->admin);

        Mail::assertSent(SystemAdminLoginAlert::class, function (SystemAdminLoginAlert $mail) {
            return $mail->hasTo('sysadmin@example.com');
        });
    }

    public function test_invalid_mfa_code_is_rejected(): void
    {
        $secret = TotpService::generateSecret();

        $this->admin->forceFill([
            'mfa_secret' => $secret,
            'mfa_enabled' => true,
            'mfa_confirmed_at' => now(),
        ])->save();

        $this->post('/portal/login', [
            'employee_id' => 'SYS-ADMIN1',
            'password' => 'password1234',
        ])->assertRedirect('/portal/mfa');

        $this->post('/portal/mfa', ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_admin_can_disable_mfa_with_their_password(): void
    {
        $secret = TotpService::generateSecret();

        $this->admin->forceFill([
            'mfa_secret' => $secret,
            'mfa_enabled' => true,
            'mfa_confirmed_at' => now(),
        ])->save();

        $this->actingAs($this->admin)->postJson('/portal/api/mfa/disable', [
            'password' => 'password1234',
        ])->assertOk();

        $this->admin->refresh();
        $this->assertFalse($this->admin->mfa_enabled);
        $this->assertNull($this->admin->mfa_secret);
    }

    public function test_totp_codes_validate_for_the_current_window(): void
    {
        $secret = TotpService::generateSecret();

        $this->assertTrue(TotpService::verify($secret, TotpService::code($secret)));
        $this->assertFalse(TotpService::verify($secret, '000000'));
    }
}
