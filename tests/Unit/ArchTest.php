<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Mockery\LegacyMockInterface;
use PHPUnit\Framework\MockObject\MockObject;

arch()->preset()->php();
arch()->preset()->strict();
arch()->preset()->security();
arch()->preset()->laravel();

arch('controllers')
    ->expect('App\Http\Controllers')
    ->not->toBeUsed();

arch('commands')
    ->expect('App\Console\Commands')
    ->not->toBeUsed();

arch('actions')
    ->expect('App\Actions')
    ->classes()
    ->not->toHaveSuffix('Action');

arch('actions have an execute method')
    ->expect('App\Actions')
    ->toHaveMethod('execute')
    ->ignoring('App\Actions\Fortify');

arch('actions expose no other public method')
    ->expect('App\Actions')
    ->not->toHavePublicMethodsBesides(['execute', '__construct'])
    ->ignoring('App\Actions\Fortify');

arch('actions are readonly')
    ->expect('App\Actions')
    ->toBeReadonly()
    ->ignoring('App\Actions\Fortify');

arch('queries')
    ->expect('App\Queries')
    ->toBeReadonly()
    ->toHaveSuffix('Query')
    ->toHaveMethod('get');

arch('queries expose no other public method')
    ->expect('App\Queries')
    ->not->toHavePublicMethodsBesides(['get', '__construct']);

arch('no mocking')
    ->expect('App')
    ->not->toUse([
        LegacyMockInterface::class,
        MockInterface::class,
        MockObject::class,
    ]);
