<?php

declare(strict_types=1);

use Tests\PHPStan\Rules\DelegatingLayerMatcher;
use Tests\PHPStan\Rules\SideEffectCallDetector;
use Tests\PHPStan\Rules\DatabaseQueryOutsideQueryRule;

function databaseQueryOutsideQueryMessage(string $className, string $databaseQuery): string
{
    return sprintf('[R1] %s queries the database with %s. Move the query into a query class in app/Queries and call its get() method from here.', $className, $databaseQuery);
}

it('reports database queries in livewire components and commands', function (): void {
    $this->assertRuleErrors(new DatabaseQueryOutsideQueryRule(new SideEffectCallDetector([]), new DelegatingLayerMatcher()), <<<'PHP'
        <?php

        namespace App\Livewire {
            use App\Models\User;
            use Illuminate\Support\Facades\DB;

            final class UserList
            {
                public function users(User $user): void
                {
                    User::query()->where('name', 'Alice')->get();
                    User::find(1);
                    DB::table('users')->count();
                    $user->fresh();
                    $user->newQuery()->count();
                }
            }
        }

        namespace App\Console\Commands {
            use App\Models\User;

            final class CountUsersCommand
            {
                public function handle(): int
                {
                    return User::count();
                }
            }
        }
        PHP, [
        [databaseQueryOutsideQueryMessage('App\Livewire\UserList', 'User::query()'), 11],
        [databaseQueryOutsideQueryMessage('App\Livewire\UserList', 'User::find()'), 12],
        [databaseQueryOutsideQueryMessage('App\Livewire\UserList', 'DB::table()'), 13],
        [databaseQueryOutsideQueryMessage('App\Livewire\UserList', 'fresh()'), 14],
        [databaseQueryOutsideQueryMessage('App\Livewire\UserList', 'newQuery()'), 15],
        [databaseQueryOutsideQueryMessage('App\Console\Commands\CountUsersCommand', 'User::count()'), 27],
    ]);
});

it('allows queries in query classes, query class calls, auth and validation rules', function (): void {
    $this->assertRuleErrors(new DatabaseQueryOutsideQueryRule(new SideEffectCallDetector([]), new DelegatingLayerMatcher()), <<<'PHP'
        <?php

        namespace App\Queries {
            use App\Models\User;
            use Illuminate\Database\Eloquent\Collection;

            final readonly class ListActiveUsersQuery
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

        namespace App\Livewire {
            use App\Models\User;
            use Illuminate\Validation\Rule;
            use App\Queries\ListActiveUsersQuery;
            use Illuminate\Support\Facades\Auth;

            final class ActiveUserList
            {
                public function users(ListActiveUsersQuery $listActiveUsersQuery): void
                {
                    $listActiveUsersQuery->get();
                    Auth::user();
                    Rule::unique(User::class);
                }

                public function store(User $user): void
                {
                    $user->save();
                }
            }
        }
        PHP, []);
});
