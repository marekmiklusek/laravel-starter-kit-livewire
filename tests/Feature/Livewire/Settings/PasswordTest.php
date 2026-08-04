<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;
use App\Livewire\Settings\Password;
use Illuminate\Support\Facades\Hash;

it('updates the password', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $component = Livewire::test(Password::class)
        ->set('current_password', 'password')
        ->set('password', 'NewPassword123')
        ->set('password_confirmation', 'NewPassword123')
        ->call('updatePassword');

    $component->assertHasNoErrors();
    $component->assertDispatched('password-updated');

    expect(Hash::check('NewPassword123', $user->refresh()->password))->toBeTrue();
});

it('fails with incorrect current password', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Password::class)
        ->set('current_password', 'wrong-password')
        ->set('password', 'NewPassword123')
        ->set('password_confirmation', 'NewPassword123')
        ->call('updatePassword')
        ->assertHasErrors(['current_password']);
});
