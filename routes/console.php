<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('payments:expire-abandoned')->hourly();
Schedule::command('shop:send-recovery-emails')->everyFifteenMinutes();
Schedule::command('shop:send-review-requests')->daily();
Schedule::command('shop:send-weekly-digest')->weeklyOn(1, '8:00');
