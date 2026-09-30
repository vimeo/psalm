<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Context;
use Psalm\IssueBuffer;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class Php85Test extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * Purity of a call result is only tracked while unused variables are being searched for.
     */
    public function testPipeResultIsPureCompatible(): void
    {
        $this->project_analyzer->setPhpVersion('8.5', 'tests');
        $codebase = $this->project_analyzer->getCodebase();
        $codebase->find_unused_variables = true;
        $codebase->config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                final class Box
                {
                    public int $value = 0;

                    /** @psalm-external-mutation-free */
                    public function set(int $x): int
                    {
                        $this->value = $x;
                        return $x;
                    }
                }

                /** @psalm-pure */
                function makeBox(int $_x): Box
                {
                    return new Box();
                }

                /** @psalm-pure */
                function fresh(): int
                {
                    return (1 |> makeBox(...))->set(2);
                }
            ',
        );

        $this->analyzeFile('somefile.php', new Context());

        $this->assertSame([], IssueBuffer::getIssuesData()['somefile.php'] ?? []);
    }

    #[Override]
    public function providerValidCodeParse(): iterable
    {
        return [
            'pipeOperator' => [
                'code' => '<?php
                    /**
                     * @param array<string, string> $array
                     * @return list<string>
                     * @psalm-pure
                     */
                    function foo(array $array): array
                    {
                        return $array |> array_keys(...);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'pipeOperatorInfersReturnType' => [
                'code' => '<?php
                    $x = \'abc\' |> strlen(...);',
                'assertions' => [
                    '$x===' => 'int<1, max>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'pipeOperatorWithClosureVariable' => [
                'code' => '<?php
                    $f = static fn(int $i): string => (string) $i;
                    $x = 5 |> $f;',
                'assertions' => [
                    '$x===' => 'numeric-string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'pipeOperatorChained' => [
                'code' => '<?php
                    /** @var array<string, int> $arr */
                    $x = $arr |> array_keys(...) |> count(...);',
                'assertions' => [
                    '$x===' => 'int<0, max>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'pipeOperatorWithMethodFirstClassCallable' => [
                'code' => '<?php
                    class A
                    {
                        public function double(int $i): string
                        {
                            return (string) ($i * 2);
                        }
                    }

                    $x = 2 |> (new A())->double(...);',
                'assertions' => [
                    '$x===' => 'string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'pipeOperatorWithStaticMethodFirstClassCallable' => [
                'code' => '<?php
                    class A
                    {
                        public static function twice(int $i): string
                        {
                            return (string) ($i * 2);
                        }
                    }

                    $x = 2 |> A::twice(...);',
                'assertions' => [
                    '$x===' => 'string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'pipeOperatorPreservesTemplatedReturn' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param list<T> $l
                     * @return T
                     */
                    function first(array $l)
                    {
                        return $l[0];
                    }

                    $list = [1, 2, 3];
                    $x = $list |> first(...);',
                'assertions' => [
                    '$x===' => '1|2|3',
                ],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'pipeOperatorEvaluatesLeftHandSideFirst' => [
                'code' => '<?php
                    final class A
                    {
                        public function id(self $a): self
                        {
                            return $a;
                        }
                    }

                    $x = ($o = new A()) |> $o->id(...);',
                'assertions' => [
                    '$x===' => 'A',
                ],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'pipeOperatorByRefParameterDoesNotModifyLeftHandSide' => [
                'code' => '<?php
                    /**
                     * @param non-empty-list<int> $a
                     * @return non-empty-list<int>
                     */
                    function f(array $a): array
                    {
                        /** @psalm-suppress InvalidPassByReference */
                        $a |> array_pop(...);
                        return $a;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'pipeOperatorPreferRefParameterDoesNotModifyLeftHandSide' => [
                'code' => '<?php
                    $a = [2, 1];
                    $sorted = $a |> array_multisort(...);',
                'assertions' => [
                    '$a===' => 'list{2, 1}',
                    '$sorted===' => 'true',
                ],
                'ignored_issues' => ['InvalidArgument'],
                'php_version' => '8.5',
            ],
            'pipeOperatorReferenceReturningCallToByRefParameter' => [
                'code' => '<?php
                    function &refret(int $x): int
                    {
                        static $n = 0;
                        $n = $x;
                        return $n;
                    }

                    function set(int &$x): void
                    {
                        $x = 3;
                    }

                    set(1 |> refret(...));',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'pipeOperatorTypesClosureFromParameter' => [
                'code' => '<?php
                    /** @param Closure(int): int $f */
                    function apply(Closure $f): int
                    {
                        return $f(1);
                    }

                    $x = (fn($i) => $i + 1) |> apply(...);',
                'assertions' => [
                    '$x===' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'pipeOperatorNarrowsPipedVariable' => [
                'code' => '<?php
                    /** @psalm-assert string $x */
                    function assertString(mixed $x): void
                    {
                        if (!is_string($x)) {
                            throw new RuntimeException();
                        }
                    }

                    function checked(?string $s): int
                    {
                        if ($s |> is_string(...)) {
                            return strlen($s);
                        }
                        return 0;
                    }

                    function asserted(mixed $m): int
                    {
                        $m |> assertString(...);
                        return strlen($m);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
        ];
    }

    #[Override]
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'pipeOperatorTooFewArguments' => [
                'code' => '<?php
                    $x = \'a\' |> str_repeat(...);',
                'error_message' => 'TooFewArguments',
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'pipeOperatorInvalidArgumentType' => [
                'code' => '<?php
                    /** @param int $i */
                    function f(int $i): void {}

                    \'abc\' |> f(...);',
                'error_message' => 'InvalidScalarArgument',
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'pipeOperatorNonCallableRhs' => [
                'code' => '<?php
                    $x = 5 |> 6;',
                'error_message' => 'InvalidFunctionCall',
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'pipeOperatorUndefinedFunction' => [
                'code' => '<?php
                    $x = 5 |> nonExistentFn(...);',
                'error_message' => 'UndefinedFunction',
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'pipeOperatorByRefParameter' => [
                'code' => '<?php
                    $a = [3, 1, 2];
                    $b = $a |> sort(...);',
                'error_message' => 'InvalidPassByReference',
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'pipeOperatorByRefParameterUndefinedLeftHandSide' => [
                'code' => '<?php
                    $b = $undefined |> sort(...);',
                'error_message' => 'UndefinedGlobalVariable',
                'ignored_issues' => ['InvalidPassByReference'],
                'php_version' => '8.5',
            ],
            'pipeOperatorNoNarrowingWhenRhsReassignsPipedVariable' => [
                'code' => '<?php
                    /** @param-out null $s */
                    function clear(?string &$s): Closure
                    {
                        $s = null;
                        return is_string(...);
                    }

                    function f(?string $s): int
                    {
                        if ($s |> clear($s)) {
                            return strlen($s);
                        }
                        return 0;
                    }',
                'error_message' => 'NullArgument',
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'pipeOperatorRequiresPhp85' => [
                'code' => '<?php
                    $x = \'abc\' |> strlen(...);',
                'error_message' => 'ParseError',
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
        ];
    }
}
