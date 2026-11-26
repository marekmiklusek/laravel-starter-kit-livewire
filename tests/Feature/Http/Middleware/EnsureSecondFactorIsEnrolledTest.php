<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Fortify\Features;

it('lets guests through', function (): void {
    $this->get(route('login'))->assertOk();
});

it('lets enrolled users through', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

it('redirects unenrolled users to two factor setup', function (): void {
    $user = User::factory()->withoutTwoFactor()->withoutSecondFactorEnrollment()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('two-factor.show'));
});

it('redirects unenrolled users to passkeys when two factor is disabled', function (): void {
    config()->set('fortify.features', [
        Features::passkeys(['confirmPassword' => true]),
    ]);

    $user = User::factory()->withoutTwoFactor()->withoutSecondFactorEnrollment()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('passkeys.show'));
});

it('lets unenrolled users through when no second factor feature is enabled', function (): void {
    config()->set('fortify.features', []);

    $user = User::factory()->withoutTwoFactor()->withoutSecondFactorEnrollment()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

it('lets unenrolled users through when enrollment is not required', function (): void {
    config()->set('fortify.second_factor_required', false);

    $user = User::factory()->withoutTwoFactor()->withoutSecondFactorEnrollment()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

it('lets unenrolled users reach the enrollment routes', function (string $routeName): void {
    $user = User::factory()->withoutTwoFactor()->withoutSecondFactorEnrollment()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route($routeName))
        ->assertOk();
})->with([
    'two-factor.show',
    'passkeys.show',
    'password.confirm',
]);

it('lets unenrolled users log out', function (): void {
    $user = User::factory()->withoutTwoFactor()->withoutSecondFactorEnrollment()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect('/');

    $this->assertGuest();
});
