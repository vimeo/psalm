<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class NativeIntersectionsTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'nativeTypeIntersectionInConstructor' => [
                'code' => '<?php
                    interface A {
                    }
                    interface B {
                    }
                    class Foo {
                        public function __construct(private A&B $self) {}

                        public function self(): A&B
                        {
                            return $this->self;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'nativeTypeIntersectionAsArgument' => [
                'code' => '<?php
                    interface A {
                        function foo(): void;
                    }
                    interface B {
                    }
                    class C implements A, B {
                        function foo(): void {
                        }
                    }
                    function test(A&B $in): void {
                        $in->foo();
                    }
                    test(new C());
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'invalidNativeIntersectionArgument' => [
                'code' => '<?php
                    interface A {
                        function foo(): void;
                    }
                    interface B {
                    }
                    class C implements A {
                        function foo(): void {
                        }
                    }
                    function test(A&B $in): void {
                        $in->foo();
                    }
                    test(new C());
                ',
                'error_message' => 'InvalidArgument',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'mismatchDocblockNativeIntersectionArgument' => [
                'code' => '<?php
                    interface A {
                        function foo(): void;
                    }
                    interface B {
                    }
                    interface C {
                    }
                    /**
                     * @param A&C $in
                     */
                    function test(A&B $in): void {
                        $in->foo();
                    }
                ',
                'error_message' => 'MismatchingDocblockParamType',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'intersectionsNotAllowedWithUnions' => [
                'code' => '<?php
                    interface A {
                    }
                    interface B {
                    }
                    interface C {
                    }
                    function foo (A&B|C $test): A&B|C {
                        return $test;
                    }',
                'error_message' => 'ParseError',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'intersectionsNotAllowedWithNonClasses' => [
                'code' => '<?php
                    interface A {
                    }
                    function foo (A&string $test): A&string {
                        return $test;
                    }',
                'error_message' => 'ParseError',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'intersectionsNotAllowedInPHP80' => [
                'code' => '<?php
                    interface A {
                    }
                    interface B {
                    }
                    function foo (A&B $test): A&B {
                        return $test;
                    }',
                'error_message' => 'ParseError',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
        ];
    }
}
