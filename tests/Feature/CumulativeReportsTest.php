<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CumulativeReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_cumulative_reports_requires_admin_authentication(): void
    {
        $response = $this->getJson('/api/admin/reports/cumulative');
        $response->assertStatus(401);
    }

    public function test_admin_can_fetch_cumulative_reports(): void
    {
        $org = Organization::create([
            'name' => 'Acme Corp',
            'contact_email' => 'admin@acme.com',
            'status' => 'active',
            'subscription_status' => 'active',
        ]);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@acme.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'org_id' => $org->id,
            'active' => true,
        ]);

        $branch = Branch::create([
            'org_id' => $org->id,
            'name' => 'Main Branch',
            'lat' => -6.7924,
            'lng' => 39.2083,
            'radius_meters' => 100,
            'check_in_time' => '08:00',
            'check_out_time' => '17:00',
            'grace_period_minutes' => 15,
        ]);

        $employee = User::create([
            'name' => 'Jane Doe',
            'email' => 'jane@acme.com',
            'password' => bcrypt('password123'),
            'role' => 'employee',
            'org_id' => $org->id,
            'branch_id' => $branch->id,
            'employee_id' => 'EMP001',
            'active' => true,
        ]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson('/api/admin/reports/cumulative?period=month');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'period',
                'start_date',
                'end_date',
                'expected_work_days',
                'summary' => [
                    'total_employees',
                    'expected_work_days',
                    'avg_attendance_rate',
                    'avg_punctuality_rate',
                    'total_worked_minutes',
                    'total_present_instances',
                    'total_late_instances',
                    'total_absent_instances',
                    'total_leave_instances',
                ],
                'rows' => [
                    '*' => [
                        'id',
                        'name',
                        'employee_id',
                        'active',
                        'branch',
                        'branch_id',
                        'present_days',
                        'late_days',
                        'absent_days',
                        'leave_days',
                        'attended_days',
                        'expected_work_days',
                        'total_worked_minutes',
                        'attendance_rate',
                        'punctuality_rate',
                    ],
                ],
            ]);
    }
}
