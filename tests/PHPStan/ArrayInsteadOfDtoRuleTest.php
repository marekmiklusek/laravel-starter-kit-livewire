<?php

declare(strict_types=1);

use Tests\PHPStan\Rules\ArrayInsteadOfDtoRule;

function arrayInsteadOfDtoMessage(string $method): string
{
    return sprintf('[D1] %s returns an associative array. Return a DTO from app/Dtos, a model, a collection or a list<...> instead.', $method);
}

function arrayParameterInsteadOfDtoMessage(string $parameter, string $method): string
{
    return sprintf('[D1] Parameter $%s of %s is an associative array. Pass a DTO from app/Dtos instead.', $parameter, $method);
}

it('reports associative arrays in actions, queries and api clients', function (): void {
    $this->assertRuleErrors(new ArrayInsteadOfDtoRule(['App\Services']), <<<'PHP'
        <?php

        namespace App\Actions {
            final readonly class CreateOrderSummary
            {
                /**
                 * @param  array<string, mixed>  $orderData
                 * @return array{total: int, currency: string}
                 */
                public function execute(array $orderData): array
                {
                    return ['total' => 100, 'currency' => 'EUR'];
                }
            }
        }

        namespace App\Queries {
            final readonly class OrderStatisticsQuery
            {
                /**
                 * @return array<string, int>|null
                 */
                public function get(): ?array
                {
                    return null;
                }
            }
        }

        namespace App\Services {
            final readonly class ShoptetClient
            {
                /**
                 * @return array<string, mixed>
                 */
                public function getOrder(string $code): array
                {
                    return [];
                }
            }
        }
        PHP, [
        [arrayInsteadOfDtoMessage('App\Actions\CreateOrderSummary::execute()'), 10],
        [arrayParameterInsteadOfDtoMessage('orderData', 'App\Actions\CreateOrderSummary::execute()'), 10],
        [arrayInsteadOfDtoMessage('App\Queries\OrderStatisticsQuery::get()'), 23],
        [arrayInsteadOfDtoMessage('App\Services\ShoptetClient::getOrder()'), 36],
    ]);
});

it('allows lists, models, constructor arrays, fortify actions, api client input and other namespaces', function (): void {
    $this->assertRuleErrors(new ArrayInsteadOfDtoRule(['App\Services']), <<<'PHP'
        <?php

        namespace App\Actions {
            use App\Models\User;

            final readonly class ListOrderNumbers
            {
                /**
                 * @param  list<int>  $orderIds
                 * @return list<string>
                 */
                public function execute(array $orderIds, string $prefix): array
                {
                    return ['A1', 'A2'];
                }
            }

            final readonly class FindOrderOwner
            {
                /**
                 * @param  array<string, mixed>  $options
                 */
                public function __construct(private array $options) {}

                public function execute(): ?User
                {
                    return null;
                }
            }
        }

        namespace App\Actions\Fortify {
            final readonly class ResetCustomerPassword
            {
                /**
                 * @param  array<string, string>  $input
                 */
                public function reset(array $input): void {}
            }
        }

        namespace App\Services {
            final readonly class OrderApiClient
            {
                /**
                 * @param  array<string, mixed>  $payload
                 */
                public function createOrder(array $payload): string
                {
                    return 'created';
                }
            }
        }

        namespace App\Support {
            final readonly class OrderExporter
            {
                /**
                 * @return array<string, mixed>
                 */
                public function export(): array
                {
                    return [];
                }
            }
        }
        PHP, []);
});
