<?php

declare(strict_types=1);

namespace Tests\PHPStan\Rules;

use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use PhpParser\Node\Identifier;
use Illuminate\Bus\PendingBatch;
use Illuminate\Mail\PendingMail;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Bus;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Foundation\Bus\PendingChain;
use PhpParser\Node\Expr\NullsafeMethodCall;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Notification;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

final readonly class SideEffectCallDetector
{
    private const array DATABASE_WRITE_METHODS = [
        'save', 'savequietly', 'saveorfail', 'savemany',
        'update', 'updatequietly', 'updateorfail', 'updateorcreate', 'updateorinsert', 'updateexistingpivot',
        'upsert', 'insert', 'insertgetid', 'insertorignore', 'insertusing',
        'create', 'createquietly', 'createmany', 'createmanyquietly', 'forcecreate', 'forcecreatequietly', 'firstorcreate', 'createorfirst',
        'delete', 'deletequietly', 'deleteorfail', 'forcedelete', 'forcedeletequietly', 'destroy', 'truncate',
        'restore', 'restorequietly', 'increment', 'decrement', 'incrementeach', 'decrementeach',
        'touch', 'push', 'pushquietly', 'attach', 'detach', 'sync', 'syncwithoutdetaching', 'toggle',
    ];

    private const array DATABASE_WRITE_TARGETS = [
        Model::class,
        EloquentBuilder::class,
        QueryBuilder::class,
        Relation::class,
    ];

    private const array CONNECTION_WRITE_METHODS = [
        'transaction', 'begintransaction', 'commit', 'rollback',
        'insert', 'update', 'delete', 'statement', 'unprepared', 'affectingstatement',
    ];

    private const array CONNECTION_TARGETS = [
        ConnectionInterface::class,
        DatabaseManager::class,
    ];

    private const array HTTP_METHODS = ['get', 'post', 'put', 'patch', 'delete', 'head', 'send', 'pool'];

    private const array DISPATCH_FUNCTIONS = ['dispatch', 'dispatch_sync', 'event', 'broadcast'];

    private const array NOTIFY_METHODS = ['notify', 'notifynow'];

    private const array FACADE_SIDE_EFFECT_METHODS = [
        Http::class => self::HTTP_METHODS,
        Mail::class => ['send', 'sendnow', 'queue', 'later', 'raw', 'html', 'plain'],
        Notification::class => ['send', 'sendnow'],
        Bus::class => ['dispatch', 'dispatchsync', 'dispatchnow', 'dispatchafterresponse'],
        Event::class => ['dispatch', 'until'],
        Queue::class => ['push', 'pushon', 'later', 'lateron', 'bulk'],
    ];

    private const array OBJECT_SIDE_EFFECT_METHODS = [
        PendingRequest::class => self::HTTP_METHODS,
        HttpFactory::class => self::HTTP_METHODS,
        PendingMail::class => ['send', 'sendnow', 'queue', 'later'],
        Mailer::class => ['send', 'sendnow', 'raw', 'html', 'plain', 'queue', 'later'],
        PendingChain::class => ['dispatch'],
        PendingBatch::class => ['dispatch', 'dispatchafterresponse'],
        BusDispatcher::class => ['dispatch', 'dispatchsync', 'dispatchnow'],
        EventDispatcher::class => ['dispatch', 'until'],
    ];

    /**
     * @param  list<string>  $sideEffectClasses
     */
    public function __construct(private array $sideEffectClasses) {}

    public function describeDatabaseWrite(CallLike $call, Scope $scope): ?string
    {
        $methodName = $this->methodName($call);

        if ($methodName === null) {
            return null;
        }

        $normalizedMethodName = mb_strtolower($methodName);

        if ($call instanceof MethodCall || $call instanceof NullsafeMethodCall) {
            $isModelWrite = in_array($normalizedMethodName, self::DATABASE_WRITE_METHODS, true)
                && $this->isCalledOnAnyOf($call, $scope, self::DATABASE_WRITE_TARGETS);

            $isConnectionWrite = in_array($normalizedMethodName, self::CONNECTION_WRITE_METHODS, true)
                && $this->isCalledOnAnyOf($call, $scope, self::CONNECTION_TARGETS);

            return $isModelWrite || $isConnectionWrite ? $methodName.'()' : null;
        }

        if (! $call instanceof StaticCall) {
            return null;
        }

        $className = $this->staticClassName($call, $scope);

        if ($className === null) {
            return null;
        }

        $isConnectionWrite = $className === DB::class
            && in_array($normalizedMethodName, self::CONNECTION_WRITE_METHODS, true);

        $isModelWrite = in_array($normalizedMethodName, self::DATABASE_WRITE_METHODS, true)
            && $this->isSubclassOf($className, Model::class);

        return $isConnectionWrite || $isModelWrite ? $this->shortClassName($className).'::'.$methodName.'()' : null;
    }

    public function describeExternalSideEffect(CallLike $call, Scope $scope): ?string
    {
        $methodName = $this->methodName($call);

        if ($methodName === null) {
            return null;
        }

        $normalizedMethodName = mb_strtolower($methodName);

        if ($call instanceof FuncCall) {
            return in_array($normalizedMethodName, self::DISPATCH_FUNCTIONS, true) ? $methodName.'()' : null;
        }

        if ($call instanceof StaticCall) {
            return $this->describeStaticSideEffect($call, $scope, $methodName, $normalizedMethodName);
        }

        if (! $call instanceof MethodCall && ! $call instanceof NullsafeMethodCall) {
            return null;
        }

        if (in_array($normalizedMethodName, self::NOTIFY_METHODS, true) || $this->isCalledOnAnyOf($call, $scope, $this->sideEffectClasses)) {
            return $methodName.'()';
        }

        foreach (self::OBJECT_SIDE_EFFECT_METHODS as $className => $methodNames) {
            if (in_array($normalizedMethodName, $methodNames, true) && $this->isCalledOnAnyOf($call, $scope, [$className])) {
                return $methodName.'()';
            }
        }

        return null;
    }

    private function describeStaticSideEffect(StaticCall $staticCall, Scope $scope, string $methodName, string $normalizedMethodName): ?string
    {
        $className = $this->staticClassName($staticCall, $scope);

        if ($className === null) {
            return null;
        }

        $facadeMethodNames = self::FACADE_SIDE_EFFECT_METHODS[$className] ?? [];

        $isSideEffect = in_array($className, $this->sideEffectClasses, true)
            || in_array($normalizedMethodName, $facadeMethodNames, true)
            || str_starts_with($normalizedMethodName, 'dispatch');

        return $isSideEffect ? $this->shortClassName($className).'::'.$methodName.'()' : null;
    }

    private function methodName(CallLike $call): ?string
    {
        if ($call instanceof FuncCall) {
            return $call->name instanceof Name ? $call->name->getLast() : null;
        }

        if ($call instanceof MethodCall || $call instanceof NullsafeMethodCall || $call instanceof StaticCall) {
            return $call->name instanceof Identifier ? $call->name->toString() : null;
        }

        return null;
    }

    private function staticClassName(StaticCall $staticCall, Scope $scope): ?string
    {
        return $staticCall->class instanceof Name ? $scope->resolveName($staticCall->class) : null;
    }

    /**
     * @param  list<string>  $classNames
     */
    private function isCalledOnAnyOf(MethodCall|NullsafeMethodCall $methodCall, Scope $scope, array $classNames): bool
    {
        $calledOnType = $scope->getType($methodCall->var);

        return array_any($classNames, fn (string $className): bool => new ObjectType($className)->isSuperTypeOf($calledOnType)->yes());
    }

    private function isSubclassOf(string $className, string $parentClassName): bool
    {
        return new ObjectType($parentClassName)->isSuperTypeOf(new ObjectType($className))->yes();
    }

    private function shortClassName(string $className): string
    {
        $namespaceSeparatorPosition = mb_strrpos($className, '\\');

        return $namespaceSeparatorPosition === false ? $className : mb_substr($className, $namespaceSeparatorPosition + 1);
    }
}
