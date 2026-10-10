<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Testing\PendingCommand;

it('creates a user from the prompted details', function (): void {
    /** @var PendingCommand $command */
    $command = $this->artisan('user:create');

    $command->expectsQuestion('Name', 'Test User')
        ->expectsQuestion('Email', 'test@example.com')
        ->expectsQuestion('Password', 'Password123')
        ->expectsQuestion('Confirm password', 'Password123')
        ->expectsOutputToContain('User [test@example.com] created successfully.')
        ->assertSuccessful();

    $command->run();

    expect(User::query()->where('email', 'test@example.com')->exists())->toBeTrue();
});

it('rejects a too long name right after it is entered', function (): void {
    /** @var PendingCommand $command */
    $command = $this->artisan('user:create');

    $command->expectsQuestion('Name', str_repeat('a', 256))
        ->expectsOutputToContain('The name field must not be greater than 255 characters.')
        ->assertFailed();

    $command->run();
});

it('rejects an invalid email right after it is entered', function (): void {
    /** @var PendingCommand $command */
    $command = $this->artisan('user:create');

    $command->expectsQuestion('Name', 'Test User')
        ->expectsQuestion('Email', 'not-an-email')
        ->expectsOutputToContain('The email field must be a valid email address.')
        ->assertFailed();

    $command->run();
});

it('rejects a taken email right after it is entered', function (): void {
    User::factory()->create(['email' => 'taken@example.com']);

    /** @var PendingCommand $command */
    $command = $this->artisan('user:create');

    $command->expectsQuestion('Name', 'Test User')
        ->expectsQuestion('Email', 'taken@example.com')
        ->expectsOutputToContain('The email has already been taken.')
        ->assertFailed();

    $command->run();

    expect(User::query()->count())->toBe(1);
});

it('rejects a weak password right after it is entered', function (): void {
    /** @var PendingCommand $command */
    $command = $this->artisan('user:create');

    $command->expectsQuestion('Name', 'Test User')
        ->expectsQuestion('Email', 'test@example.com')
        ->expectsQuestion('Password', 'short')
        ->expectsOutputToContain('The password field must be at least 8 characters.')
        ->assertFailed();

    $command->run();
});

it('rejects a mismatched password confirmation', function (): void {
    /** @var PendingCommand $command */
    $command = $this->artisan('user:create');

    $command->expectsQuestion('Name', 'Test User')
        ->expectsQuestion('Email', 'test@example.com')
        ->expectsQuestion('Password', 'Password123')
        ->expectsQuestion('Confirm password', 'Mismatch123')
        ->expectsOutputToContain('The password field confirmation does not match.')
        ->assertFailed();

    $command->run();

    expect(User::query()->count())->toBe(0);
});
