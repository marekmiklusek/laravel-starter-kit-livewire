<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Finder\SplFileInfo;

it('declares every column in casts and as property-read', function (): void {
    $violations = collect(File::allFiles(app_path('Models')))
        ->map(fn (SplFileInfo $file): string => 'App\\Models\\'.str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname()))
        ->filter(fn (string $class): bool => is_subclass_of($class, Model::class) && ! new ReflectionClass($class)->isAbstract())
        ->flatMap(function (string $class): array {
            /** @var Model $model */
            $model = new $class;

            $columns = $model->getConnection()->getSchemaBuilder()->getColumnListing($model->getTable());

            /** @var array<string, mixed> $casts */
            $casts = new ReflectionMethod($model, 'casts')->invoke($model);

            preg_match_all('/@property-read\s+.+?\s+\$(\w+)/', (string) new ReflectionClass($class)->getDocComment(), $matches);

            return [
                ...array_map(fn (string $column): string => "{$class}::casts() is missing {$column}", array_values(array_diff($columns, array_keys($casts)))),
                ...array_map(fn (string $column): string => "{$class} @property-read is missing {$column}", array_values(array_diff($columns, $matches[1]))),
            ];
        })
        ->values()
        ->all();

    expect($violations)->toBeEmpty(implode(PHP_EOL, $violations));
});
