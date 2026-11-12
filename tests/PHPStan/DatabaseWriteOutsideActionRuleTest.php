<?php

declare(strict_types=1);

use Tests\PHPStan\Rules\DelegatingLayerMatcher;
use Tests\PHPStan\Rules\SideEffectCallDetector;
use Tests\PHPStan\Rules\DatabaseWriteOutsideActionRule;

function databaseWriteOutsideActionMessage(string $className, string $databaseWrite): string
{
    return sprintf('[A1] %s writes to the database with %s. Move the write into an action in app/Actions and call the action from here.', $className, $databaseWrite);
}

it('reports database writes in livewire components, controllers and jobs', function (): void {
    $this->assertRuleErrors(new DatabaseWriteOutsideActionRule(new SideEffectCallDetector([]), new DelegatingLayerMatcher()), <<<'PHP'
        <?php

        namespace App\Livewire {
            use App\Models\User;
            use Illuminate\Support\Facades\DB;

            final class EditUserProfile
            {
                public function save(User $user): void
                {
                    $user->save();
                    User::forceCreate(['name' => 'Created']);
                    DB::transaction(fn (): bool => true);
                }
            }
        }

        namespace App\Http\Controllers {
            use App\Models\User;

            final class UserController
            {
                public function destroy(User $user): void
                {
                    $user->delete();
                }
            }
        }

        namespace App\Jobs {
            use App\Models\User;

            final class DeactivateUsersJob
            {
                public function handle(): void
                {
                    User::query()->update(['name' => 'Inactive']);
                }
            }
        }
        PHP, [
        [databaseWriteOutsideActionMessage('App\Livewire\EditUserProfile', 'save()'), 11],
        [databaseWriteOutsideActionMessage('App\Livewire\EditUserProfile', 'User::forceCreate()'), 12],
        [databaseWriteOutsideActionMessage('App\Livewire\EditUserProfile', 'DB::transaction()'), 13],
        [databaseWriteOutsideActionMessage('App\Http\Controllers\UserController', 'delete()'), 25],
        [databaseWriteOutsideActionMessage('App\Jobs\DeactivateUsersJob', 'update()'), 37],
    ]);
});

it('allows database writes in actions and seeders and action calls from livewire', function (): void {
    $this->assertRuleErrors(new DatabaseWriteOutsideActionRule(new SideEffectCallDetector([]), new DelegatingLayerMatcher()), <<<'PHP'
        <?php

        namespace App\Actions {
            use App\Models\User;

            final readonly class RenameUser
            {
                public function execute(User $user, string $name): void
                {
                    $user->forceFill(['name' => $name])->save();
                }
            }
        }

        namespace App\Livewire {
            use App\Models\User;
            use App\Actions\RenameUser;

            final class RenameUserForm
            {
                public function rename(User $user, RenameUser $renameUser): void
                {
                    $renameUser->execute($user, 'Renamed');
                }
            }
        }

        namespace Database\Seeders {
            use App\Models\User;

            final class UserSeeder
            {
                public function run(): void
                {
                    User::forceCreate(['name' => 'Seeded']);
                }
            }
        }
        PHP, []);
});
