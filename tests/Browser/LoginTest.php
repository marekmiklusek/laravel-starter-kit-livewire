<?php

declare(strict_types=1);

it('displays the login page', function (): void {
    $page = visit('/login');

    $page->assertSee(__('Log in to your account'))
        ->assertNoJavascriptErrors();
});
