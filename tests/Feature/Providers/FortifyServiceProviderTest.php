<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\RateLimiter;

it('resolves fortify auth views', function (): void {
    $this->get('/login')->assertOk();
    $this->get('/forgot-password')->assertOk();
    $this->get('/reset-password/token-value')->assertOk();
});

it('resolves the register view when registration is enabled', function (): void {
    $this->get('/register')->assertOk();
})->skip(fn (): bool => ! config()->boolean('fortify.registration_enabled'), 'Registration is disabled.');

it('configures the login rate limiter', function (): void {
    $request = Request::create('/login', 'POST', [
        'email' => 'user@example.com',
    ]);

    $limit = RateLimiter::limiter('login')($request);

    expect($limit->maxAttempts)->toBe(5);
});

it('configures the two factor rate limiter', function (): void {
    $request = Request::create('/two-factor-challenge', 'POST');
    $request->setLaravelSession(resolve(Session::class));

    $limit = RateLimiter::limiter('two-factor')($request);

    expect($limit->maxAttempts)->toBe(5);
});
