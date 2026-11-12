<?php

declare(strict_types=1);

namespace Tests\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<CollectedDataNode>
 */
final readonly class DuplicatedActionPrivateMethodRule implements Rule
{
    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $methodsByBodyHash = [];

        foreach ($node->get(ActionPrivateMethodCollector::class) as $filePath => $collectedMethods) {
            foreach ($collectedMethods as [$className, $methodName, $bodyHash, $line]) {
                $methodsByBodyHash[$bodyHash][] = [$filePath, $className, $methodName, $line];
            }
        }

        $errors = [];

        foreach ($methodsByBodyHash as $duplicatedMethods) {
            $classNames = array_values(array_unique(array_map(fn (array $duplicatedMethod): string => $duplicatedMethod[1], $duplicatedMethods)));

            if (count($classNames) < 2) {
                continue;
            }

            foreach ($duplicatedMethods as [$filePath, $className, $methodName, $line]) {
                $otherClassNames = array_values(array_diff($classNames, [$className]));

                $errors[] = RuleErrorBuilder::message(sprintf('[P1] Private method %s::%s() has the same body as a private method in %s. Extract it into its own action in app/Actions and inject it.', $className, $methodName, implode(', ', $otherClassNames)))
                    ->identifier('app.duplicatedActionPrivateMethod')
                    ->file($filePath)
                    ->line($line)
                    ->build();
            }
        }

        return $errors;
    }
}
