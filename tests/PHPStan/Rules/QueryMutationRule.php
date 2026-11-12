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
final readonly class QueryMutationRule implements Rule
{
    public function __construct(private SideEffectCallDetector $sideEffectCallDetector) {}

    public function getNodeType(): string
    {
        return CallLike::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $className = $scope->getClassReflection()?->getName();

        if ($className === null || ! str_starts_with($className, 'App\\Queries\\')) {
            return [];
        }

        $stateChange = $this->sideEffectCallDetector->describeDatabaseWrite($node, $scope)
            ?? $this->sideEffectCallDetector->describeExternalSideEffect($node, $scope);

        if ($stateChange === null) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf('[Q1] %s only reads data, but %s changes state. Move the change into an action in app/Actions.', $className, $stateChange))
                ->identifier('app.queryMutation')
                ->build(),
        ];
    }
}
