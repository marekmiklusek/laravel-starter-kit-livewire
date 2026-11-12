<?php

declare(strict_types=1);

use Tests\PHPStan\Rules\QueryMutationRule;
use Tests\PHPStan\Rules\SideEffectCallDetector;

function queryMutationMessage(string $stateChange): string
{
    return sprintf('[Q1] App\Queries\MutatingUsersQuery only reads data, but %s changes state. Move the change into an action in app/Actions.', $stateChange);
}

it('reports state changes inside queries', function (): void {
    $this->assertRuleErrors(new QueryMutationRule(new SideEffectCallDetector([])), <<<'PHP'
        <?php

        namespace App\Queries;

        use App\Models\User;
        use Illuminate\Support\Facades\DB;
        use Illuminate\Support\Facades\Http;
        use Illuminate\Auth\Notifications\VerifyEmail;

        final readonly class MutatingUsersQuery
        {
            public function get(User $user): void
            {
                $user->save();
                User::query()->where('id', $user->id)->update(['name' => 'Updated']);
                User::forceCreate(['name' => 'Created']);
                DB::transaction(fn (): bool => true);
                Http::post('https://example.com');
                $user->notify(new VerifyEmail);
                event('users.mutated');
            }
        }
        PHP, [
        [queryMutationMessage('save()'), 14],
        [queryMutationMessage('update()'), 15],
        [queryMutationMessage('User::forceCreate()'), 16],
        [queryMutationMessage('DB::transaction()'), 17],
        [queryMutationMessage('Http::post()'), 18],
        [queryMutationMessage('notify()'), 19],
        [queryMutationMessage('event()'), 20],
    ]);
});

it('ignores reads inside queries and writes outside queries', function (): void {
    $this->assertRuleErrors(new QueryMutationRule(new SideEffectCallDetector([])), <<<'PHP'
        <?php

        namespace App\Queries {
            use App\Models\User;
            use Illuminate\Database\Eloquent\Collection;

            final readonly class ListLatestUsersQuery
            {
                /**
                 * @return Collection<int, User>
                 */
                public function get(): Collection
                {
                    return User::query()->latest()->get();
                }
            }
        }

        namespace App\Actions {
            use App\Models\User;

            final readonly class SaveUserProfile
            {
                public function execute(User $user): void
                {
                    $user->save();
                }
            }
        }
        PHP, []);
});
