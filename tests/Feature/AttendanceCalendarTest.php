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

class AttendanceCalendarTest extends TestCase
{
    use RefreshDatabase;

    private function organization(array $calendar = []): Organization
    {
        $organization = Organization::create([
            'name' => 'Calendar Test', 'status' => 'active', 'subscription_status' => 'active',
        ]);
        $organization->attendance_calendar = $calendar;
        $organization->save();

        return $organization;
    }

    private function person(Organization $organization, string $role = 'employee'): User
    {
        $branch = Branch::create(['org_id' => $organization->id, 'name' => 'Main', 'lat' => 0, 'lng' => 0, 'radius_meters' => 100, 'active' => true]);

        return User::factory()->create([
            'org_id' => $organization->id, 'branch_id' => $branch->id, 'role' => $role,
            'active' => true, 'must_change_password' => false,
        ]);
    }

    public function test_admin_can_save_only_their_organization_calendar(): void
    {
        $organization = $this->organization();
        $other = $this->organization();
        Sanctum::actingAs($this->person($organization, 'admin'));
        $calendar = ['weekend_days' => [0, 6], 'holidays' => [['date' => '2026-12-25', 'name' => 'Holiday', 'kind' => 'holiday']]];

        $this->putJson('/api/admin/attendance-calendar', [...$calendar, 'org_id' => $other->id])
            ->assertOk()->assertJsonPath('calendar', $calendar);

        $this->assertSame($calendar, $organization->fresh()->attendance_calendar);
        $this->assertSame([], $other->fresh()->attendance_calendar);
    }

    public function test_employees_cannot_change_the_calendar(): void
    {
        $organization = $this->organization();
        Sanctum::actingAs($this->person($organization));

        $this->putJson('/api/admin/attendance-calendar', ['weekend_days' => [0], 'holidays' => []])->assertForbidden();

        $this->assertSame([], $organization->fresh()->attendance_calendar);
    }

    public function test_calendar_requires_authentication(): void
    {
        $this->getJson('/api/attendance/calendar')->assertUnauthorized();
    }

    public function test_invalid_calendar_is_not_saved(): void
    {
        $organization = $this->organization();
        Sanctum::actingAs($this->person($organization, 'admin'));

        $this->putJson('/api/admin/attendance-calendar', [
            'weekend_days' => [7], 'holidays' => [['date' => 'not-a-date', 'name' => '', 'kind' => 'workday']],
        ])->assertUnprocessable()->assertJsonValidationErrors(['weekend_days.0', 'holidays.0.date', 'holidays.0.name', 'holidays.0.kind']);

        $this->assertSame([], $organization->fresh()->attendance_calendar);
    }

    public function test_duplicate_holiday_dates_are_rejected(): void
    {
        $organization = $this->organization();
        Sanctum::actingAs($this->person($organization, 'admin'));
        $holiday = ['date' => '2026-12-25', 'name' => 'Holiday', 'kind' => 'holiday'];

        $this->putJson('/api/admin/attendance-calendar', ['weekend_days' => [], 'holidays' => [$holiday, $holiday]])
            ->assertUnprocessable()->assertJsonValidationErrors(['holidays.0.date']);
    }

    public static function closedDayCases(): array
    {
        return [
            'weekend check-in' => [[0, 6], [], false],
            'weekend check-out' => [[0, 6], [], true],
            'holiday check-in' => [[], [['date' => '2026-11-21', 'name' => 'Office holiday', 'kind' => 'holiday']], false],
            'holiday check-out' => [[], [['date' => '2026-11-21', 'name' => 'Office holiday', 'kind' => 'holiday']], true],
            'dated weekend' => [[], [['date' => '2026-11-21', 'name' => 'Extra weekend', 'kind' => 'weekend']], false],
        ];
    }

