<?php

declare(strict_types=1);

use App\Models\User;
use App\Actions\UpdateUserProfile;

it('updates the name and email and clears verification when the email changes', function (): void {
    $user = User::factory()->create();

    resolve(UpdateUserProfile::class)->execute($user, 'Bob', 'bob@example.com');

    $freshUser = $user->fresh();

    expect($freshUser?->name)->toBe('Bob')
        ->and($freshUser?->email)->toBe('bob@example.com')
        ->and($freshUser?->email_verified_at)->toBeNull();
});

it('keeps verification when the email is unchanged', function (): void {
    $user = User::factory()->create(['email' => 'same@example.com']);

    resolve(UpdateUserProfile::class)->execute($user, 'Changed', 'same@example.com');

    $freshUser = $user->fresh();

    expect($freshUser?->name)->toBe('Changed')
        ->and($freshUser?->email_verified_at)->not->toBeNull();
});
