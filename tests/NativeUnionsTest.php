<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class NativeUnionsTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[],php_version?:string}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'nativeTypeUnionInConstructor' => [
                '<?php
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
                'error_levels' => [],
                'php_version' => '8.0'
            ],
            'nativeTypeUnionAsArgument' => [
                '<?php
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
                'error_levels' => [],
                'php_version' => '8.0'
            ],
            'unionAndNullableEquivalent' => [
                '<?php
                    function test(string|null $in): ?string {
                        return $in;
                    }
                ',
                'assertions' => [],
                'error_levels' => [],
                'php_version' => '8.0'
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'invalidNativeUnionArgument' => [
                '<?php
                    function test(string|null $in): string|null {
                        return $in;
                    }
                    test(2);
                ',
                'error_message' => 'InvalidScalarArgument',
                [],
                false,
                '8.0'
            ],
            'mismatchDocblockNativeUnionArgument' => [
                '<?php
                    /**
                     * @param string|null $in
                     */
                    function test(int|bool $in): bool {
                        return !!$in;
                    }
                ',
                'error_message' => 'MismatchingDocblockParamType',
                [],
                false,
                '8.0'
            ],
            'unionsNotAllowedInPHP74' => [
                '<?php
                    interface A {
                    }
                    interface B {
                    }
                    function foo (A|B $test): A&B {
                        return $test;
                    }',
                'error_message' => 'ParseError',
                [],
                false,
                '7.4'
            ],
        ];
    }
}