    #[DataProvider('closedDayCases')]
    public function test_checkin_and_checkout_are_blocked_on_closed_days(array $weekends, array $holidays, bool $checkedIn): void
    {
        $this->travelTo(Carbon::parse('2026-11-21 10:00:00', 'Africa/Dar_es_Salaam'));
        $organization = $this->organization(['weekend_days' => $weekends, 'holidays' => $holidays]);
        $employee = $this->person($organization);
        if ($checkedIn) {
            Attendance::create(['user_id' => $employee->id, 'branch_id' => $employee->branch_id, 'type' => 'in', 'lat' => 0, 'lng' => 0, 'occurred_at' => now()->subHour()]);
        }
        Sanctum::actingAs($employee);

        $this->postJson('/api/attendance/toggle', ['lat' => 0, 'lng' => 0])
            ->assertForbidden()->assertJsonPath('code', 'ATTENDANCE_DAY_CLOSED')->assertJsonPath('attendance_day.date', '2026-11-21');

        $this->assertDatabaseCount('attendances', $checkedIn ? 1 : 0);
    }

    public function test_workday_attendance_still_succeeds(): void
    {
        $this->travelTo(Carbon::parse('2026-11-23 10:00:00', 'Africa/Dar_es_Salaam'));
        $organization = $this->organization(['weekend_days' => [0, 6], 'holidays' => []]);
        $employee = $this->person($organization);
        Sanctum::actingAs($employee);

        $this->postJson('/api/attendance/toggle', ['lat' => 0, 'lng' => 0])->assertOk()->assertJsonPath('is_checked_in', true);

        $this->assertDatabaseHas('attendances', ['user_id' => $employee->id, 'type' => 'in']);
    }

    public function test_calendar_uses_organization_timezone_at_midnight(): void
    {
        $this->travelTo(Carbon::parse('2026-11-20T21:01:00Z'));
        $organization = $this->organization(['weekend_days' => [6], 'holidays' => []]);
        Sanctum::actingAs($this->person($organization));

        $this->getJson('/api/attendance/calendar')->assertOk()
            ->assertJsonPath('today.date', '2026-11-21')->assertJsonPath('today.blocked', true);
    }

    public function test_sync_rejects_closed_day_records_before_writing_any_records(): void
    {
        $this->travelTo(Carbon::parse('2026-11-23T10:00:00Z'));
        $organization = $this->organization(['weekend_days' => [6], 'holidays' => []]);
        Sanctum::actingAs($this->person($organization));

        $this->postJson('/api/attendance/sync', ['records' => [
            ['type' => 'in', 'lat' => 0, 'lng' => 0, 'occurred_at' => '2026-11-20T10:00:00Z'],
            ['type' => 'out', 'lat' => 0, 'lng' => 0, 'occurred_at' => '2026-11-20T21:01:00Z'],
        ]])->assertUnprocessable()->assertJsonPath('code', 'ATTENDANCE_DAY_CLOSED')->assertJsonPath('rejected.0.index', 1);

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_previous_workday_record_can_sync_during_a_weekend(): void
    {
        $this->travelTo(Carbon::parse('2026-11-21T10:00:00Z'));
        $organization = $this->organization(['weekend_days' => [6], 'holidays' => []]);
        Sanctum::actingAs($this->person($organization));

        $this->postJson('/api/attendance/sync', ['records' => [
            ['type' => 'in', 'lat' => 0, 'lng' => 0, 'occurred_at' => '2026-11-20T10:00:00Z'],
        ]])->assertOk();

        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_requested_holiday_timestamp_cannot_bypass_calendar_check_on_workday(): void
    {
        $this->travelTo(Carbon::parse('2026-11-23T10:00:00Z'));
        $organization = $this->organization(['weekend_days' => [6], 'holidays' => []]);
        Sanctum::actingAs($this->person($organization));

        $this->postJson('/api/attendance/toggle', ['lat' => 0, 'lng' => 0, 'occurred_at' => '2026-11-21T10:00:00Z'])
            ->assertForbidden()->assertJsonPath('code', 'ATTENDANCE_DAY_CLOSED');

        $this->assertDatabaseCount('attendances', 0);
    }
}
