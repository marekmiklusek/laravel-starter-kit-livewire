<?php

declare(strict_types=1);

it('sends the noindex header on every response', function (): void {
    $response = $this->get('/login');

    $response->assertOk();
    $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

it('renders the noindex meta tag in the document head', function (): void {
    $response = $this->get('/login');

    $response->assertOk();
    $response->assertSeeHtml('<meta name="robots" content="noindex, nofollow" />');
});
