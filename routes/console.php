<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('lyva:watchdog --notify')->everyFiveMinutes();
Schedule::command('lyva:finance-alert --notify')->hourly();
Schedule::command('lyva:background-remover:purge')->everyFiveMinutes()->withoutOverlapping();

$privateInstallmentReminderTime = trim((string) config('private_installment.reminder.time', '09:00'));
[$privateInstallmentReminderHour, $privateInstallmentReminderMinute] = array_pad(
    array_map('intval', explode(':', $privateInstallmentReminderTime, 2)),
    2,
    0,
);

if (config('private_installment.enabled', true) && config('private_installment.reminder.enabled', true)) {
    Schedule::command('lyva:private-installment:remind')
        ->weeklyOn(
            max(0, min(6, (int) config('private_installment.reminder.day_of_week', 1))),
            sprintf('%02d:%02d', $privateInstallmentReminderHour, $privateInstallmentReminderMinute),
        )
        ->withoutOverlapping();
}
