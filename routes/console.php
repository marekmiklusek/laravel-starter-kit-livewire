<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('playground', function (): void {
    // ...
});

Schedule::command('db-backup:run')
    ->dailyAt('02:00')
    ->onOneServer()
    ->runInBackground();
