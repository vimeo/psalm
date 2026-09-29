<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class Php85Test extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

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
