<?php

namespace Tests\Feature;

use App\Mail\NewOrganizationRegistration;
use App\Mail\OrganizationApproved;
use App\Mail\OrganizationRejected;
use App\Mail\PasswordResetCode;
use App\Mail\PaymentReceipt;
use App\Models\Organization;
use App\Models\Package;
use App\Models\Payment;
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

    public function test_registration_emails_system_admins(): void
    {
        $this->postJson('/api/auth/register-organization', [
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
                'email' => 'newadmin@example.com',
                'employee_id' => 'NMC-0001',
                'password' => 'Password@123',
                'phone' => '+255 713 111 222',
            ],
            'branches' => [
                ['name' => 'Main Office', 'lat' => -6.7924, 'lng' => 39.2083, 'radius_meters' => 150],
            ],
        ])->assertCreated();

        Mail::assertSent(NewOrganizationRegistration::class, function (NewOrganizationRegistration $mail) {
            return $mail->hasTo('system@smartattend.co.tz')
                && $mail->organization->name === 'New Medical Centre';
        });
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
