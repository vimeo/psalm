<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class NativeUnionsTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'nativeTypeUnionInConstructor' => [
                'code' => '<?php
                    interface A {
                    }
                    interface B {
                    }
                    class Foo {
                        public function __construct(private A|B $self) {}

                        public function self(): A|B
                        {
                            return $this->self;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'nativeTypeUnionAsArgument' => [
                'code' => '<?php
                    interface A {
                        function foo(): void;
                    }
                    interface B {
                        function foo(): void;
                    }
                    class C implements A {
                        function foo(): void {
                        }
                    }
                    function test(A|B $in): void {
                        $in->foo();
                    }
                    test(new C());
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'unionAndNullableEquivalent' => [
                'code' => '<?php
                    function test(string|null $in): ?string {
                        return $in;
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return [
            'duplicateTypeInAParamUnion' => [
                'code' => '<?php
                    function bar(string|string $foo): void {
                        echo $foo;
                    }',
                'error_message' => 'ParseError - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:34 - Duplicate type string is redundant',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'duplicateTypeInAReturnUnion' => [
                'code' => '<?php
                    function bar(): int|string|int {
                        return 1;
                    }',
                'error_message' => 'ParseError - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:37 - Duplicate type int is redundant',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'duplicateClassInAPropertyUnion' => [
                'code' => '<?php
                    class A {
                        public A|null|A $a = null;
                    }',
                'error_message' => 'ParseError - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:32 - Duplicate type A is redundant',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'invalidNativeUnionArgument' => [
                'code' => '<?php
                    function test(string|null $in): string|null {
                        return $in;
                    }
                    test(2);
                ',
                'error_message' => 'InvalidScalarArgument',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'mismatchDocblockNativeUnionArgument' => [
                'code' => '<?php
                    /**
                     * @param string|null $in
                     */
                    function test(int|bool $in): bool {
                        return !!$in;
                    }
                ',
                'error_message' => 'MismatchingDocblockParamType',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'unionsNotAllowedInPHP74' => [
                'code' => '<?php
                    interface A {
                    }
                    interface B {
                    }
                    function foo (A|B $test): A&B {
                        return $test;
                    }',
                'error_message' => 'ParseError',
                'error_levels' => [],
                'php_version' => '7.4',
            ],
        ];
    }
}
