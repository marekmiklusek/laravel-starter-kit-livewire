<?php

declare(strict_types=1);

use Tests\PHPStan\Rules\SideEffectCallDetector;
use Tests\PHPStan\Rules\SideEffectInTransactionRule;

function sideEffectInTransactionMessage(string $sideEffect): string
{
    return sprintf('[T1] %s runs inside DB::transaction() and a rollback cannot undo it. Move it after the transaction or defer it with afterCommit().', $sideEffect);
}

it('reports side effects inside transactions', function (): void {
    $this->assertRuleErrors(new SideEffectInTransactionRule(new SideEffectCallDetector(['App\Actions\ShoptetClient'])), <<<'PHP'
        <?php

        namespace App\Actions;

        use App\Models\User;
        use Illuminate\Support\Facades\DB;
        use Illuminate\Support\Facades\Http;

        final readonly class SyncUserToShoptet
        {
            public function __construct(private ShoptetClient $shoptetClient) {}

            public function execute(User $user): void
            {
                DB::transaction(function () use ($user): void {
                    $user->save();
                    Http::post('https://example.com');
                    $this->shoptetClient->updateCustomer();
                    event('user.synced');
                });

                DB::transaction(fn (): mixed => Http::post('https://example.com'));
            }
        }

        final class ShoptetClient
        {
            public function updateCustomer(): void {}
        }
        PHP, [
        [sideEffectInTransactionMessage('Http::post()'), 17],
        [sideEffectInTransactionMessage('updateCustomer()'), 18],
        [sideEffectInTransactionMessage('event()'), 19],
        [sideEffectInTransactionMessage('Http::post()'), 22],
    ]);
});

it('allows deferred side effects and side effects outside transactions', function (): void {
    $this->assertRuleErrors(new SideEffectInTransactionRule(new SideEffectCallDetector([])), <<<'PHP'
        <?php

        namespace App\Actions;

        use App\Models\User;
        use Illuminate\Support\Facades\DB;
        use Illuminate\Support\Facades\Http;
        use Illuminate\Foundation\Bus\Dispatchable;

        final readonly class RegisterCustomer
        {
            public function execute(User $user): void
            {
                DB::transaction(function () use ($user): void {
                    $user->save();
                    SendCustomerWelcomeMail::dispatch()->afterCommit();
                    DB::afterCommit(fn (): mixed => Http::post('https://example.com'));
                });

                Http::post('https://example.com');
            }
        }

        final class SendCustomerWelcomeMail
        {
            use Dispatchable;
        }
        PHP, []);
});
