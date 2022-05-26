<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class ParamValidationTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{code:string,assertions?:array<string,string>,ignored_issues?:list<string>,php_version?:string}>
     */
    public function providerValidCodeParse(): iterable
    {
        yield 'functionCheckMixedIsInt' => [
            'code' => '<?php
                /**
                 * @psalm-require-param-validation
                 * @param int $bar
                 */
                function foo($bar): void
                {
                    if (!is_int($bar)) {
                        throw new \InvalidArgumentException();
                    }
                    requiresInt($bar);
                }

                function requiresInt(int $_int): void {}
            ',
        ];
        yield 'functionPhpTypeIsInt' => [
            'code' => '<?php
                /**
                 * @psalm-require-param-validation
                 */
                function foo(int $bar): void
                {
                    requiresInt($bar);
                }

                function requiresInt(int $_int): void {}
            ',
        ];
        yield 'methodCheckMixedIsInt' => [
            'code' => '<?php
                class Foo
                {
                    /**
                     * @psalm-require-param-validation
                     * @param int $bar
                     */
                    public function foo($bar): void
                    {
                        if (!is_int($bar)) {
                            throw new \InvalidArgumentException();
                        }
                        requiresInt($bar);
                    }
                }

                function requiresInt(int $_int): void {}
            ',
        ];
        yield 'methodPhpTypeIsInt' => [
            'code' => '<?php
                class Foo
                {
                    /**
                     * @psalm-require-param-validation
                     */
                    public function foo(int $bar): void
                    {
                        requiresInt($bar);
                    }
                }

                function requiresInt(int $_int): void {}
            ',
        ];
    }

    /**
     * @return iterable<string,array{code:string,error_message:string,ignored_issues?:list<string>,php_version?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        yield 'functionCheckMixedIsInt' => [
            'code' => '<?php
                /**
                 * @psalm-require-param-validation
                 * @param int $bar
                 */
                function foo($bar): void
                {
                    requiresInt($bar);
                }

                function requiresInt(int $_int): void {}
            ',
            'error_message' => 'MixedArgument',
        ];
        yield 'methodCheckMixedIsInt' => [
            'code' => '<?php
                class Foo
                {
                    /**
                     * @psalm-require-param-validation
                     * @param int $bar
                     */
                    public function foo($bar): void
                    {
                        requiresInt($bar);
                    }
                }

                function requiresInt(int $_int): void {}
            ',
            'error_message' => 'MixedArgument',
        ];
        yield 'checkPositiveIntForDeclaredInt' => [
            'code' => '<?php
                /**
                 * @psalm-require-param-validation
                 * @param int $bar
                 */
                function foo(int $bar): void
                {
                    requiresPositiveInt($bar);
                }

                /** @param positive-int $_int */
                function requiresPositiveInt(int $_int): void {}
            ',
            'error_message' => 'ArgumentTypeCoercion',
        ];
        yield 'typeIsStillCheckedForCaller' => [
            'code' => '<?php
                /**
                 * @psalm-require-param-validation
                 * @param int $bar
                 */
                function foo($bar): void
                {
                    if (!is_int($bar)) {
                        throw new \InvalidArgumentException();
                    }
                    requiresInt($bar);
                }

                function requiresInt(int $_int): void {}

                foo("notanint");
            ',
            'error_message' => 'InvalidArgument',
        ];
    }
}
