<?php

declare(strict_types=1);

use App\Models\User;
use App\Livewire\Settings\Appearance;

it('renders the appearance settings page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('appearance.edit'));

    $response->assertOk();
    $response->assertSeeLivewire(Appearance::class);
});
