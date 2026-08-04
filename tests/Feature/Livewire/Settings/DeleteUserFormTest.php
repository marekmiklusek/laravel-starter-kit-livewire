<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;
use Illuminate\Support\Facades\Auth;
use App\Livewire\Settings\DeleteUserForm;

it('deletes the user with correct password', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test(DeleteUserForm::class)
        ->set('password', 'password')
        ->call('deleteUser');

    $component->assertHasNoErrors();
    $component->assertRedirect('/');

    expect(Auth::check())->toBeFalse();
});

it('does not delete the user with wrong password', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(DeleteUserForm::class)
        ->set('password', 'wrong-password')
        ->call('deleteUser')
        ->assertHasErrors(['password']);

    $this->assertDatabaseHas('users', ['id' => $user->id]);
});
