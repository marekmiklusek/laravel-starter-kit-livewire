<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Testing\PendingCommand;

beforeEach(function (): void {
    $this->appPath = storage_path('framework/testing/make-dto-'.uniqid());

    $this->app->useAppPath($this->appPath);
});

afterEach(function (): void {
    File::deleteDirectory($this->appPath);
});

it('creates a dto from the stub with the dto suffix', function (string $name): void {
    /** @var PendingCommand $command */
    $command = $this->artisan('make:dto', ['name' => $name]);

    $command->assertSuccessful();

    $command->run();

    expect(File::get($this->appPath.'/Dtos/OrderSummaryDto.php'))->toBe(<<<'PHP'
        <?php

        declare(strict_types=1);

        namespace App\Dtos;

        final readonly class OrderSummaryDto
        {
            public function __construct() {}
        }

        PHP);
})->with([
    'without suffix' => ['OrderSummary'],
    'with suffix' => ['OrderSummaryDto'],
]);
