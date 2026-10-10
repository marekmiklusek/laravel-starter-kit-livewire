<?php

declare(strict_types=1);

use App\Models\User;
use App\Actions\CreateUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

it('creates a verified user with a hashed password', function (): void {
    $user = resolve(CreateUser::class)->execute([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
    ]);

    expect($user->exists)->toBeTrue()
        ->and($user->name)->toBe('Test User')
        ->and($user->email)->toBe('test@example.com')
        ->and($user->email_verified_at?->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and(Hash::check('Password123', $user->password))->toBeTrue();
});

it('rejects invalid input', function (array $input): void {
    /** @var array<string, string> $payload */
    $payload = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
        ...$input,
    ];

    expect(fn (): User => resolve(CreateUser::class)->execute($payload))->toThrow(ValidationException::class)
        ->and(User::query()->count())->toBe(0);
})->with([
    'empty name' => [['name' => '']],
    'too long name' => [['name' => str_repeat('a', 256)]],
    'invalid email' => [['email' => 'not-an-email']],
    'too long email' => [['email' => str_repeat('a', 250).'@example.com']],
    'short password' => [['password' => 'short', 'password_confirmation' => 'short']],
    'password mismatch' => [['password_confirmation' => 'Mismatch123']],
]);

it('rejects an email that is already taken', function (): void {
    User::factory()->create(['email' => 'taken@example.com']);

    expect(fn (): User => resolve(CreateUser::class)->execute([
        'name' => 'Test User',
        'email' => 'taken@example.com',
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
    ]))->toThrow(ValidationException::class)
        ->and(User::query()->count())->toBe(1);
});
