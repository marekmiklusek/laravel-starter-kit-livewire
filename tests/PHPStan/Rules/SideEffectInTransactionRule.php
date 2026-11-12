<?php

declare(strict_types=1);

namespace Tests\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PhpParser\NodeTraverser;
use PHPStan\Type\ObjectType;
use PhpParser\Node\Identifier;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\CallLike;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Rules\RuleErrorBuilder;
use PhpParser\Node\Expr\ArrowFunction;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\ConnectionInterface;

/**
 * @implements Rule<CallLike>
 */
final readonly class SideEffectInTransactionRule implements Rule
{
    public function __construct(private SideEffectCallDetector $sideEffectCallDetector) {}

    public function getNodeType(): string
    {
        return CallLike::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->isFirstClassCallable() || ! $this->isTransaction($node, $scope)) {
            return [];
        }

        $transactionCallback = $node->getArgs()[0]->value ?? null;

        if (! $transactionCallback instanceof Closure && ! $transactionCallback instanceof ArrowFunction) {
            return [];
        }

        $transactionBodyCallCollector = new TransactionBodyCallCollector();

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor($transactionBodyCallCollector);
        $nodeTraverser->traverse($transactionCallback->getStmts());

        $errors = [];

        foreach ($transactionBodyCallCollector->calls() as $call) {
            $sideEffect = $this->sideEffectCallDetector->describeExternalSideEffect($call, $scope);

            if ($sideEffect === null) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf('[T1] %s runs inside DB::transaction() and a rollback cannot undo it. Move it after the transaction or defer it with afterCommit().', $sideEffect))
                ->identifier('app.sideEffectInTransaction')
                ->line($call->getStartLine())
                ->build();
        }

        return $errors;
    }

    private function isTransaction(CallLike $call, Scope $scope): bool
    {
        if ($call instanceof StaticCall) {
            return $call->class instanceof Name
                && $call->name instanceof Identifier
                && $call->name->toLowerString() === 'transaction'
                && $scope->resolveName($call->class) === DB::class;
        }

        if (! $call instanceof MethodCall || ! $call->name instanceof Identifier || $call->name->toLowerString() !== 'transaction') {
            return false;
        }

        $calledOnType = $scope->getType($call->var);

        return new ObjectType(ConnectionInterface::class)->isSuperTypeOf($calledOnType)->yes()
            || new ObjectType(DatabaseManager::class)->isSuperTypeOf($calledOnType)->yes();
    }
}
