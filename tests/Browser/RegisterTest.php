<?php

declare(strict_types=1);

it('displays the register page', function (): void {
    $page = visit('/register');

    $page->assertSee(__('Create an account'))
        ->assertNoJavascriptErrors();
});
