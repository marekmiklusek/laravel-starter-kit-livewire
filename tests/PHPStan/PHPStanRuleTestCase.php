<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use LogicException;
use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @template TRule of Rule<covariant Node>
 *
 * @extends RuleTestCase<TRule>
 */
abstract class PHPStanRuleTestCase extends RuleTestCase
{
    /** @var TRule|null */
    private ?Rule $rule = null;

    /**
     * @param  TRule  $rule
     * @param  list<array{0: string, 1: int}>  $expectedErrors
     */
    final public function assertRuleErrors(Rule $rule, string $code, array $expectedErrors): void
    {
        $this->rule = $rule;

        $analysedFilePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'phpstan-rule-'.bin2hex(random_bytes(8)).'.php';

        file_put_contents($analysedFilePath, $code);

        try {
            $this->analyse([$analysedFilePath], $expectedErrors);
        } finally {
            unlink($analysedFilePath);
        }
    }

    /**
     * @return TRule
     */
    protected function getRule(): Rule
    {
        return $this->rule ?? throw new LogicException('Call assertRuleErrors() before analysing.');
    }
}
