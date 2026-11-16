<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\PendingCommand;

it('creates a verified user with a generated password and shows it', function (): void {
    Str::createRandomStringsUsing(fn (): string => 'generated-password');

    /** @var PendingCommand $command */
    $command = $this->artisan('user:create');

    $command->expectsQuestion('Name', 'Test User')
        ->expectsQuestion('Email', 'test@example.com')
        ->expectsOutputToContain('User [test@example.com] created successfully.')
        ->expectsOutputToContain('generated-password')
        ->assertSuccessful();

    $command->run();

    $user = User::query()->sole();

    expect($user->email)->toBe('test@example.com')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and(Hash::check('generated-password', $user->password))->toBeTrue();
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
