<?php

declare(strict_types=1);

it('displays the register page', function (): void {
    $page = visit('/register');

    $page->assertSee(__('Create an account'))
        ->assertNoJavascriptErrors();
})->skip(fn (): bool => ! config()->boolean('fortify.registration_enabled'), 'Registration is disabled.');

it('returns 404 for the register page when registration is disabled', function (): void {
    $this->get('/register')->assertNotFound();
})->skip(fn (): bool => config()->boolean('fortify.registration_enabled'), 'Registration is enabled.');
