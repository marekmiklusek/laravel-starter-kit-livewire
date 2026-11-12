<?php

declare(strict_types=1);

namespace Tests\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PhpParser\Node\Expr\CallLike;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<CallLike>
 */
final readonly class DatabaseQueryOutsideQueryRule implements Rule
{
    public function __construct(
        private SideEffectCallDetector $sideEffectCallDetector,
        private DelegatingLayerMatcher $delegatingLayerMatcher,
    ) {}

    public function getNodeType(): string
    {
        return CallLike::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $className = $scope->getClassReflection()?->getName();

        if ($className === null || ! $this->delegatingLayerMatcher->matches($className)) {
            return [];
        }

        $databaseQuery = $this->sideEffectCallDetector->describeDatabaseQuery($node, $scope);

        if ($databaseQuery === null) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf('[R1] %s queries the database with %s. Move the query into a query class in app/Queries and call its get() method from here.', $className, $databaseQuery))
                ->identifier('app.databaseQueryOutsideQuery')
                ->build(),
        ];
    }
}
