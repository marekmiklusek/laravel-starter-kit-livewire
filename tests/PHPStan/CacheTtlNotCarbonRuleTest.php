<?php

declare(strict_types=1);

use Tests\PHPStan\Rules\CacheTtlNotCarbonRule;

it('reports expirations that are not carbon expressions', function (): void {
    $expectedMessage = '[C1] Expiration must be an inline Carbon expression such as now()->addMinutes(15), int given.';

    $this->assertRuleErrors(new CacheTtlNotCarbonRule(), <<<'PHP'
        <?php

        namespace App\Actions;

        use Illuminate\Support\Facades\URL;
        use Illuminate\Support\Facades\Cache;
        use Illuminate\Support\Facades\RateLimiter;

        final readonly class CacheOrderTotals
        {
            public function execute(int $orderCacheSeconds): void
            {
                Cache::remember('totals', 3600, fn (): int => 1);
                Cache::put('totals', 1, $orderCacheSeconds);
                Cache::add('totals', 1, ttl: 60);
                Cache::store()->put('totals', 1, 60);
                RateLimiter::hit('orders', 60);
                URL::temporarySignedRoute('unsubscribe', 600);
                URL::signedRoute('unsubscribe', [], 600);
            }
        }
        PHP, [
        [$expectedMessage, 13],
        [$expectedMessage, 14],
        [$expectedMessage, 15],
        [$expectedMessage, 16],
        [$expectedMessage, 17],
        [$expectedMessage, 18],
        [$expectedMessage, 19],
    ]);
});

it('allows carbon expirations', function (): void {
    $this->assertRuleErrors(new CacheTtlNotCarbonRule(), <<<'PHP'
        <?php

        namespace App\Actions;

        use Illuminate\Support\Facades\URL;
        use Illuminate\Support\Facades\Cache;
        use Illuminate\Support\Facades\RateLimiter;

        final readonly class CacheInvoiceTotals
        {
            public function execute(): void
            {
                Cache::remember('invoices', now()->addHour(), fn (): int => 1);
                Cache::put('invoices', 1, now()->addMinutes(15));
                Cache::forever('invoices', 1);
                RateLimiter::hit('invoices', now()->addMinute());
                URL::temporarySignedRoute('unsubscribe', now()->addMinutes(10));
            }
        }
        PHP, []);
});
