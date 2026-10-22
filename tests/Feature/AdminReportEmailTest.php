<?php

namespace Tests\Feature;

use App\Mail\AttendanceReportEmail;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminReportEmailTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $org = Organization::create([
            'name' => 'Apex Logistics Tanzania',
            'contact_email' => 'admin@apex.tz',
            'plan' => 'starter',
            'status' => 'active',
        ]);

        $org->startTrial(30);

        $this->admin = User::create([
            'name' => 'Org Admin',
            'email' => 'admin@apex.tz',
            'employee_id' => 'APX-0001',
            'password' => 'password123',
            'role' => 'admin',
            'org_id' => $org->id,
            'active' => true,
        ]);
    }

    public function test_org_admin_can_email_an_attendance_report(): void
    {
        Sanctum::actingAs($this->admin);

        $file = UploadedFile::fake()->create('daily-attendance.pdf', 120, 'application/pdf');

        $this->post('/api/admin/reports/email', [
            'report_type' => 'daily',
            'report_title' => 'Daily attendance report',
            'report_period' => '22 Oct 2026',
            'report' => $file,
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('email', 'admin@apex.tz');

        Mail::assertSent(AttendanceReportEmail::class, function (AttendanceReportEmail $mail) {
            return $mail->hasTo('admin@apex.tz')
                && $mail->reportTitle === 'Daily attendance report'
                && $mail->filename === 'daily-attendance.pdf';
        });
    }

    public function test_org_admin_can_email_the_report_to_another_address(): void
    {
        Sanctum::actingAs($this->admin);

        $file = UploadedFile::fake()->create('report.pdf', 120, 'application/pdf');

        $this->post('/api/admin/reports/email', [
            'email' => 'manager@apex.tz',
            'report_type' => 'cumulative',
            'report_title' => 'Cumulative attendance report',
            'report_period' => 'October 2026',
            'report' => $file,
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('email', 'manager@apex.tz');

        Mail::assertSent(AttendanceReportEmail::class, fn (AttendanceReportEmail $mail) => $mail->hasTo('manager@apex.tz'));
    }

    public function test_report_email_requires_a_pdf_attachment(): void
    {
        Sanctum::actingAs($this->admin);

        $this->post('/api/admin/reports/email', [
            'report_type' => 'daily',
            'report_title' => 'Daily attendance report',
            'report_period' => '22 Oct 2026',
        ], ['Accept' => 'application/json'])->assertStatus(422);
    }
}
