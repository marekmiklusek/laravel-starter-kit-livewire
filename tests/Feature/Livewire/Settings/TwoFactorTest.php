<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;
use Laravel\Fortify\Features;
use PragmaRX\Google2FA\Google2FA;
use App\Livewire\Settings\TwoFactor;

beforeEach(function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $this->actingAs($user);
    $this->user = $user;
});

it('mounts with two factor disabled', function (): void {
    Livewire::test(TwoFactor::class)
        ->assertSet('twoFactorEnabled', false)
        ->assertSet('requiresConfirmation', true);
});

it('resets pending two factor on mount when unconfirmed', function (): void {
    $this->user->forceFill([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code-1'])),
        'two_factor_confirmed_at' => null,
    ])->save();

    Livewire::test(TwoFactor::class)
        ->assertSet('twoFactorEnabled', false);

    expect($this->user->fresh()?->two_factor_secret)->toBeNull();
});

it('enables two factor and loads setup data', function (): void {
    $component = Livewire::test(TwoFactor::class)
        ->call('enable')
        ->assertSet('showModal', true);

    expect($component->get('qrCodeSvg'))->not->toBe('')
        ->and($component->get('manualSetupKey'))->not->toBe('');
});

it('shows verification step when required', function (): void {
    Livewire::test(TwoFactor::class)
        ->call('enable')
        ->call('showVerificationIfNecessary')
        ->assertSet('showVerificationStep', true);
});

it('confirms two factor with valid code', function (): void {
    $component = Livewire::test(TwoFactor::class)
        ->call('enable');

    $user = $this->user->fresh();
    $secret = decrypt($user->two_factor_secret);
    $code = resolve(Google2FA::class)->getCurrentOtp($secret);

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
    $this->user->forceFill([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code-1'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    Livewire::test(TwoFactor::class)
        ->call('disable')
        ->assertSet('twoFactorEnabled', false);

    expect($this->user->fresh()?->two_factor_secret)->toBeNull();
});

it('returns enabled modal config', function (): void {
    $component = Livewire::test(TwoFactor::class);
    $instance = $component->instance();
    $instance->twoFactorEnabled = true;

    expect($instance->modalConfig())->toHaveKeys(['title', 'description', 'buttonText']);
});

it('returns verification modal config', function (): void {
    $component = Livewire::test(TwoFactor::class);
    $instance = $component->instance();
    $instance->twoFactorEnabled = false;
    $instance->showVerificationStep = true;

    expect($instance->modalConfig()['title'])->toBe(__('Verify Authentication Code'));
});

it('returns default modal config', function (): void {
    $component = Livewire::test(TwoFactor::class);

    expect($component->instance()->modalConfig()['title'])
        ->toBe(__('Enable Two-Factor Authentication'));
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
    $this->user->forceFill([
        'two_factor_secret' => 'not-properly-encrypted',
    ])->save();

    $component = Livewire::test(TwoFactor::class);
    $instance = $component->instance();
    $reflection = new ReflectionClass($instance);
    $method = $reflection->getMethod('loadSetupData');
    $method->invoke($instance);

    expect($instance->qrCodeSvg)->toBe('')
        ->and($instance->manualSetupKey)->toBe('');
});
