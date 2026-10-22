<?php

namespace Tests\Feature;

use App\Mail\EmployeeIdChanged;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrganizationPrefixUpdateTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->org = Organization::create([
            'name' => 'Apex Logistics Tanzania',
            'contact_email' => 'admin@apex.tz',
            'plan' => 'starter',
            'status' => 'active',
            'employee_id_prefix' => 'OLD',
        ]);

        $this->org->startTrial(30);

        $this->admin = User::create([
            'name' => 'Org Admin',
            'email' => 'admin@apex.tz',
            'employee_id' => 'ADM-0001',
            'password' => 'password123',
            'role' => 'admin',
            'org_id' => $this->org->id,
            'active' => true,
        ]);
    }

    private function employee(string $name, string $employeeId, string $email): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'employee_id' => $employeeId,
            'password' => 'password123',
            'role' => 'employee',
            'org_id' => $this->org->id,
            'active' => true,
        ]);
    }

    public function test_prefix_change_can_replace_existing_employee_ids_and_email_staff(): void
    {
        $first = $this->employee('First Employee', 'OLD-0001', 'first@apex.tz');
        $second = $this->employee('Second Employee', 'OLD-0005', 'second@apex.tz');

        // A soft-deleted ID under the new prefix must never be reused.
        $deleted = $this->employee('Deleted Employee', 'NEW-0001', 'deleted@apex.tz');
        $deleted->delete();

        Sanctum::actingAs($this->admin);

        $response = $this->patchJson('/api/admin/organization', [
            'employee_id_prefix' => 'NEW',
            'replace_employee_ids' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('employee_ids_replaced', 2)
            ->assertJsonPath('employees_notified', 2)
            ->assertJsonPath('org.employee_id_prefix', 'NEW');

        $this->assertSame('NEW-0002', $first->fresh()->employee_id);
        $this->assertSame('NEW-0003', $second->fresh()->employee_id);
        $this->assertSame('ADM-0001', $this->admin->fresh()->employee_id);
        $this->assertSame('NEW-0001', $deleted->fresh()->employee_id);

        Mail::assertSent(EmployeeIdChanged::class, function (EmployeeIdChanged $mail) {
            return $mail->hasTo('first@apex.tz')
                && $mail->oldEmployeeId === 'OLD-0001'
                && $mail->newEmployeeId === 'NEW-0002';
        });

        Mail::assertSent(EmployeeIdChanged::class, fn (EmployeeIdChanged $mail) => $mail->hasTo('second@apex.tz'));
    }

    public function test_prefix_change_keeps_existing_ids_when_replacement_is_not_requested(): void
    {
        $employee = $this->employee('First Employee', 'OLD-0001', 'first@apex.tz');

        Sanctum::actingAs($this->admin);

        $this->patchJson('/api/admin/organization', [
            'employee_id_prefix' => 'NEW',
        ])->assertOk()
            ->assertJsonPath('employee_ids_replaced', 0);

        $this->assertSame('OLD-0001', $employee->fresh()->employee_id);

        Mail::assertNotSent(EmployeeIdChanged::class);
    }
}
