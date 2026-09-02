<?php

declare(strict_types=1);

use Tests\TestCase;
use Illuminate\Support\Str;
use Illuminate\Support\Sleep;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->tia()->locally();

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        Str::createRandomStringsNormally();
        Str::createUuidsNormally();
        Http::preventStrayRequests();
        Process::preventStrayProcesses();
        Sleep::fake();

        Date::setTestNow(Date::now());
    })
    ->in('Browser', 'Feature', 'Unit');
