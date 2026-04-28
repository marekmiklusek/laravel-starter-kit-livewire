<?php

declare(strict_types=1);

it('redirects the home page to login', function (): void {
    $page = visit('/');

    $page->assertPathIs('/login')
        ->assertNoJavascriptErrors();
});
