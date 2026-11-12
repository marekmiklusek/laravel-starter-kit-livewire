<?php

declare(strict_types=1);

namespace Tests\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\NodeVisitor;
use PhpParser\Node\Identifier;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\NullsafeMethodCall;

final class TransactionBodyCallCollector extends NodeVisitorAbstract
{
    private const array SKIPPED_SUBTREE_METHODS = ['aftercommit', 'transaction'];

    /** @var list<CallLike> */
    private array $calls = [];

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof ClassLike || $this->startsSkippedSubtree($node)) {
            return NodeVisitor::DONT_TRAVERSE_CHILDREN;
        }

        if ($node instanceof CallLike) {
            $this->calls[] = $node;
        }

        return null;
    }

    /**
     * @return list<CallLike>
     */
    public function calls(): array
    {
        return $this->calls;
    }

    private function startsSkippedSubtree(Node $node): bool
    {
        if (! $node instanceof MethodCall && ! $node instanceof NullsafeMethodCall && ! $node instanceof StaticCall) {
            return false;
        }

        return $node->name instanceof Identifier
            && in_array($node->name->toLowerString(), self::SKIPPED_SUBTREE_METHODS, true);
    }
}
