<?php

namespace App\Http\Controllers\Api;

use App\Services\AttendanceCalendar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceCalendarController extends Controller
{
    public function __construct(private readonly AttendanceCalendar $calendar) {}

    public function show(Request $request): JsonResponse
    {
        $organization = $request->user()->organization;
        abort_unless($organization, 403, 'No organization is assigned to your account.');

        return response()->json([
            'calendar' => $this->calendar->settings($organization),
            'today' => $this->calendar->employeeDay($request->user()),
            'attendance_days' => $request->user()->attendance_days,
            'timezone' => AttendanceCalendar::TIMEZONE,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'weekend_days' => ['present', 'array', 'max:7'],
            'weekend_days.*' => ['required', 'integer', 'between:0,6', 'distinct'],
            'holidays' => ['present', 'array', 'max:400'],
            'holidays.*.date' => ['required', 'date_format:Y-m-d', 'distinct'],
            'holidays.*.name' => ['required', 'string', 'max:100'],
            'holidays.*.kind' => ['required', 'in:holiday,weekend'],
        ]);
        $settings = [
            'weekend_days' => array_values(array_map('intval', $data['weekend_days'])),
            'holidays' => collect($data['holidays'])->map(fn (array $day): array => [
                'date' => $day['date'], 'name' => trim($day['name']), 'kind' => $day['kind'],
            ])->sortBy('date')->values()->all(),
        ];
        $organization = $request->user()->organization;
        $organization->attendance_calendar = $settings;
        $organization->save();

        return $this->show($request);
    }
}
