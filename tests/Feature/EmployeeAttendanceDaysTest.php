<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EmployeeAttendanceDaysTest extends TestCase
{
    use RefreshDatabase;

    private function organization(array $calendar = []): Organization
    {
        $organization = Organization::create(['name' => 'Day Assignments', 'status' => 'active', 'subscription_status' => 'active']);
        $organization->attendance_calendar = $calendar;
        $organization->save();

        return $organization;
    }

    private function person(Organization $organization, string $role = 'employee', ?array $days = null): User
    {
        $branch = Branch::create(['org_id' => $organization->id, 'name' => 'Main', 'lat' => 0, 'lng' => 0, 'radius_meters' => 100, 'active' => true]);

        return User::factory()->create([
            'org_id' => $organization->id, 'branch_id' => $branch->id, 'role' => $role,
            'attendance_days' => $days, 'active' => true, 'must_change_password' => false,
        ]);
    }

    public function test_admin_can_assign_days_when_adding_an_employee(): void
    {
        $organization = $this->organization();
        Sanctum::actingAs($this->person($organization, 'admin'));

        $this->postJson('/api/admin/employees', ['name' => 'Monday Wednesday Friday', 'attendance_days' => [1, 3, 5]])
            ->assertCreated()->assertJsonPath('employee.attendance_days', [1, 3, 5]);

        $employee = $organization->users()->where('role', 'employee')->sole();
        $this->assertSame([1, 3, 5], $employee->attendance_days);
    }

    public function test_admin_can_edit_existing_employee_days(): void
    {
        $organization = $this->organization();
        $employee = $this->person($organization, 'employee', [1]);
        Sanctum::actingAs($this->person($organization, 'admin'));

        $this->patchJson("/api/admin/employees/{$employee->id}/attendance-days", ['attendance_days' => [5, 2]])
            ->assertOk()->assertJsonPath('employee.attendance_days', [2, 5]);

        $this->assertSame([2, 5], $employee->fresh()->attendance_days);
    }

    public function test_another_organization_cannot_change_employee_days(): void
    {
        $employee = $this->person($this->organization(), 'employee', [1]);
        Sanctum::actingAs($this->person($this->organization(), 'admin'));

        $this->patchJson("/api/admin/employees/{$employee->id}/attendance-days", ['attendance_days' => [2]])->assertNotFound();

        $this->assertSame([1], $employee->fresh()->attendance_days);
    }

    public function test_employee_cannot_change_own_assignments(): void
    {
        $employee = $this->person($this->organization(), 'employee', [1]);
        Sanctum::actingAs($employee);

        $this->patchJson("/api/admin/employees/{$employee->id}/attendance-days", ['attendance_days' => [2]])->assertForbidden();

        $this->assertSame([1], $employee->fresh()->attendance_days);
    }

    public function test_profile_update_does_not_override_assigned_days(): void
    {
        $employee = $this->person($this->organization(), 'employee', [1]);
        Sanctum::actingAs($employee);

        $this->putJson('/api/me', ['name' => $employee->name, 'attendance_days' => [2]])->assertOk();

        $this->assertSame([1], $employee->fresh()->attendance_days);
    }

    public function test_schedule_edit_requires_authentication(): void
    {
        $employee = $this->person($this->organization());

        $this->patchJson("/api/admin/employees/{$employee->id}/attendance-days", ['attendance_days' => [1]])->assertUnauthorized();
    }

    public static function invalidAssignments(): array
    {
        return ['empty' => [[]], 'invalid day' => [[7]], 'duplicate' => [[1, 1]], 'text' => [['Monday']], 'null edit' => [null]];
    }

    #[DataProvider('invalidAssignments')]
    public function test_invalid_days_cannot_be_saved(?array $days): void
    {
        $organization = $this->organization();
        $employee = $this->person($organization, 'employee', [1]);
        Sanctum::actingAs($this->person($organization, 'admin'));

        $this->patchJson("/api/admin/employees/{$employee->id}/attendance-days", ['attendance_days' => $days])->assertUnprocessable();

        $this->assertSame([1], $employee->fresh()->attendance_days);
    }

    public function test_new_employee_without_a_schedule_keeps_existing_behavior(): void
    {
        Sanctum::actingAs($this->person($this->organization(), 'admin'));

        $this->postJson('/api/admin/employees', ['name' => 'All Working Days'])
            ->assertCreated()->assertJsonPath('employee.attendance_days', null);
    }

    public static function attendanceCases(): array
    {
        return [
            'assigned Monday' => ['2026-11-23T10:00:00Z', [1, 3, 5], false, true],
            'unassigned Tuesday check-in' => ['2026-11-24T10:00:00Z', [1, 3, 5], false, false],
            'unassigned Tuesday check-out' => ['2026-11-24T10:00:00Z', [1, 3, 5], true, false],
            'legacy employee without days' => ['2026-11-24T10:00:00Z', null, false, true],
            'organization timezone Tuesday' => ['2026-11-23T21:01:00Z', [1], false, false],
        ];
    }

    #[DataProvider('attendanceCases')]
    public function test_attendance_obeys_assigned_days(string $at, ?array $days, bool $checkedIn, bool $allowed): void
    {
        $this->travelTo(Carbon::parse($at));
        $employee = $this->person($this->organization(), 'employee', $days);
        if ($checkedIn) {
            Attendance::create(['user_id' => $employee->id, 'branch_id' => $employee->branch_id, 'type' => 'in', 'lat' => 0, 'lng' => 0, 'occurred_at' => now()->subHour()]);
        }
        Sanctum::actingAs($employee);

        $response = $this->postJson('/api/attendance/toggle', ['lat' => 0, 'lng' => 0]);

        if ($allowed) {
            $response->assertOk()->assertJsonPath('is_checked_in', true);
        } else {
            $response->assertForbidden()->assertJsonPath('attendance_day.kind', 'unassigned');
        }
        $this->assertDatabaseCount('attendances', $allowed || $checkedIn ? 1 : 0);
    }

    public function test_holiday_overrides_employee_assignment(): void
    {
        $this->travelTo(Carbon::parse('2026-11-23T10:00:00Z'));
        $organization = $this->organization(['weekend_days' => [], 'holidays' => [['date' => '2026-11-23', 'name' => 'Office closed', 'kind' => 'holiday']]]);
        Sanctum::actingAs($this->person($organization, 'employee', [1]));

        $this->postJson('/api/attendance/toggle', ['lat' => 0, 'lng' => 0])
            ->assertForbidden()->assertJsonPath('attendance_day.kind', 'holiday');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_offline_records_on_unassigned_days_are_rejected(): void
    {
        $this->travelTo(Carbon::parse('2026-11-25T10:00:00Z'));
        Sanctum::actingAs($this->person($this->organization(), 'employee', [1, 3, 5]));

        $this->postJson('/api/attendance/sync', ['records' => [
            ['type' => 'in', 'lat' => 0, 'lng' => 0, 'occurred_at' => '2026-11-24T10:00:00Z'],
        ]])->assertUnprocessable()->assertJsonPath('rejected.0.attendance_day.kind', 'unassigned');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_calendar_and_profile_expose_employee_assignments(): void
    {
        $this->travelTo(Carbon::parse('2026-11-24T10:00:00Z'));
        Sanctum::actingAs($this->person($this->organization(), 'employee', [1, 3, 5]));

        $this->getJson('/api/attendance/calendar')->assertOk()
            ->assertJsonPath('attendance_days', [1, 3, 5])->assertJsonPath('today.kind', 'unassigned');
        $this->getJson('/api/me')->assertOk()->assertJsonPath('user.attendance_days', [1, 3, 5]);
    }
}
