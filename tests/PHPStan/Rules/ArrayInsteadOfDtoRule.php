<?php

declare(strict_types=1);

namespace Tests\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Type\Type;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Type\TypeCombinator;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<InClassMethodNode>
 */
final readonly class ArrayInsteadOfDtoRule implements Rule
{
    private const array LAYER_NAMESPACES = ['App\\Actions', 'App\\Queries'];

    private const array EXCLUDED_NAMESPACES = ['App\\Actions\\Fortify'];

    /**
     * @param  list<string>  $apiResponseNamespaces
     */
    public function __construct(private array $apiResponseNamespaces) {}

    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $className = $node->getClassReflection()->getName();
        $methodReflection = $node->getMethodReflection();

        if (! $methodReflection->isPublic() || $methodReflection->getName() === '__construct' || $this->isInNamespaces($className, self::EXCLUDED_NAMESPACES)) {
            return [];
        }

        $isLayerClass = $this->isInNamespaces($className, self::LAYER_NAMESPACES);

        if (! $isLayerClass && ! $this->isInNamespaces($className, $this->apiResponseNamespaces)) {
            return [];
        }

        $methodName = $className.'::'.$methodReflection->getName().'()';
        $methodVariant = $methodReflection->getOnlyVariant();
        $errors = [];

        if ($this->isAssociativeArray($methodVariant->getReturnType())) {
            $errors[] = RuleErrorBuilder::message(sprintf('[D1] %s returns an associative array. Return a DTO from app/Dtos, a model, a collection or a list<...> instead.', $methodName))
                ->identifier('app.arrayInsteadOfDto')
                ->build();
        }

        if (! $isLayerClass) {
            return $errors;
        }

        $parameterNodes = $node->getOriginalNode()->getParams();

        foreach ($methodVariant->getParameters() as $parameterPosition => $parameterReflection) {
            if (! $this->isAssociativeArray($parameterReflection->getType())) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf('[D1] Parameter $%s of %s is an associative array. Pass a DTO from app/Dtos instead.', $parameterReflection->getName(), $methodName))
                ->identifier('app.arrayInsteadOfDto')
                ->line(($parameterNodes[$parameterPosition] ?? $node->getOriginalNode())->getStartLine())
                ->build();
        }

        return $errors;
    }

    private function isAssociativeArray(Type $type): bool
    {
        $typeWithoutNull = TypeCombinator::removeNull($type);

        return $typeWithoutNull->isArray()->yes() && ! $typeWithoutNull->isList()->yes();
    }

    /**
     * @param  list<string>  $namespaces
     */
    private function isInNamespaces(string $className, array $namespaces): bool
    {
        foreach ($namespaces as $namespace) {
            if (str_starts_with($className, mb_rtrim($namespace, '\\').'\\')) {
                return true;
            }
        }

        return false;
    }
}
