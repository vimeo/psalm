<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class NativeUnionsTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
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

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'invalidNativeUnionArgument' => [
                'code' => '<?php
                    function test(string|null $in): string|null {
                        return $in;
                    }
                    test(2);
                ',
                'error_message' => 'InvalidScalarArgument',
                'ignored_issues' => [],
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
                'ignored_issues' => [],
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
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
        ];
    }
}
