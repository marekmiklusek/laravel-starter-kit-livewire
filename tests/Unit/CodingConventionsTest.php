<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Finder\SplFileInfo;
use Illuminate\Database\Eloquent\Attributes\Scope;

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

/**
 * @return list<ReflectionClass<object>>
 */
function appClassReflections(): array
{
    $classReflections = [];

    foreach (File::allFiles(app_path()) as $file) {
        $className = 'App\\'.str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());

        if (class_exists($className) || trait_exists($className) || interface_exists($className)) {
            $classReflections[] = new ReflectionClass($className);
        }
    }

    return $classReflections;
}

function isDateParameterType(string $className): bool
{
    return is_a($className, DateTimeInterface::class, true);
}

/**
 * @return list<string>
 */
function testFilePaths(): array
{
    $testFilePaths = [];

    foreach (['Feature', 'Unit'] as $suite) {
        foreach (File::allFiles(base_path("tests/{$suite}")) as $file) {
            $testFilePaths[] = $suite.'/'.str_replace('\\', '/', $file->getRelativePathname());
        }
    }

    return $testFilePaths;
}

/**
 * @param  list<PhpToken>  $tokens
 * @return list<list<PhpToken>>
 */
function arrayElements(array $tokens, int $openingIndex): array
{
    $elements = [];
    $element = [];
    $depth = 0;

    foreach (array_slice($tokens, $openingIndex + 1) as $token) {
        if ($depth === 0 && $token->is([']', ','])) {
            if ($element !== []) {
                $elements[] = $element;
            }

            if ($token->is(']')) {
                break;
            }

            $element = [];

            continue;
        }

        if ($token->is(['[', '(', '{'])) {
            $depth++;
        }

        if ($token->is([']', ')', '}'])) {
            $depth--;
        }

        $element[] = $token;
    }

    return $elements;
}

/**
 * @param  list<PhpToken>  $tokens
 */
function isPlainInstantiation(array $tokens, int $classIndex): bool
{
    $depth = 0;

    foreach (array_slice($tokens, $classIndex + 1) as $token) {
        if ($token->is('(')) {
            $depth++;

            continue;
        }

        if ($token->is(')')) {
            if ($depth === 0) {
                return true;
            }

            $depth--;

            continue;
        }

        if ($depth === 0) {
            return $token->is([';', ',', ']']);
        }
    }

    return false;
}

/**
 * @param  array<int, string>  $rules
 */
