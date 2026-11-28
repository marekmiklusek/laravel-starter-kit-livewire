<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Fortify\Features;
use App\Queries\RequiresSecondFactorEnrollmentQuery;

it('requires enrollment from users without any second factor', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    expect(resolve(RequiresSecondFactorEnrollmentQuery::class)->get($user))->toBeTrue();
});

it('does not require enrollment from users with confirmed two factor', function (): void {
    $user = User::factory()->create();

    expect(resolve(RequiresSecondFactorEnrollmentQuery::class)->get($user))->toBeFalse();
});

it('does not require enrollment from users with a passkey', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $user->passkeys()->create([
        'name' => 'MacBook',
        'credential_id' => 'credential-id',
        'credential' => [],
    ]);

    expect(resolve(RequiresSecondFactorEnrollmentQuery::class)->get($user))->toBeFalse();
});

it('ignores two factor when the feature is disabled', function (): void {
    config()->set('fortify.features', [
        Features::passkeys(['confirmPassword' => true]),
    ]);

    $user = User::factory()->create();

    expect(resolve(RequiresSecondFactorEnrollmentQuery::class)->get($user))->toBeTrue();
});

it('ignores passkeys when the feature is disabled', function (): void {
    config()->set('fortify.features', [
        Features::twoFactorAuthentication(['confirm' => true, 'confirmPassword' => true]),
    ]);

    $user = User::factory()->withoutTwoFactor()->create();

    $user->passkeys()->create([
        'name' => 'MacBook',
        'credential_id' => 'credential-id',
        'credential' => [],
    ]);

    expect(resolve(RequiresSecondFactorEnrollmentQuery::class)->get($user))->toBeTrue();
});

it('does not require enrollment when no second factor feature is enabled', function (): void {
    config()->set('fortify.features', []);

    $user = User::factory()->withoutTwoFactor()->create();

    expect(resolve(RequiresSecondFactorEnrollmentQuery::class)->get($user))->toBeFalse();
});

it('does not require enrollment when it is turned off', function (): void {
    config()->set('fortify.second_factor_required', false);

    $user = User::factory()->withoutTwoFactor()->create();

    expect(resolve(RequiresSecondFactorEnrollmentQuery::class)->get($user))->toBeFalse();
});
