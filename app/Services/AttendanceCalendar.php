<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class AttendanceCalendar
{
    public const TIMEZONE = 'Africa/Dar_es_Salaam';

    /** @return array{weekend_days: array, holidays: array} */
    public function settings(Organization $organization): array
    {
        $settings = $organization->attendance_calendar ?? [];

        return [
            'weekend_days' => $settings['weekend_days'] ?? [],
            'holidays' => $settings['holidays'] ?? [],
        ];
    }

    /** @return array{date: string, blocked: bool, kind: ?string, name: ?string, message: ?string} */
    public function day(Organization $organization, ?CarbonInterface $at = null): array
    {
        $date = $at ? Carbon::parse($at->toIso8601String())->setTimezone(self::TIMEZONE) : Carbon::now(self::TIMEZONE);
        $key = $date->toDateString();
        $settings = $this->settings($organization);
        foreach ($settings['holidays'] as $holiday) {
            if ($holiday['date'] === $key) {
                $kind = $holiday['kind'] ?? 'holiday';
                $name = $holiday['name'] ?: ucfirst($kind);

                return ['date' => $key, 'blocked' => true, 'kind' => $kind, 'name' => $name,
                    'message' => "Check-in and check-out are disabled on {$key}: {$name}."];
            }
        }
        if (in_array($date->dayOfWeek, $settings['weekend_days'], true)) {
            return ['date' => $key, 'blocked' => true, 'kind' => 'weekend', 'name' => 'Weekend',
                'message' => "Check-in and check-out are disabled on {$key}: organization weekend."];
        }

        return ['date' => $key, 'blocked' => false, 'kind' => null, 'name' => null, 'message' => null];
    }

    /** @return array{date: string, blocked: bool, kind: ?string, name: ?string, message: ?string} */
    public function employeeDay(User $employee, ?CarbonInterface $at = null): array
    {
        $day = $this->day($employee->organization, $at);
        if ($day['blocked'] || $employee->attendance_days === null) {
            return $day;
        }
        $weekday = Carbon::parse($day['date'], self::TIMEZONE)->dayOfWeek;
        if (! in_array($weekday, $employee->attendance_days, true)) {
            return [
                'date' => $day['date'], 'blocked' => true, 'kind' => 'unassigned', 'name' => 'Not scheduled today',
                'message' => "You are not assigned to attend on {$day['date']}. Check-in and check-out are disabled. Contact your administrator to change your attendance days.",
            ];
        }

        return $day;
    }
}
