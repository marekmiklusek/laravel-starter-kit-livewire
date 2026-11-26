<?php

declare(strict_types=1);

use App\Models\User;

it('confirms the password after logging in with it', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHas('auth.password_confirmed_at');

    $this->get(route('two-factor.show'))->assertOk();
});

it('does not confirm the password before the two factor challenge', function (): void {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])
        ->assertRedirect(route('two-factor.login'))
        ->assertSessionMissing('auth.password_confirmed_at');
});
