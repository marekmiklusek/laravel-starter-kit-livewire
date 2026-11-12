<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;
use App\Livewire\Settings\Appearance;

it('renders the appearance settings', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Appearance::class)
        ->assertSee(__('Update the appearance settings for your account'));
});
