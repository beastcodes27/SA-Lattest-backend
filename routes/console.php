<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Commands
|--------------------------------------------------------------------------
| Run every hour to auto-expire unanswered leave requests and send
| 24-hour pre-expiry reminders to org admins.
*/
Schedule::command('leave:expire-pending')->hourly()->withoutOverlapping();
