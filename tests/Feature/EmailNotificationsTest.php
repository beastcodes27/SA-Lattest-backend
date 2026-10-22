<?php

namespace Tests\Feature;

use App\Mail\Announcement;
use App\Mail\NewOrganizationRegistration;
use App\Mail\NotificationMessage;
use App\Mail\OneTimePasscode;
use App\Mail\OrganizationApproved;
use App\Mail\OrganizationRegistrationReceived;
use App\Mail\OrganizationRejected;
use App\Mail\PasswordResetCode;
use App\Mail\PaymentReceipt;
use App\Mail\PromotionMessage;
use App\Mail\SystemAdminWelcome;
use App\Models\Organization;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Promo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmailNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private User $systemAdmin;

    private Organization $organization;

    private User $orgAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        Package::updateOrCreate(['code' => 'starter'], [
            'name' => 'Starter',
            'monthly_price' => 45000,
            'annual_price' => 432000,
            'currency' => 'TZS',
            'employee_limit' => 50,
            'branch_limit' => 1,
            'active' => true,
        ]);

        $this->systemAdmin = User::create([
            'name' => 'System Admin',
            'email' => 'system@smartattend.co.tz',
            'employee_id' => 'SYS-ADMIN1',
            'password' => bcrypt('password123'),
            'role' => 'superadmin',
            'active' => true,
        ]);

        $this->organization = Organization::create([
            'name' => 'Apex Logistics Tanzania',
            'contact_email' => 'admin@apex.tz',
            'contact_phone' => '+255 712 000 000',
            'plan' => 'starter',
            'status' => 'pending',
        ]);

        $this->orgAdmin = User::create([
            'name' => 'Org Admin',
            'email' => 'admin@apex.tz',
            'employee_id' => 'APX-0001',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'org_id' => $this->organization->id,
            'active' => true,
        ]);
    }

    private function registrationPayload(string $email, ?string $code = null): array
    {
        return [
            'organization' => [
                'name' => 'New Medical Centre',
                'address' => 'Mikocheni, Dar es Salaam',
                'phone' => '+255 712 111 222',
                'tin' => '123-456-789',
                'plan' => 'starter',
                'default_password' => 'Default@123',
            ],
            'admin' => [
                'name' => 'New Admin',
                'email' => $email,
                'employee_id' => 'NMC-0001',
                'password' => 'Password@123',
                'phone' => '+255 713 111 222',
            ],
            'branches' => [
                ['name' => 'Main Office', 'lat' => -6.7924, 'lng' => 39.2083, 'radius_meters' => 150],
            ],
            'email_verification_code' => $code,
        ];
    }

    private function requestRegistrationCode(string $email): string
    {
        $code = null;

        $this->postJson('/api/auth/register-organization/send-code', [
            'email' => $email,
            'name' => 'New Admin',
        ])->assertOk();

        Mail::assertSent(OneTimePasscode::class, function (OneTimePasscode $mail) use ($email, &$code) {
            if ($mail->hasTo($email)) {
                $code = $mail->code;

                return true;
            }

            return false;
        });

        return (string) $code;
    }

    public function test_registration_requires_a_valid_email_verification_code(): void
    {
        $email = 'newadmin@example.com';
        $code = $this->requestRegistrationCode($email);

        $this->postJson('/api/auth/register-organization', $this->registrationPayload($email))
            ->assertStatus(422)
            ->assertJsonValidationErrors('email_verification_code');

        $this->postJson('/api/auth/register-organization', $this->registrationPayload($email, '000000'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('email_verification_code');

        $this->assertDatabaseMissing('users', ['email' => $email]);

        $this->postJson('/api/auth/register-organization', $this->registrationPayload($email, $code))
            ->assertCreated();

        $this->assertDatabaseMissing('email_verification_codes', ['email' => $email]);
    }

    public function test_registration_email_code_can_be_verified_before_submitting(): void
    {
        $email = 'newadmin@example.com';
        $code = $this->requestRegistrationCode($email);

        $this->postJson('/api/auth/register-organization/verify-code', [
            'email' => $email,
            'code' => $code,
        ])->assertOk()->assertJson(['verified' => true]);

        $this->postJson('/api/auth/register-organization', $this->registrationPayload($email, $code))
            ->assertCreated();
    }

    public function test_registration_emails_system_admins(): void
    {
        $email = 'newadmin@example.com';
        $code = $this->requestRegistrationCode($email);

        $this->postJson('/api/auth/register-organization', $this->registrationPayload($email, $code))
            ->assertCreated();

        Mail::assertSent(NewOrganizationRegistration::class, function (NewOrganizationRegistration $mail) {
            return $mail->hasTo('system@smartattend.co.tz')
                && $mail->organization->name === 'New Medical Centre';
        });

        Mail::assertSent(OrganizationRegistrationReceived::class, function (OrganizationRegistrationReceived $mail) {
            return $mail->hasTo('newadmin@example.com')
                && $mail->organization->name === 'New Medical Centre';
        });
    }

    public function test_registration_verification_emails_are_limited_to_two_per_day(): void
    {
        $payload = ['email' => 'newadmin@example.com', 'name' => 'New Admin'];

        $this->postJson('/api/auth/register-organization/send-code', $payload)->assertOk();
        $this->postJson('/api/auth/register-organization/send-code', $payload)->assertOk();
        $this->postJson('/api/auth/register-organization/send-code', $payload)->assertStatus(429);

        Mail::assertSent(OneTimePasscode::class, 2);
    }

    public function test_approval_emails_organization_admins(): void
    {
        Sanctum::actingAs($this->systemAdmin);

        $this->postJson("/api/system/organizations/{$this->organization->id}/approve")
            ->assertOk();

        Mail::assertSent(OrganizationApproved::class, fn (OrganizationApproved $mail) => $mail->hasTo('admin@apex.tz'));
    }

    public function test_rejection_emails_organization_admins_with_reason(): void
    {
        Sanctum::actingAs($this->systemAdmin);

        $this->postJson("/api/system/organizations/{$this->organization->id}/reject", [
            'reason' => 'TIN document was unreadable.',
        ])->assertOk();

        Mail::assertSent(OrganizationRejected::class, function (OrganizationRejected $mail) {
            return $mail->hasTo('admin@apex.tz')
                && $mail->reason === 'TIN document was unreadable.';
        });
    }

    public function test_password_reset_code_can_be_used_once(): void
    {
        $code = null;

        $this->postJson('/api/auth/forgot-password', ['email' => $this->orgAdmin->email])
            ->assertOk();

        Mail::assertSent(PasswordResetCode::class, function (PasswordResetCode $mail) use (&$code) {
            $code = $mail->code;

            return $mail->hasTo('admin@apex.tz');
        });

        $this->assertNotNull($code);

        $this->postJson('/api/auth/reset-password', [
            'email' => $this->orgAdmin->email,
            'code' => $code,
            'password' => 'NewPassword@456',
            'password_confirmation' => 'NewPassword@456',
        ])->assertOk();

        $this->assertTrue(Hash::check('NewPassword@456', $this->orgAdmin->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $this->orgAdmin->email]);
    }

    public function test_password_reset_emails_are_limited_to_two_per_day(): void
    {
        $payload = ['email' => $this->orgAdmin->email];

        $this->postJson('/api/auth/forgot-password', $payload)->assertOk();
        $this->postJson('/api/auth/forgot-password', $payload)->assertOk();
        $this->postJson('/api/auth/forgot-password', $payload)->assertStatus(429);

        Mail::assertSent(PasswordResetCode::class, 2);
    }

    public function test_expired_password_reset_code_is_rejected(): void
    {
        DB::table('password_reset_tokens')->insert([
            'email' => $this->orgAdmin->email,
            'token' => Hash::make('123456'),
            'created_at' => now()->subMinutes(30),
        ]);

        $this->postJson('/api/auth/reset-password', [
            'email' => $this->orgAdmin->email,
            'code' => '123456',
            'password' => 'NewPassword@456',
            'password_confirmation' => 'NewPassword@456',
        ])->assertStatus(422);

        $this->assertTrue(Hash::check('password123', $this->orgAdmin->fresh()->password));
    }

    public function test_email_templates_render(): void
    {
        $this->organization->forceFill(['status' => 'active'])->save();

        $payment = Payment::create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->orgAdmin->id,
            'plan' => 'starter',
            'billing_cycle' => 'monthly',
            'amount' => 45000,
            'original_amount' => 45000,
            'discount_amount' => 0,
            'currency' => 'TZS',
            'mobile_provider' => 'mpesa',
            'phone_number' => '0754123456',
            'reference' => 'SA-PAY-RENDER-01',
            'status' => Payment::STATUS_COMPLETED,
            'paid_at' => now(),
        ]);

        $this->assertStringContainsString('New registration request', (new NewOrganizationRegistration($this->organization, $this->orgAdmin))->render());
        $this->assertStringContainsString('approved', (new OrganizationApproved($this->organization))->render());
        $this->assertStringContainsString('unreadable', (new OrganizationRejected($this->organization, 'TIN was unreadable.'))->render());
        $this->assertStringContainsString('123456', (new PasswordResetCode($this->orgAdmin, '123456'))->render());
        $this->assertStringContainsString('SA-PAY-RENDER-01', (new PaymentReceipt($payment))->render());
        $this->assertStringContainsString('654321', (new OneTimePasscode('654321', $this->orgAdmin->name, 'sign-in verification'))->render());
        $this->assertStringContainsString('System maintenance', (new Announcement('System maintenance', 'We will be offline tonight for upgrades.'))->render());
        $this->assertStringContainsString('Payment received', (new NotificationMessage('Payment received', 'Your payment was received.', $this->orgAdmin->name, 'Billing'))->render());
        $this->assertStringContainsString('SAVE20', (new PromotionMessage('20% off upgrades', 'Upgrade today and save.', 'SAVE20', '20% off on your package', '31 Dec 2026'))->render());
        $this->assertStringContainsString('Registration request received', (new OrganizationRegistrationReceived($this->organization, $this->orgAdmin))->render());
        $this->assertStringContainsString('administrator account is ready', (new SystemAdminWelcome($this->systemAdmin, 'TempPass123', 'Root Admin'))->render());
    }

    public function test_emails_do_not_expose_portal_or_backend_urls(): void
    {
        $mailables = [
            new NewOrganizationRegistration($this->organization, $this->orgAdmin),
            new OrganizationRegistrationReceived($this->organization, $this->orgAdmin),
            new OrganizationApproved($this->organization),
            new OrganizationRejected($this->organization, 'Missing document'),
            new PasswordResetCode($this->orgAdmin, '123456'),
            new OneTimePasscode('654321', $this->orgAdmin->name, 'sign-in verification'),
            new Announcement('Maintenance', 'We will be offline tonight.'),
            new NotificationMessage('Payment received', 'Thank you.'),
            new PromotionMessage('20% off', 'Save today.', 'SAVE20', '20% off on your package'),
            new SystemAdminWelcome($this->systemAdmin, 'TempPass123', 'Root Admin'),
        ];

        foreach ($mailables as $mailable) {
            $html = $mailable->render();

            $this->assertStringNotContainsString(config('app.url'), $html, $mailable::class.' exposed the backend URL.');
            $this->assertStringNotContainsString('/portal', $html, $mailable::class.' exposed the portal path.');
            $this->assertStringNotContainsString('smartattend.org', $html);
            $this->assertStringNotContainsString('smartattend.og', $html);
            $this->assertStringContainsString('mailto:help@smartattend.com', $html);
        }
    }

    public function test_system_broadcast_can_also_email_an_announcement(): void
    {
        Sanctum::actingAs($this->systemAdmin);

        $this->postJson('/api/system/notifications/broadcast', [
            'title' => 'Scheduled maintenance',
            'body' => 'SmartAttend will be offline tonight from 22:00 to 23:00.',
            'role' => 'org_admin',
            'send_email' => true,
        ])->assertOk()
            ->assertJsonPath('email_sent', 1);

        Mail::assertSent(Announcement::class, function (Announcement $mail) {
            return $mail->title === 'Scheduled maintenance'
                && $mail->actionUrl === null
                && collect($mail->bcc)->contains(fn ($address) => ($address['address'] ?? null) === 'admin@apex.tz');
        });
    }

    public function test_system_broadcast_can_attach_a_promotion_to_the_email(): void
    {
        $promo = Promo::create([
            'code' => 'SAVE20',
            'title' => '20% off upgrades',
            'type' => 'discount_percent',
            'value' => 20,
            'active' => true,
            'ends_at' => now()->addMonth(),
        ]);

        Sanctum::actingAs($this->systemAdmin);

        $this->postJson('/api/system/notifications/broadcast', [
            'title' => 'Upgrade and save',
            'body' => 'Upgrade today and save on your next subscription.',
            'role' => 'org_admin',
            'send_email' => true,
            'promo_id' => $promo->id,
        ])->assertOk();

        Mail::assertSent(PromotionMessage::class, function (PromotionMessage $mail) {
            return $mail->code === 'SAVE20'
                && $mail->offerLabel === '20% off on your package'
                && $mail->actionUrl === null;
        });
    }

    public function test_payment_receipt_is_emailed_only_once(): void
    {
        $this->organization->forceFill(['status' => 'active'])->save();

        $payment = Payment::create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->orgAdmin->id,
            'plan' => 'starter',
            'billing_cycle' => 'annual',
            'amount' => 432000,
            'original_amount' => 432000,
            'discount_amount' => 0,
            'currency' => 'TZS',
            'mobile_provider' => 'airtelmoney',
            'phone_number' => '0688123456',
            'reference' => 'SA-PAY-EMAIL-01',
            'status' => Payment::STATUS_PENDING,
        ]);

        Sanctum::actingAs($this->orgAdmin);

        $this->postJson("/api/admin/subscription/payments/{$payment->id}/simulate")
            ->assertOk();

        Mail::assertSent(PaymentReceipt::class, fn (PaymentReceipt $mail) => $mail->hasTo('admin@apex.tz'));

        $this->postJson("/api/admin/subscription/payments/{$payment->id}/simulate")
            ->assertOk();

        Mail::assertSent(PaymentReceipt::class, 1);
    }
}
