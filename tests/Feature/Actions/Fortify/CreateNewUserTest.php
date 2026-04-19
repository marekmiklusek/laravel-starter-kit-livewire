<?php

declare(strict_types=1);

use App\Models\User;
use App\Actions\Fortify\CreateNewUser;
use Illuminate\Validation\ValidationException;

it('creates a new user', function (): void {
    $user = new CreateNewUser()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
    ]);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('Test User')
        ->and($user->email)->toBe('test@example.com');
});

it('validates input when creating a user', function (): void {
    expect(fn (): User => new CreateNewUser()->create([
        'name' => '',
        'email' => 'not-an-email',
        'password' => 'short',
        'password_confirmation' => 'mismatch',
    ]))->toThrow(ValidationException::class);
});
