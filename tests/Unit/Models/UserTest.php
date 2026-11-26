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
            'second_factor_enrolled_at',
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
        'second_factor_enrolled_at' => 'datetime',
        'remember_token' => 'string',
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ]);
});

test('passkeys', function (): void {
    $user = User::factory()->create();

    $user->passkeys()->create([
        'name' => 'MacBook',
        'credential_id' => 'credential-id',
        'credential' => [],
    ]);

    expect($user->passkeys()->pluck('name')->all())->toBe(['MacBook'])
        ->and($user->hasPasskeysEnabled())->toBeTrue();
});
