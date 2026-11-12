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
final readonly class DatabaseWriteOutsideActionRule implements Rule
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

        $databaseWrite = $this->sideEffectCallDetector->describeDatabaseWrite($node, $scope);

        if ($databaseWrite === null) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf('[A1] %s writes to the database with %s. Move the write into an action in app/Actions and call the action from here.', $className, $databaseWrite))
                ->identifier('app.databaseWriteOutsideAction')
                ->build(),
        ];
    }
}
