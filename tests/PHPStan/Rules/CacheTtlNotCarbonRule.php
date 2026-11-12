<?php

declare(strict_types=1);

namespace Tests\PHPStan\Rules;

use PhpParser\Node;
use DateTimeInterface;
use PhpParser\Node\Arg;
use PHPStan\Rules\Rule;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use PhpParser\Node\Identifier;
use PHPStan\Type\VerbosityLevel;
use Illuminate\Cache\RateLimiter;
use PhpParser\Node\Expr\CallLike;
use Illuminate\Cache\CacheManager;
use Illuminate\Support\Facades\URL;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Rules\RuleErrorBuilder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Cache\Repository;
use PhpParser\Node\Expr\NullsafeMethodCall;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Support\Facades\RateLimiter as RateLimiterFacade;

/**
 * @implements Rule<CallLike>
 */
final readonly class CacheTtlNotCarbonRule implements Rule
{
    private const array CACHE_EXPIRATION_PARAMETERS = [
        'remember' => ['ttl', 1],
        'put' => ['ttl', 2],
        'add' => ['ttl', 2],
        'set' => ['ttl', 2],
        'putmany' => ['ttl', 1],
    ];

    private const array RATE_LIMITER_EXPIRATION_PARAMETERS = [
        'hit' => ['decaySeconds', 1],
        'increment' => ['decaySeconds', 1],
        'attempt' => ['decaySeconds', 3],
    ];

    private const array SIGNED_URL_EXPIRATION_PARAMETERS = [
        'temporarysignedroute' => ['expiration', 1],
        'signedroute' => ['expiration', 2],
    ];

    public function getNodeType(): string
    {
        return CallLike::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $expirationParameter = $this->expirationParameter($node, $scope);

        if ($expirationParameter === null) {
            return [];
        }

        [$parameterName, $parameterPosition] = $expirationParameter;

        $expirationArgument = $this->findArgument($node, $parameterName, $parameterPosition);

        if (! $expirationArgument instanceof Arg) {
            return [];
        }

        $expirationType = $scope->getType($expirationArgument->value);

        if (new ObjectType(DateTimeInterface::class)->isSuperTypeOf($expirationType)->yes()) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf('[C1] Expiration must be an inline Carbon expression such as now()->addMinutes(15), %s given.', $expirationType->describe(VerbosityLevel::typeOnly())))
                ->identifier('app.cacheTtlNotCarbon')
                ->build(),
        ];
    }

    /**
     * @return array{string, int}|null
     */
    private function expirationParameter(CallLike $call, Scope $scope): ?array
    {
        if (! $call instanceof MethodCall && ! $call instanceof NullsafeMethodCall && ! $call instanceof StaticCall) {
            return null;
        }

        if (! $call->name instanceof Identifier) {
            return null;
        }

        $methodName = $call->name->toLowerString();

        $parameterGroups = [
            [[Cache::class, Repository::class, CacheManager::class], self::CACHE_EXPIRATION_PARAMETERS],
            [[RateLimiterFacade::class, RateLimiter::class], self::RATE_LIMITER_EXPIRATION_PARAMETERS],
            [[URL::class, UrlGenerator::class], self::SIGNED_URL_EXPIRATION_PARAMETERS],
        ];

        foreach ($parameterGroups as [$targetClassNames, $expirationParameters]) {
            if (isset($expirationParameters[$methodName]) && $this->targetsAnyOf($call, $scope, $targetClassNames)) {
                return $expirationParameters[$methodName];
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $targetClassNames
     */
    private function targetsAnyOf(MethodCall|NullsafeMethodCall|StaticCall $call, Scope $scope, array $targetClassNames): bool
    {
        if ($call instanceof StaticCall) {
            return $call->class instanceof Name && in_array($scope->resolveName($call->class), $targetClassNames, true);
        }

        $calledOnType = $scope->getType($call->var);

        return array_any($targetClassNames, fn (string $targetClassName): bool => new ObjectType($targetClassName)->isSuperTypeOf($calledOnType)->yes());
    }

    private function findArgument(CallLike $call, string $parameterName, int $parameterPosition): ?Arg
    {
        if ($call->isFirstClassCallable()) {
            return null;
        }

        foreach ($call->getArgs() as $argumentPosition => $argument) {
            if ($argument->name instanceof Identifier) {
                if ($argument->name->toString() === $parameterName) {
                    return $argument;
                }

                continue;
            }

            if ($argumentPosition === $parameterPosition) {
                return $argument;
            }
        }

        return null;
    }
}
