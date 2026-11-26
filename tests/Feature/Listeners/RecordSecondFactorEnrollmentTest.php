<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Fortify\Features;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Events\PasskeyRegistered;
use Illuminate\Foundation\Auth\User as AuthUser;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;

it('marks enrollment when two factor is confirmed', function (): void {
    $user = User::factory()->withoutSecondFactorEnrollment()->create();

    event(new TwoFactorAuthenticationConfirmed($user));

    expect($user->refresh()->second_factor_enrolled_at)->not->toBeNull();
});

it('does not mark enrollment when two factor still awaits confirmation', function (): void {
    $user = User::factory()->withoutSecondFactorEnrollment()->create([
        'two_factor_confirmed_at' => null,
    ]);

    event(new TwoFactorAuthenticationEnabled($user));

    expect($user->refresh()->second_factor_enrolled_at)->toBeNull();
});

it('marks enrollment when two factor is enabled without confirmation', function (): void {
    config()->set('fortify.features', [
        Features::twoFactorAuthentication(['confirm' => false]),
    ]);

    $user = User::factory()->withoutSecondFactorEnrollment()->create([
        'two_factor_confirmed_at' => null,
    ]);

    event(new TwoFactorAuthenticationEnabled($user));

    expect($user->refresh()->second_factor_enrolled_at)->not->toBeNull();
});

it('marks enrollment when a passkey is registered', function (): void {
    $user = User::factory()->withoutTwoFactor()->withoutSecondFactorEnrollment()->create();

    /** @var Passkey $passkey */
    $passkey = $user->passkeys()->create([
        'name' => 'MacBook',
        'credential_id' => 'credential-id',
        'credential' => [],
    ]);

    event(new PasskeyRegistered($user, $passkey));

    expect($user->refresh()->second_factor_enrolled_at)->not->toBeNull();
});

it('ignores users of another model', function (): void {
    $user = User::factory()->withoutSecondFactorEnrollment()->create();

    /** @var Passkey $passkey */
    $passkey = $user->passkeys()->create([
        'name' => 'MacBook',
        'credential_id' => 'credential-id',
        'credential' => [],
    ]);

    event(new PasskeyRegistered(new AuthUser(), $passkey));

    expect($user->refresh()->second_factor_enrolled_at)->toBeNull();
});
