<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;
use App\Livewire\Settings\TwoFactor\RecoveryCodes;

it('loads recovery codes when two factor is enabled', function (): void {
    $codes = ['code-1', 'code-2'];
    $user = User::factory()->create([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_recovery_codes' => encrypt(json_encode($codes)),
        'two_factor_confirmed_at' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test(RecoveryCodes::class)
        ->assertSet('recoveryCodes', $codes);
});

it('skips loading when two factor is not enabled', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $this->actingAs($user);

    Livewire::test(RecoveryCodes::class)
        ->assertSet('recoveryCodes', []);
});

it('regenerates recovery codes', function (): void {
    $user = User::factory()->create([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['old-code'])),
        'two_factor_confirmed_at' => now(),
    ]);

    $this->actingAs($user);

    $component = Livewire::test(RecoveryCodes::class)
        ->call('regenerateRecoveryCodes');

    /** @var array<int, string> $newCodes */
    $newCodes = $component->get('recoveryCodes');

    expect($newCodes)->not->toBe(['old-code'])
        ->and($newCodes)->toHaveCount(8);
});

it('adds an error when recovery codes cannot be decoded', function (): void {
    $user = User::factory()->create([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_recovery_codes' => 'not-encrypted',
        'two_factor_confirmed_at' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test(RecoveryCodes::class)
        ->assertHasErrors('recoveryCodes')
        ->assertSet('recoveryCodes', []);
});
