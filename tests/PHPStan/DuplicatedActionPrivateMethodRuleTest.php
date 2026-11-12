<?php

declare(strict_types=1);

use Tests\PHPStan\Rules\ActionPrivateMethodCollector;
use Tests\PHPStan\Rules\DuplicatedActionPrivateMethodRule;

function duplicatedActionPrivateMethodMessage(string $method, string $otherClassName): string
{
    return sprintf('[P1] Private method %s has the same body as a private method in %s. Extract it into its own action in app/Actions and inject it.', $method, $otherClassName);
}

it('reports private methods duplicated across actions', function (): void {
    $this->assertRuleErrors(new DuplicatedActionPrivateMethodRule(), <<<'PHP'
        <?php

        namespace App\Actions;

        final readonly class ShipOrder
        {
            public function execute(int $orderTotal): int
            {
                return $this->roundToCents($orderTotal);
            }

            private function roundToCents(int $amount): int
            {
                return intdiv($amount, 100) * 100;
            }
        }

        final readonly class RefundOrder
        {
            public function execute(int $orderTotal): int
            {
                return $this->roundToHundreds($orderTotal);
            }

            private function roundToHundreds(int $amount): int
            {
                return intdiv($amount, 100) * 100;
            }
        }
        PHP, [
        [duplicatedActionPrivateMethodMessage('App\Actions\ShipOrder::roundToCents()', 'App\Actions\RefundOrder'), 12],
        [duplicatedActionPrivateMethodMessage('App\Actions\RefundOrder::roundToHundreds()', 'App\Actions\ShipOrder'), 25],
    ], [new ActionPrivateMethodCollector()]);
});

it('allows different bodies, duplicates within one action and duplicates outside actions', function (): void {
    $this->assertRuleErrors(new DuplicatedActionPrivateMethodRule(), <<<'PHP'
        <?php

        namespace App\Actions {
            final readonly class CancelOrder
            {
                public function execute(int $orderTotal): int
                {
                    return $this->roundToCents($orderTotal) + $this->roundDown($orderTotal);
                }

                private function roundToCents(int $amount): int
                {
                    return intdiv($amount, 100) * 100;
                }

                private function roundDown(int $amount): int
                {
                    return intdiv($amount, 100) * 100;
                }
            }

            final readonly class ArchiveOrder
            {
                public function execute(int $orderTotal): int
                {
                    return $this->roundToThousands($orderTotal);
                }

                private function roundToThousands(int $amount): int
                {
                    return intdiv($amount, 1000) * 1000;
                }
            }
        }

        namespace App\Services {
            final readonly class OrderRounding
            {
                public function round(int $orderTotal): int
                {
                    return $this->roundToCents($orderTotal);
                }

                private function roundToCents(int $amount): int
                {
                    return intdiv($amount, 100) * 100;
                }
            }
        }
        PHP, [], [new ActionPrivateMethodCollector()]);
});
