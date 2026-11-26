<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;
use App\Http\Middleware\EnsureSecondFactorIsEnrolled;

it('resolves fortify auth views', function (): void {
    $this->get('/login')->assertOk();
    $this->get('/forgot-password')->assertOk();
    $this->get('/reset-password/token-value')->assertOk();
});

it('does not expose registration routes', function (): void {
    $this->get('/register')->assertNotFound();
    $this->post('/register')->assertNotFound();
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

it('throttles passkey attempts per credential', function (): void {
    $credential = ['credential' => ['id' => 'credential-id']];

    for ($attempt = 1; $attempt <= 10; $attempt++) {
        $this->postJson(route('passkey.login'), $credential)->assertUnprocessable();
    }

    $this->postJson(route('passkey.login'), $credential)->assertTooManyRequests();
    $this->postJson(route('passkey.login'), ['credential' => ['id' => 'other-credential-id']])->assertUnprocessable();
});

it('applies the second factor enrollment to livewire requests', function (): void {
    expect(Livewire::getPersistentMiddleware())->toContain(EnsureSecondFactorIsEnrolled::class);
});

it('offers passkey password confirmation only to users with a passkey', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('password.confirm'))
        ->assertOk()
        ->assertDontSee(__('Confirm with passkey'));

    $user->passkeys()->create([
        'name' => 'MacBook',
        'credential_id' => 'credential-id',
        'credential' => [],
    ]);

    $this->actingAs($user)
        ->get(route('password.confirm'))
        ->assertOk()
        ->assertSee(__('Confirm with passkey'));
});
