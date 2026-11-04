<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Testing\PendingCommand;

beforeEach(function (): void {
    $this->appPath = storage_path('framework/testing/make-action-'.uniqid());

    $this->app->useAppPath($this->appPath);
});

afterEach(function (): void {
    File::deleteDirectory($this->appPath);
});

it('creates an action from the stub without the action suffix', function (): void {
    /** @var PendingCommand $command */
    $command = $this->artisan('make:action', ['name' => 'CreateOrder']);

    $command->assertSuccessful();

    $command->run();

    expect(File::get($this->appPath.'/Actions/CreateOrder.php'))->toBe(<<<'PHP'
        <?php

        declare(strict_types=1);

        namespace App\Actions;

        final readonly class CreateOrder
        {
            public function execute(): void
            {
                //
            }
        }

        PHP);
});

it('refuses to overwrite an existing action', function (): void {
    File::ensureDirectoryExists($this->appPath.'/Actions');
    File::put($this->appPath.'/Actions/CreateOrder.php', 'existing');

    /** @var PendingCommand $command */
    $command = $this->artisan('make:action', ['name' => 'CreateOrder']);

    $command->expectsOutputToContain('Action already exists.');

    $command->run();

    expect(File::get($this->appPath.'/Actions/CreateOrder.php'))->toBe('existing');
});
