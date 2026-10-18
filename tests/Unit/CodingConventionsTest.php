<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @return array<string, list<PhpToken>>
 */
function tokenizedPhpFiles(string ...$directories): array
{
    $files = [];

    foreach ($directories as $directory) {
        foreach (File::allFiles(base_path($directory)) as $file) {
            $path = $directory.'/'.str_replace('\\', '/', $file->getRelativePathname());

            if ($file->getExtension() !== 'php' || str_starts_with($path, 'bootstrap/cache/')) {
                continue;
            }

            $files[$path] = array_values(array_filter(
                PhpToken::tokenize($file->getContents()),
                fn (PhpToken $token): bool => ! $token->isIgnorable(),
            ));
        }
    }

    return $files;
}

it('reads config only through typed accessors', function (): void {
    $violations = [];

    foreach (tokenizedPhpFiles('app', 'bootstrap', 'config', 'database', 'routes', 'tests') as $path => $tokens) {
        foreach ($tokens as $index => $token) {
            $isUntypedConfigRead = $token->is(['config', '\config'])
                && ($tokens[$index + 1] ?? null)?->is('(')
                && ($tokens[$index + 2] ?? null)?->is(T_CONSTANT_ENCAPSED_STRING)
                && ! ($tokens[$index - 1] ?? null)?->is([T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION]);

            if ($isUntypedConfigRead) {
                $violations[] = "{$path}:{$token->line}";
            }
        }
    }

    expect($violations)->toBeEmpty(implode(PHP_EOL, $violations));
});

it('defines no default column values in migrations', function (): void {
    $violations = [];

    foreach (tokenizedPhpFiles('database/migrations') as $path => $tokens) {
        foreach ($tokens as $index => $token) {
            $isDefaultCall = $token->is('default')
                && ($tokens[$index - 1] ?? null)?->is([T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR])
                && ($tokens[$index + 1] ?? null)?->is('(');

            if ($isDefaultCall) {
                $violations[] = "{$path}:{$token->line}";
            }
        }
    }

    expect($violations)->toBeEmpty(implode(PHP_EOL, $violations));
});

it('uses no mocks in tests', function (): void {
    $mockingMethods = [
        'mock',
        'partialMock',
        'spy',
        'shouldReceive',
        'shouldNotReceive',
        'shouldHaveReceived',
        'createMock',
        'createStub',
        'createPartialMock',
        'createConfiguredMock',
        'getMockBuilder',
    ];

    $violations = [];

    foreach (tokenizedPhpFiles('tests') as $path => $tokens) {
        foreach ($tokens as $index => $token) {
            $name = mb_ltrim($token->text, '\\');

            $isMockingClass = $token->is([T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED])
                && (str_starts_with($name, 'Mockery') || str_starts_with($name, 'PHPUnit\\Framework\\MockObject'));

            $isMockingCall = $token->is(T_STRING)
                && in_array($token->text, $mockingMethods, true)
                && ($tokens[$index + 1] ?? null)?->is('(')
                && ! ($tokens[$index - 1] ?? null)?->is(T_FUNCTION);

            if ($isMockingClass || $isMockingCall) {
                $violations[] = "{$path}:{$token->line}";
            }
        }
    }

    expect($violations)->toBeEmpty(implode(PHP_EOL, $violations));
});

it('injects action dependencies through private promoted properties', function (): void {
    $violations = collect(File::allFiles(app_path('Actions')))
        ->map(fn (SplFileInfo $file): string => 'App\\Actions\\'.str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname()))
        ->reject(fn (string $class): bool => str_starts_with($class, 'App\\Actions\\Fortify\\'))
        ->flatMap(fn (string $class): array => class_exists($class) ? array_map(
            fn (ReflectionProperty $property): string => "{$class}::\${$property->getName()}",
            array_filter(
                new ReflectionClass($class)->getProperties(),
                fn (ReflectionProperty $property): bool => ! $property->isPrivate() || ! $property->isPromoted(),
            ),
        ) : [])
        ->values()
        ->all();

    expect($violations)->toBeEmpty(implode(PHP_EOL, $violations));
});
