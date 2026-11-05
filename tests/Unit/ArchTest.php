<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\ValidationRule;

arch()->preset()->php();
arch()->preset()->laravel();
arch()->preset()->security();
arch()->preset()->strict()->ignoring('App\Models');

arch('controllers')
    ->expect('App\Http\Controllers')
    ->not->toBeUsed();

arch('commands')
    ->expect('App\Console\Commands')
    ->not->toBeUsed();

arch('controllers are cruddy by design and expose only resource methods')
    ->expect('App\Http\Controllers')
    ->not->toHavePublicMethodsBesides([
        '__construct',
        '__invoke',
        'index',
        'create',
        'store',
        'show',
        'edit',
        'update',
        'destroy',
    ]);

arch('controllers delegate validation and authorization to form requests')
    ->expect('App\Http\Controllers')
    ->not->toUse([
        'abort',
        'abort_if',
        'abort_unless',
        'throw_if',
        'throw_unless',
        'validator',
        Validator::class,
        ValidationException::class,
        Gate::class,
    ]);

arch('models follow the strict preset but may declare protected scopes')
    ->expect('App\Models')
    ->toUseStrictTypes()
    ->toUseStrictEquality()
    ->classes()
    ->toBeFinal()
    ->not->toBeAbstract();

arch('actions delegate validation and authorization to form requests')
    ->expect('App\Actions')
    ->not->toUse([
        'abort',
        'abort_if',
        'abort_unless',
        'validator',
        Validator::class,
        ValidationException::class,
        Gate::class,
    ])
    ->ignoring('App\Actions\Fortify');

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

arch('dtos are readonly')
    ->expect('App\Dtos')
    ->toBeReadonly();

arch('rules')
    ->expect('App\Rules')
    ->classes()
    ->toHaveSuffix('Rule')
    ->toImplement(ValidationRule::class);
