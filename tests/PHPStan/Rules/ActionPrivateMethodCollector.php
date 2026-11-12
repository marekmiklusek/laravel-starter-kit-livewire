<?php

declare(strict_types=1);

namespace Tests\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PhpParser\Node\Expr\Closure;
use PHPStan\Node\InClassMethodNode;
use PhpParser\PrettyPrinter\Standard;

/**
 * @implements Collector<InClassMethodNode, array{string, string, string, int}>
 */
final readonly class ActionPrivateMethodCollector implements Collector
{
    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }

    public function processNode(Node $node, Scope $scope): ?array
    {
        $className = $node->getClassReflection()->getName();
        $classMethod = $node->getOriginalNode();

        if (! $this->isActionClass($className) || $scope->isInTrait() || ! $classMethod->isPrivate() || in_array($classMethod->stmts, [null, []], true)) {
            return null;
        }

        $methodBody = new Standard()->prettyPrintExpr(new Closure([
            'params' => $classMethod->params,
            'returnType' => $classMethod->returnType,
            'stmts' => $classMethod->stmts,
        ]));

        return [
            $className,
            $classMethod->name->toString(),
            sha1($methodBody),
            $classMethod->getStartLine(),
        ];
    }

    private function isActionClass(string $className): bool
    {
        return str_starts_with($className, 'App\\Actions\\') && ! str_starts_with($className, 'App\\Actions\\Fortify\\');
    }
}
