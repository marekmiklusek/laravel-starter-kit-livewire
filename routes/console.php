<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('db-backup:run')
    ->dailyAt('01:00')
    ->runInBackground();
