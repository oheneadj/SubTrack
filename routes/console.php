<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─── Scheduled Tasks ─────────────────────────────────
// Cron entry (add in Hostinger hPanel → Cron Jobs):
// * * * * * cd /home/u197483685/domains/arnsoninnovate.com/public_html/subtrack && /usr/bin/php artisan schedule:run >> /dev/null 2>&1

// Process queued jobs (emails, notifications) — runs every minute, stops when empty
Schedule::command('queue:work --stop-when-empty --max-time=55')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Check subscription expiries and send reminders daily at 8 AM
Schedule::command('subtrack:check-expiries')->dailyAt('08:00');

// Housekeeping: prune old logs weekly
Schedule::command('log:clear')->weekly();
