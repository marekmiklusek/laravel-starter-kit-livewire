<?php

declare(strict_types=1);

use App\Actions\CreateUser;
use Illuminate\Support\Facades\Hash;

it('creates a verified user with a hashed password', function (): void {
    $user = resolve(CreateUser::class)->execute('Test User', 'test@example.com', 'Password123');

    expect($user->exists)->toBeTrue()
        ->and($user->name)->toBe('Test User')
        ->and($user->email)->toBe('test@example.com')
        ->and($user->email_verified_at?->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and(Hash::check('Password123', $user->password))->toBeTrue();
});
