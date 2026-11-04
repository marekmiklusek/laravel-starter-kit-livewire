<?php

declare(strict_types=1);

it('redirects home to login', function (): void {
    $this->get(route('home'))->assertRedirect(route('login'));
});
