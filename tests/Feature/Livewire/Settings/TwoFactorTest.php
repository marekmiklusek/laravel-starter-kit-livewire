<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;
use Laravel\Fortify\Features;
use PragmaRX\Google2FA\Google2FA;
use App\Livewire\Settings\TwoFactor;

beforeEach(function (): void {
    $this->user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($this->user);
});

it('mounts with two factor disabled', function (): void {
    Livewire::test(TwoFactor::class)
        ->assertSet('twoFactorEnabled', false)
        ->assertSet('requiresConfirmation', true);
});

it('resets pending two factor on mount when unconfirmed', function (): void {
    /** @var User $user */
    $user = $this->user;

    $user->forceFill([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code-1'])),
        'two_factor_confirmed_at' => null,
    ])->save();

    Livewire::test(TwoFactor::class)
        ->assertSet('twoFactorEnabled', false);

    expect($user->refresh()->two_factor_secret)->toBeNull();
});

it('enables two factor and loads setup data', function (): void {
    $component = Livewire::test(TwoFactor::class)
        ->call('enable')
        ->assertSet('showModal', true);

    expect($component->get('qrCodeSvg'))->toBeString()->not->toBe('')
        ->and($component->get('manualSetupKey'))->toBeString()->not->toBe('');
});

it('shows verification step when required', function (): void {
    Livewire::test(TwoFactor::class)
        ->call('enable')
        ->call('showVerificationIfNecessary')
        ->assertSet('showVerificationStep', true);
});

it('confirms two factor with valid code', function (): void {
    /** @var User $user */
    $user = $this->user;

    $component = Livewire::test(TwoFactor::class)
        ->call('enable');

    $secret = $user->refresh()->two_factor_secret;

    expect($secret)->toBeString();

    $decrypted = decrypt($secret ?? '');

    expect($decrypted)->toBeString();

    $code = resolve(Google2FA::class)->getCurrentOtp(is_string($decrypted) ? $decrypted : '');

    $component
        ->set('code', $code)
        ->call('confirmTwoFactor')
        ->assertSet('twoFactorEnabled', true)
        ->assertSet('showModal', false);
});

it('resets verification state', function (): void {
    Livewire::test(TwoFactor::class)
        ->call('enable')
        ->call('showVerificationIfNecessary')
        ->set('code', '123456')
        ->call('resetVerification')
        ->assertSet('code', '')
        ->assertSet('showVerificationStep', false);
});

it('disables two factor', function (): void {
    /** @var User $user */
    $user = $this->user;

    $user->forceFill([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code-1'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    Livewire::test(TwoFactor::class)
        ->call('disable')
        ->assertSet('twoFactorEnabled', false);

    expect($user->refresh()->two_factor_secret)->toBeNull();
});

it('renders the enabled modal config', function (): void {
    /** @var User $user */
    $user = $this->user;

    $user->forceFill([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code-1'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    Livewire::test(TwoFactor::class)
        ->call('enable')
        ->assertSee(__('Two-Factor Authentication Enabled'))
        ->assertSee(__('Close'));
});

it('renders the verification modal config', function (): void {
    Livewire::test(TwoFactor::class)
        ->call('enable')
        ->call('showVerificationIfNecessary')
        ->assertSee(__('Verify Authentication Code'))
        ->assertSee(__('Enter the 6-digit code from your authenticator app.'));
});

it('renders the default modal config', function (): void {
    Livewire::test(TwoFactor::class)
        ->call('enable')
        ->assertSee(__('Enable Two-Factor Authentication'));
});

it('enables two factor directly when confirmation is disabled', function (): void {
    config()->set('fortify.features', [
        Features::twoFactorAuthentication(['confirm' => false]),
    ]);

    Livewire::test(TwoFactor::class)
        ->assertSet('requiresConfirmation', false)
        ->call('enable')
        ->assertSet('twoFactorEnabled', true);
});

it('closes modal from showVerificationIfNecessary when confirmation is disabled', function (): void {
    config()->set('fortify.features', [
        Features::twoFactorAuthentication(['confirm' => false]),
    ]);

    Livewire::test(TwoFactor::class)
        ->call('enable')
        ->call('showVerificationIfNecessary')
        ->assertSet('showModal', false);
});

it('surfaces setup data error when qr fails', function (): void {
    /** @var User $user */
    $user = $this->user;

    $user->forceFill([
        'two_factor_secret' => 'not-properly-encrypted',
        'two_factor_recovery_codes' => encrypt(json_encode(['code-1'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $component = Livewire::test(TwoFactor::class)
        ->call('enable')
        ->assertSet('qrCodeSvg', '')
        ->assertSet('manualSetupKey', '');

    $component->assertHasErrors('setupData');
});
