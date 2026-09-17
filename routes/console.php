<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Schedule::command('queue:work --stop-when-empty --timeout=120 --max-time=300')
    ->everyMinute()
    ->withoutOverlapping(10)
    ->runInBackground();

Schedule::call(function (): void {
    DB::table('cache')->where('expiration', '<', now()->getTimestamp())->delete();
})->daily()->name('prune-expired-cache');

Schedule::command('queue:prune-failed', ['--hours' => 168])->daily();

Schedule::command('queue:prune-batches', ['--hours' => 168])->daily();

Schedule::command('db-backup:run')
    ->dailyAt('01:00')
    ->runInBackground();
