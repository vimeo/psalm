<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class NativeIntersectionsTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
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
            'nativeTypeIntersectionAsClassProperty' => [
                'code' => '<?php
                    interface A {}
                    interface B {}
                    class C implements A, B {}
                    class D {
                        private A&B $intersection;
                        public function __construct()
                        {
                            $this->intersection = new C();
                        }
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'nativeTypeIntersectionAsClassPropertyUsingProcessedInterfaces' => [
                'code' => '<?php
                    interface A {}
                    interface B {}
                    class AB implements A, B {}
                    class C {
                        private A&B $other;
                        public function __construct()
                        {
                            $this->other = new AB();
                        }
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'nativeTypeIntersectionAsClassPropertyUsingUnprocessedInterfaces' => [
                'code' => '<?php
                    class StringableJson implements \Stringable, \JsonSerializable {
                        public function jsonSerialize(): array
                        {
                            return [];
                        }
                        public function __toString(): string
                        {
                            return json_encode($this);
                        }
                    }
                    class C {
                        private \Stringable&\JsonSerializable $other;
                        public function __construct()
                        {
                            $this->other = new StringableJson();
                        }
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
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
            'nativeTypeIntersectionAsClassPropertyUsingUnknownInterfaces' => [
                'code' => '<?php
                    class C {
                        private \Example\Unknown\A&\Example\Unknown\B $other;
                        public function __construct()
                        {
                            $this->other = new \Example\Unknown\AB();
                        }
                    }
                ',
                // @todo decide whether a fall-back should be implemented, that allows to by-pass this failure (opt-in config)
                // `UndefinedClass - src/somefile.php:3:33 - Class, interface or enum named Example\Unknown\B does not exist`
                'error_message' => 'UndefinedClass',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
        ];
    }
}
