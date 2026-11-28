<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;
use Laravel\Passkeys\Passkey;
use App\Livewire\Settings\Passkeys;

beforeEach(function (): void {
    $this->user = User::factory()->create();

    $this->actingAs($this->user);
});

it('requires password confirmation', function (): void {
    $this->get(route('passkeys.show'))
        ->assertRedirect(route('password.confirm'));
});

it('renders the empty state', function (): void {
    $this->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('passkeys.show'))
        ->assertOk()->assertSee(__('No passkeys yet'))->assertDontSeeHtml('data-test="second-factor-enrollment-notice"');
});

it('shows the enrollment notice to unenrolled users', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('passkeys.show'))->assertOk()->assertSeeHtml('data-test="second-factor-enrollment-notice"');
});

it('forbids access when passkeys are disabled', function (): void {
    config()->set('fortify.features', []);

    Livewire::test(Passkeys::class)->assertForbidden();
});

it('lists the passkeys of the user', function (): void {
    /** @var User $user */
    $user = $this->user;

    $user->passkeys()->create([
        'name' => 'MacBook',
        'credential_id' => 'credential-id',
        'credential' => ['aaguid' => 'adce0002-35bc-c60a-648b-0b25f1f05503'],
    ]);

    User::factory()->create()->passkeys()->create([
        'name' => 'Foreign passkey',
        'credential_id' => 'foreign-credential-id',
        'credential' => [],
    ]);

    Livewire::test(Passkeys::class)
        ->assertSee('MacBook')
        ->assertSee('Chrome on Mac')
        ->assertDontSee('Foreign passkey')
        ->assertDontSee(__('No passkeys yet'));
});

it('shows when the passkey was last used', function (): void {
    /** @var User $user */
    $user = $this->user;

    /** @var Passkey $passkey */
    $passkey = $user->passkeys()->create([
        'name' => 'MacBook',
        'credential_id' => 'credential-id',
        'credential' => [],
    ]);

    $passkey->forceFill(['last_used_at' => now()->subHour()])->save();

    Livewire::test(Passkeys::class)
        ->assertSee(__('Last used :time', ['time' => '1 hour ago']));
});

it('opens the delete confirmation', function (): void {
    /** @var User $user */
    $user = $this->user;

    /** @var Passkey $passkey */
    $passkey = $user->passkeys()->create([
        'name' => 'MacBook',
        'credential_id' => 'credential-id',
        'credential' => [],
    ]);

    Livewire::test(Passkeys::class)
        ->call('confirmDelete', $passkey->id)
        ->assertSet('showDeleteModal', true)
        ->assertSet('deletingPasskeyId', $passkey->id)
        ->assertSet('deletingPasskeyName', 'MacBook');
});

it('deletes the confirmed passkey', function (): void {
    /** @var User $user */
    $user = $this->user;

    /** @var Passkey $passkey */
    $passkey = $user->passkeys()->create([
        'name' => 'MacBook',
        'credential_id' => 'credential-id',
        'credential' => [],
    ]);

    Livewire::test(Passkeys::class)
        ->call('confirmDelete', $passkey->id)
        ->call('deletePasskey')
        ->assertSet('showDeleteModal', false)
        ->assertSet('deletingPasskeyId', null)
        ->assertSee(__('No passkeys yet'));

    $this->assertModelMissing($passkey);
});

it('does nothing when deleting without confirmation', function (): void {
    /** @var User $user */
    $user = $this->user;

    /** @var Passkey $passkey */
    $passkey = $user->passkeys()->create([
        'name' => 'MacBook',
        'credential_id' => 'credential-id',
        'credential' => [],
    ]);

    Livewire::test(Passkeys::class)
        ->call('deletePasskey');

    $this->assertModelExists($passkey);
});

it('closes the delete confirmation', function (): void {
    /** @var User $user */
    $user = $this->user;

    /** @var Passkey $passkey */
    $passkey = $user->passkeys()->create([
        'name' => 'MacBook',
        'credential_id' => 'credential-id',
        'credential' => [],
    ]);

    Livewire::test(Passkeys::class)
        ->call('confirmDelete', $passkey->id)
        ->call('closeDeleteModal')
        ->assertSet('showDeleteModal', false)
        ->assertSet('deletingPasskeyId', null)
        ->assertSet('deletingPasskeyName', '');
});
