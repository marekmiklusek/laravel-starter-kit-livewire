<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;
use App\Livewire\Settings\Profile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;

it('mounts with user data', function (): void {
    $user = User::factory()->create(['name' => 'Alice', 'email' => 'alice@example.com']);
    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->assertSet('name', 'Alice')
        ->assertSet('email', 'alice@example.com');
});

it('updates profile information', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $component = Livewire::test(Profile::class)
        ->set('name', 'Bob')
        ->set('email', 'bob@example.com')
        ->call('updateProfileInformation');

    $component->assertHasNoErrors();
    $component->assertDispatched('profile-updated');

    $fresh = $user->fresh();

    expect($fresh?->name)->toBe('Bob')
        ->and($fresh?->email)->toBe('bob@example.com')
        ->and($fresh?->email_verified_at)->toBeNull();
});

it('keeps verification when email unchanged', function (): void {
    $user = User::factory()->create(['email' => 'same@example.com']);
    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->set('name', 'Changed')
        ->set('email', 'same@example.com')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    expect($user->fresh()?->email_verified_at)->not->toBeNull();
});

it('redirects when resending verification for already verified user', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->call('resendVerificationNotification')
        ->assertRedirect(route('dashboard', absolute: false));
});

it('sends verification notification for unverified users', function (): void {
    Notification::fake();

    $user = User::factory()->unverified()->create();
    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->call('resendVerificationNotification');

    Notification::assertSentTo($user, VerifyEmail::class);
});