function hasBothSizeLimits(array $rules): bool
{
    $hasMinimum = collect($rules)->contains(fn (string $rule): bool => Str::startsWith($rule, ['min:', 'between:', 'size:']));

    $hasMaximum = collect($rules)->contains(fn (string $rule): bool => Str::startsWith($rule, ['max:', 'between:', 'size:']));

    return $hasMinimum && $hasMaximum;
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

it('defines no down method in migrations', function (): void {
    $violations = [];

    foreach (tokenizedPhpFiles('database/migrations') as $path => $tokens) {
        foreach ($tokens as $index => $token) {
            $isDownMethod = $token->is(T_FUNCTION)
                && ($tokens[$index + 1] ?? null)?->is('down');

            if ($isDownMethod) {
                $violations[] = "{$path}:{$token->line}";
            }
        }
    }

    expect($violations)->toBeEmpty(implode(PHP_EOL, $violations));
});

it('names form requests after the controller method they serve', function (): void {
    $violations = collect(File::allFiles(app_path('Http/Requests')))
        ->reject(fn (SplFileInfo $file): bool => preg_match('/^(List|Show|Create|Update|Delete)[A-Z]\w*Request$/', $file->getFilenameWithoutExtension()) === 1)
        ->map(fn (SplFileInfo $file): string => 'app/Http/Requests/'.str_replace('\\', '/', $file->getRelativePathname()))
        ->values()
        ->all();

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

it('uses no single letter variables', function (): void {
    $violations = [];

    foreach (tokenizedPhpFiles('app', 'bootstrap', 'config', 'database', 'routes', 'tests') as $path => $tokens) {
        foreach ($tokens as $token) {
            if ($token->is(T_VARIABLE) && mb_strlen($token->text) === 2) {
                $violations[] = "{$path}:{$token->line} {$token->text}";
            }
        }
    }

    expect($violations)->toBeEmpty(implode(PHP_EOL, $violations));
});

it('names class typed parameters after their class', function (): void {
    $violations = [];

    foreach (appClassReflections() as $classReflection) {
        foreach ($classReflection->getMethods() as $methodReflection) {
            if ($methodReflection->getFileName() !== $classReflection->getFileName()) {
                continue;
            }

            $parameterClassNames = collect($methodReflection->getParameters())
                ->mapWithKeys(fn (ReflectionParameter $parameterReflection): array => [
                    $parameterReflection->getName() => $parameterReflection->getType() instanceof ReflectionNamedType && ! $parameterReflection->getType()->isBuiltin()
                        ? $parameterReflection->getType()->getName()
                        : null,
                ])
                ->filter(fn (?string $className): bool => $className !== null && ! in_array($className, [Closure::class, 'self', 'static', 'parent'], true) && ! isDateParameterType($className));

            $sharedClassNames = $parameterClassNames->duplicates();

            foreach ($parameterClassNames as $parameterName => $className) {
                $expectedName = lcfirst(class_basename((string) $className));

                if ($parameterName !== $expectedName && $sharedClassNames->doesntContain($className)) {
                    $violations[] = "{$classReflection->getName()}::{$methodReflection->getName()}() \${$parameterName} should be \${$expectedName}";
                }
            }
        }
    }

    expect($violations)->toBeEmpty(implode(PHP_EOL, $violations));
});

it('names variables holding a new instance after its class', function (): void {
    $violations = [];

    foreach (tokenizedPhpFiles('app', 'database', 'routes', 'tests') as $path => $tokens) {
        foreach ($tokens as $index => $token) {
            $classToken = $tokens[$index + 3] ?? null;

            $isNewInstanceAssignment = $token->is(T_VARIABLE)
                && ($tokens[$index + 1] ?? null)?->is('=')
                && ($tokens[$index + 2] ?? null)?->is(T_NEW)
                && $classToken?->is([T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED])
                && ! $classToken->is(['self', 'static', 'parent'])
                && isPlainInstantiation($tokens, $index + 3);

            if (! $isNewInstanceAssignment) {
                continue;
            }

            $expectedName = '$'.lcfirst(class_basename($classToken->text));

            if ($token->text !== $expectedName) {
                $violations[] = "{$path}:{$token->line} {$token->text} should be {$expectedName}";
            }
        }
    }

    expect($violations)->toBeEmpty(implode(PHP_EOL, $violations));
});

it('keeps action, dto and enum directories flat', function (): void {
    $basePath = str_replace('\\', '/', base_path()).'/';

    $violations = [];

    foreach (['Actions', 'Dtos', 'Enums'] as $directory) {
        foreach (glob(app_path($directory).'/*', GLOB_ONLYDIR) ?: [] as $subdirectory) {
            $relativePath = Str::after(str_replace('\\', '/', $subdirectory), $basePath);

            if ($relativePath !== 'app/Actions/Fortify') {
                $violations[] = $relativePath;
            }
        }
    }

    expect($violations)->toBeEmpty(implode(PHP_EOL, $violations));
});

it('defines no cache ttl as a class constant', function (): void {
    $violations = [];

    foreach (tokenizedPhpFiles('app') as $path => $tokens) {
        foreach ($tokens as $index => $token) {
            if (! $token->is(T_CONST)) {
                continue;
            }

            foreach (array_slice($tokens, $index + 1) as $constantToken) {
                if ($constantToken->is('=')) {
                    break;
                }

                if ($constantToken->is(T_STRING) && str_contains(mb_strtoupper($constantToken->text), 'TTL')) {
                    $violations[] = "{$path}:{$constantToken->line} {$constantToken->text}";
                }
            }
        }
    }

    expect($violations)->toBeEmpty(implode(PHP_EOL, $violations));
});

it('limits sized validation rules with both min and max', function (): void {
    $sizedTypes = ['string', 'integer', 'numeric', 'array', 'file'];

    $violations = [];

    foreach (tokenizedPhpFiles('app') as $path => $tokens) {
        foreach ($tokens as $index => $token) {
            if (! $token->is('[')) {
                continue;
            }

            $elements = collect(arrayElements($tokens, $index));

            $isList = $elements->flatten()->doesntContain(fn (PhpToken $elementToken): bool => $elementToken->is(T_DOUBLE_ARROW));

            $ruleSets = $isList
                ? [$elements->filter(fn (array $element): bool => count($element) === 1 && $element[0]->is(T_CONSTANT_ENCAPSED_STRING))->map(fn (array $element): string => $element[0]->text)->all()]
                : $elements->filter(fn (array $element): bool => count($element) === 3 && $element[2]->is(T_CONSTANT_ENCAPSED_STRING) && str_contains($element[2]->text, '|'))->map(fn (array $element): array => [$element[2]->text])->all();

            $hasRuleObjectWithOwnLimits = $elements->flatten()->contains(fn (PhpToken $elementToken): bool => str_contains($elementToken->text, 'Password'));

            foreach ($ruleSets as $literals) {
                $rules = collect($literals)
                    ->flatMap(fn (string $literal): array => explode('|', mb_substr($literal, 1, -1)))
                    ->values()
                    ->all();

                $ruleNames = array_map(fn (string $rule): string => Str::before($rule, ':'), $rules);

                $isSized = array_intersect($ruleNames, $sizedTypes) !== [];

                $isExempt = $hasRuleObjectWithOwnLimits || in_array('current_password', $ruleNames, true);

                if ($isSized && ! $isExempt && ! hasBothSizeLimits($rules)) {
                    $violations[] = "{$path}:{$token->line} ".implode('|', $rules);
                }
            }
        }
    }

    expect($violations)->toBeEmpty(implode(PHP_EOL, $violations));
});

it('gives no class an ambiguous bare name', function (): void {
    $violations = collect(appClassReflections())
        ->map(fn (ReflectionClass $classReflection): string => $classReflection->getName())
        ->filter(fn (string $className): bool => in_array(class_basename($className), ['Config', 'Item', 'Result', 'Rules', 'Data', 'Service', 'Manager', 'Helper', 'Handler', 'Settings', 'Options', 'Payload', 'Response', 'Status', 'Type'], true))
        ->values()
        ->all();

    expect($violations)->toBeEmpty(implode(PHP_EOL, $violations));
});

it('declares protected model methods only as scopes', function (): void {
    $violations = [];

    foreach (appClassReflections() as $classReflection) {
        if (! $classReflection->isSubclassOf(Model::class)) {
            continue;
        }

        foreach ($classReflection->getMethods(ReflectionMethod::IS_PROTECTED) as $methodReflection) {
            $isOwnMethod = $methodReflection->getFileName() === $classReflection->getFileName();

            if ($isOwnMethod && $methodReflection->getAttributes(Scope::class) === []) {
                $violations[] = "{$classReflection->getName()}::{$methodReflection->getName()}()";
            }
        }
    }

    expect($violations)->toBeEmpty(implode(PHP_EOL, $violations));
});

it('names enum cases in title case', function (): void {
    $violations = [];

    foreach (appClassReflections() as $classReflection) {
        foreach ($classReflection->getReflectionConstants() as $constantReflection) {
            $caseName = $constantReflection->getName();

            $isTitleCase = preg_match('/^[A-Z][a-zA-Z0-9]*$/', $caseName) === 1
                && preg_match('/[A-Z]{2}/', $caseName) !== 1;

            if ($constantReflection->isEnumCase() && ! $isTitleCase) {
                $violations[] = "{$classReflection->getName()}::{$caseName}";
            }
        }
    }

    expect($violations)->toBeEmpty(implode(PHP_EOL, $violations));
});

it('places class tests at the path mirroring app', function (): void {
    $appClassPaths = collect(appClassReflections())
        ->map(fn (ReflectionClass $classReflection): string => str_replace('\\', '/', Str::after($classReflection->getName(), 'App\\')))
        ->groupBy(fn (string $classPath): string => class_basename($classPath));

    $violations = [];

    foreach (testFilePaths() as $testFilePath) {
        if (! str_ends_with($testFilePath, 'Test.php')) {
            continue;
        }

        $suite = Str::before($testFilePath, '/');
        $testedClassPath = Str::beforeLast(Str::after($testFilePath, '/'), 'Test.php');

        /** @var list<string> $matchingClassPaths */
        $matchingClassPaths = $appClassPaths->get(class_basename($testedClassPath), collect())->all();

        if ($matchingClassPaths !== [] && ! in_array($testedClassPath, $matchingClassPaths, true)) {
            $violations[] = "tests/{$testFilePath} should be tests/{$suite}/{$matchingClassPaths[0]}Test.php";
        }
    }

    expect($violations)->toBeEmpty(implode(PHP_EOL, $violations));
});

it('gives every class in tested directories a test at the mirrored path', function (): void {
    $testedDirectories = ['Actions/', 'Queries/', 'Console/Commands/', 'Http/Controllers/', 'Http/Middleware/', 'Livewire/', 'Jobs/', 'Listeners/', 'Services/', 'Models/'];

    $testFilePaths = testFilePaths();

    $violations = collect(appClassReflections())
        ->reject(fn (ReflectionClass $classReflection): bool => $classReflection->isTrait() || $classReflection->isInterface() || $classReflection->isEnum() || $classReflection->isAbstract())
        ->map(fn (ReflectionClass $classReflection): string => str_replace('\\', '/', Str::after($classReflection->getName(), 'App\\')))
        ->filter(fn (string $classPath): bool => Str::startsWith($classPath, $testedDirectories))
        ->reject(fn (string $classPath): bool => array_intersect(["Feature/{$classPath}Test.php", "Unit/{$classPath}Test.php"], $testFilePaths) !== [])
        ->map(fn (string $classPath): string => "app/{$classPath}.php has no tests/Feature/{$classPath}Test.php or tests/Unit/{$classPath}Test.php")
        ->values()
        ->all();

    expect($violations)->toBeEmpty(implode(PHP_EOL, $violations));
});
