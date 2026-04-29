<?php

declare(strict_types=1);

use App\Models\User;

test('to array', function (): void {
    $user = User::factory()->create()->refresh();

    expect(array_keys($user->toArray()))
        ->toBe([
            'id',
            'name',
            'email',
            'email_verified_at',
            'two_factor_confirmed_at',
            'created_at',
            'updated_at',
        ]);
});

test('initials', function (): void {
    $user = User::factory()->make(['name' => 'Jane Ann Doe']);

    expect($user->initials())->toBe('JA');
});

test('casts', function (): void {
    $user = new User();

    expect($user->casts())->toBe([
        'id' => 'string',
        'name' => 'string',
        'email' => 'string',
        'password' => 'hashed',
        'two_factor_secret' => 'encrypted',
        'two_factor_recovery_codes' => 'encrypted',
        'two_factor_confirmed_at' => 'datetime',
        'remember_token' => 'string',
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ]);
});
