<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Fortify\Features;
use App\Actions\Fortify\CreateNewUser;
use Illuminate\Validation\ValidationException;

it('creates a new user', function (): void {
    $user = new CreateNewUser()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
    ]);

    expect($user->name)->toBe('Test User')
        ->and($user->email)->toBe('test@example.com')
        ->and($user->exists)->toBeTrue();
});

it('validates input when creating a user', function (): void {
    expect(fn (): User => new CreateNewUser()->create([
        'name' => '',
        'email' => 'not-an-email',
        'password' => 'short',
        'password_confirmation' => 'mismatch',
    ]))->toThrow(ValidationException::class);
});

it('rejects an email that is already taken', function (): void {
    User::factory()->create(['email' => 'taken@example.com']);

    expect(fn (): User => new CreateNewUser()->create([
        'name' => 'Test User',
        'email' => 'taken@example.com',
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
    ]))->toThrow(ValidationException::class);
});

it('rejects a name longer than the maximum length', function (): void {
    expect(fn (): User => new CreateNewUser()->create([
        'name' => str_repeat('a', 256),
        'email' => 'long-name@example.com',
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
    ]))->toThrow(ValidationException::class);
});

it('rejects an email longer than the maximum length', function (): void {
    expect(fn (): User => new CreateNewUser()->create([
        'name' => 'Test User',
        'email' => str_repeat('a', 250).'@example.com',
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
    ]))->toThrow(ValidationException::class);
});

it('refuses to create a user when registration is disabled', function (): void {
    config()->set('fortify.features', array_values(array_filter(
        config()->array('fortify.features'),
        fn (mixed $feature): bool => $feature !== Features::registration(),
    )));

    expect(fn (): User => new CreateNewUser()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
    ]))->toThrow(RuntimeException::class, 'Registration is disabled.')
        ->and(User::query()->where('email', 'test@example.com')->exists())->toBeFalse();
});
