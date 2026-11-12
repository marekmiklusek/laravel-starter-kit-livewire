<?php

declare(strict_types=1);

namespace Tests\PHPStan\Rules;

final readonly class DelegatingLayerMatcher
{
    private const array DELEGATING_LAYER_NAMESPACES = [
        'App\\Livewire',
        'App\\Http',
        'App\\Console\\Commands',
        'App\\Jobs',
        'App\\Listeners',
    ];

    public function matches(string $className): bool
    {
        foreach (self::DELEGATING_LAYER_NAMESPACES as $namespace) {
            if (str_starts_with($className, $namespace.'\\')) {
                return true;
            }
        }

        return false;
    }
}
