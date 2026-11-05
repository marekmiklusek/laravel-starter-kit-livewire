<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Testing\PendingCommand;

beforeEach(function (): void {
    $this->appPath = storage_path('framework/testing/make-query-'.uniqid());

    $this->app->useAppPath($this->appPath);
});

afterEach(function (): void {
    File::deleteDirectory($this->appPath);
});

it('creates a query from the stub with the query suffix', function (string $name): void {
    /** @var PendingCommand $command */
    $command = $this->artisan('make:query', ['name' => $name]);

    $command->assertSuccessful();

    $command->run();

    expect(File::get($this->appPath.'/Queries/PendingOrdersQuery.php'))->toBe(<<<'PHP'
        <?php

        declare(strict_types=1);

        namespace App\Queries;

        final readonly class PendingOrdersQuery
        {
            public function get(): void
            {
                //
            }
        }

        PHP);
})->with([
    'without suffix' => ['PendingOrders'],
    'with suffix' => ['PendingOrdersQuery'],
]);
