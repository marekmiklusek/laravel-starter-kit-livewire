<?php

declare(strict_types=1);

use App\Models\User;

it('resolves fortify auth views', function (): void {
    $this->get('/login')->assertOk();
    $this->get('/forgot-password')->assertOk();
    $this->get('/reset-password/token-value')->assertOk();
});

it('resolves the register view', function (): void {
    $this->get('/register')->assertOk();
});

it('throttles login attempts after five tries', function (): void {
    $credentials = [
        'email' => 'user@example.com',
        'password' => 'wrong-password',
    ];

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $this->post('/login', $credentials)->assertRedirect();
    }

    $this->post('/login', $credentials)->assertTooManyRequests();
});

it('throttles two factor attempts after five tries', function (): void {
    $user = User::factory()->create([
        'two_factor_secret' => encrypt('ABCDEFGHIJKLMNOP'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code-1'])),
        'two_factor_confirmed_at' => now(),
    ]);

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $this->withSession(['login.id' => $user->id])
            ->post('/two-factor-challenge', ['code' => '000000'])
            ->assertRedirect();
    }

    $this->withSession(['login.id' => $user->id])
        ->post('/two-factor-challenge', ['code' => '000000'])
        ->assertTooManyRequests();
});
