<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Example scheduled tasks, visible on the "Scheduled tasks" debug page.
Schedule::command('inspire')->hourly();

// Keep the Telescope table from ballooning: retain only the last 48 hours.
Schedule::command('telescope:prune', ['--hours' => 48])->daily();

// Drop failed queue jobs older than a week.
Schedule::command('queue:prune-failed', ['--hours' => 168])->daily();
