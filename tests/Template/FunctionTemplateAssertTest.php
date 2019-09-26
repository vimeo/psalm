<?php
namespace Psalm\Tests\Template;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits;

class FunctionTemplateAssertTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
    {
        return [
            'assertTemplatedType' => [
                '<?php
                    namespace Bar;

                    interface Foo {}

                    class Bar implements Foo {
                        public function sayHello(): void {
                            echo "Hello";
                        }
                    }

                    /**
                     * @param mixed $value
                     * @param class-string $type
                     * @template T
                     * @template-typeof T $type
                     * @psalm-assert T $value
                     */
                    function assertInstanceOf($value, string $type): void {
                        // some code
                    }

                    // Returns concreate implementation of Foo, which in this case is Bar
                    function getImplementationOfFoo(): Foo {
                        return new Bar();
                    }

                    $bar = getImplementationOfFoo();
                    assertInstanceOf($bar, Bar::class);

                    $bar->sayHello();',
            ],
            'assertInstanceofTemplatedClassMethodUnknownClass' => [
                '<?php
                    namespace Bar;

                    class C {
                        /**
                         * @template T
                         * @param class-string<T> $expected
                         * @param mixed  $actual
                         * @psalm-assert T $actual
                         */
                        public function assertInstanceOf($expected, $actual) : void {}

                        /**
                         * @param class-string $c
                         */
                        function bar(string $c, object $e) : void {
                            $this->assertInstanceOf($c, $e);
                            echo $e->getCode();
                        }
                    }',
                [],
                ['MixedArgument', 'MixedMethodCall'],
            ],
            'assertInstanceofTemplatedClassMethodUnknownStringClass' => [
                '<?php
                    namespace Bar;

                    class C {
                        /**
                         * @template T
                         * @param class-string<T> $expected
                         * @param mixed  $actual
                         * @psalm-assert T $actual
                         */
                        public function assertInstanceOf($expected, $actual) : void {}

                        function bar(string $c, object $e) : void {
                            $this->assertInstanceOf($c, $e);
                            echo $e->getCode();
                        }
                    }',
                [],
                ['MixedArgument', 'MixedMethodCall', 'ArgumentTypeCoercion'],
            ],
            'assertInstanceofTemplatedFunctionUnknownClass' => [
                '<?php
                    namespace Bar;

                    /**
                     * @template T
                     * @param class-string<T> $expected
                     * @param mixed  $actual
                     * @psalm-assert T $actual
                     */
                    function assertInstanceOf($expected, $actual) : void {}

                    /**
                     * @param class-string $c
                     */
                    function bar(string $c, object $e) : void {
                        assertInstanceOf($c, $e);
                        echo $e->getCode();
                    }',
                [],
                ['MixedArgument', 'MixedMethodCall'],
            ],
            'assertInstanceofTemplatedFunctionUnknownStringClass' => [
                '<?php
                    namespace Bar;

                    /**
                     * @template T
                     * @param class-string<T> $expected
                     * @param mixed  $actual
                     * @psalm-assert T $actual
                     */
                    function assertInstanceOf($expected, $actual) : void {}

                    function bar(string $c, object $e) : void {
                        assertInstanceOf($c, $e);
                        echo $e->getCode();
                    }',
                [],
                ['MixedArgument', 'MixedMethodCall', 'ArgumentTypeCoercion'],
            ],
            'assertTypedArray' => [
                '<?php
                    namespace Bar;

                    class A {
                        public function foo() : void {}
                    }

                    /**
                     * @template T
                     * @param class-string<T> $expected
                     * @param mixed  $actual
                     * @psalm-assert T[] $actual
                     */
                    function assertArrayOf($expected, $actual) : void {}

                    function bar(array $arr) : void {
                        assertArrayOf(A::class, $arr);
                        foreach ($arr as $a) {
                            $a->foo();
                        }
                    }',
            ],
            'assertTemplatedTypeString' => [
                '<?php
                    interface Foo {
                        function bat() : void;
                    }

                    /**
                     * @param mixed $value
                     * @param class-string $type
                     * @template T
                     * @template-typeof T $type
                     * @psalm-assert T $value
                     */
                    function assertInstanceOf($value, string $type): void {
                        // some code
                    }

                    function getFoo() : Foo {
                        return new class implements Foo {
                            public function bat(): void {
                                echo "Hello";
                            }
                        };
                    }

                    $f = getFoo();
                    /**
                     * @var mixed
                     */
                    $class = "hello";

                    /** @psalm-suppress MixedArgument */
                    assertInstanceOf($f, $class);
                    $f->bat();',
                [
                    '$f' => 'Foo',
                ],
            ],
            'suppressRedundantCondition' => [
                '<?php
                    namespace Bar;

                    class A {}

                    /**
                     * @param class-string $expected
                     * @param mixed  $actual
                     * @param string $message
                     *
                     * @template T
                     * @template-typeof T $expected
                     * @psalm-assert T $actual
                     */
                    function assertInstanceOf($expected, $actual) : void {
                    }

                    /**
                     * @psalm-suppress RedundantCondition
                     */
                    function takesA(A $a) : void {
                        assertInstanceOf(A::class, $a);
                    }',
            ],
            'allowCanBeSameAfterAssertion' => [
                '<?php
                    namespace Bar;

                    /**
                     * Asserts that two variables are the same.
                     *
                     * @template T
                     * @param T      $expected
                     * @param mixed  $actual
                     * @psalm-assert =T $actual
                     */
                    function assertSame($expected, $actual) : void {}

                    $a = rand(0, 1) ? "goodbye" : "hello";
                    $b = rand(0, 1) ? "hello" : "goodbye";
                    assertSame($a, $b);

                    $c = "hello";
                    $d = rand(0, 1) ? "hello" : "goodbye";
                    assertSame($c, $d);

                    $c = "hello";
                    $d = rand(0, 1) ? "hello" : "goodbye";
                    assertSame($d, $c);

                    $c = 4;
                    $d = rand(0, 1) ? 4 : 5;
                    assertSame($d, $c);

                    $d = rand(0, 1) ? 4 : null;
                    assertSame(null, $d);

                    function foo(string $a, string $b) : void {
                        assertSame($a, $b);
                    }',
            ],
            'allowCanBeNotSameAfterAssertion' => [
                '<?php
                    namespace Bar;

                    /**
                     * Asserts that two variables are the same.
                     *
                     * @template T
                     * @param T      $expected
                     * @param mixed  $actual
                     * @psalm-assert !=T $actual
                     */
                    function assertNotSame($expected, $actual) : void {}

                    $a = rand(0, 1) ? "goodbye" : "hello";
                    $b = rand(0, 1) ? "hello" : "goodbye";
                    assertNotSame($a, $b);

                    $c = "hello";
                    $d = rand(0, 1) ? "hello" : "goodbye";
                    assertNotSame($c, $d);

                    $c = "hello";
                    $d = rand(0, 1) ? "hello" : "goodbye";
                    assertNotSame($d, $c);

                    $c = 4;
                    $d = rand(0, 1) ? 4 : 5;
                    assertNotSame($d, $c);

                    function foo(string $a, string $b) : void {
                        assertNotSame($a, $b);
                    }',
            ],
            'allowCanBeEqualAfterAssertion' => [
                '<?php

                    /**
                     * Asserts that two variables are the same.
                     *
                     * @template T
                     * @param T      $expected
                     * @param mixed  $actual
                     * @psalm-assert ~T $actual
                     */
                    function assertEqual($expected, $actual) : void {}

                    $a = rand(0, 1) ? "goodbye" : "hello";
                    $b = rand(0, 1) ? "hello" : "goodbye";
                    assertEqual($a, $b);

                    $c = "hello";
                    $d = rand(0, 1) ? "hello" : "goodbye";
                    assertEqual($c, $d);

                    $c = "hello";
                    $d = rand(0, 1) ? "hello" : "goodbye";
                    assertEqual($d, $c);

                    $c = 4;
                    $d = rand(0, 1) ? 3.0 : 4.0;
                    assertEqual($d, $c);

                    $c = 4.0;
                    $d = rand(0, 1) ? 3 : 4;
                    assertEqual($d, $c);

                    function foo(string $a, string $b) : void {
                        assertEqual($a, $b);
                    }',
            ],
            'assertAllArrayOfClass' => [
                '<?php
                    /**
                     * @template T
                     *
                     * @psalm-assert iterable<mixed,T> $i
                     *
                     * @param iterable<mixed,mixed> $i
                     * @param class-string<T> $type
                     */
                    function assertAllInstanceOf(iterable $i, string $type): void {
                        /** @psalm-suppress MixedAssignment */
                        foreach ($i as $elt) {
                            if (!$elt instanceof $type) {
                                throw new \UnexpectedValueException("");
                            }
                        }
                    }

                    class A {}

                    function getArray(): array {
                        return [];
                    }

                    $array = getArray();
                    assertAllInstanceOf($array, A::class);',
                [
                    '$array' => 'array<array-key, A>',
                ],
            ],
            'assertAllIterableOfClass' => [
                '<?php
                    /**
                     * @template T
                     *
                     * @psalm-assert iterable<mixed,T> $i
                     *
                     * @param iterable<mixed,mixed> $i
                     * @param class-string<T> $type
                     */
                    function assertAllInstanceOf(iterable $i, string $type): void {
                        /** @psalm-suppress MixedAssignment */
                        foreach ($i as $elt) {
                            if (!$elt instanceof $type) {
                                throw new \UnexpectedValueException("");
                            }
                        }
                    }

                    class A {}

                    function getIterable(): iterable {
                        return [];
                    }

                    $iterable = getIterable();
                    assertAllInstanceOf($iterable, A::class);',
                [
                    '$iterable' => 'iterable<mixed, A>',
                ],
            ],
            'complicatedAssertAllInstanceOf' => [
                '<?php
                    /**
                     * @template T
                     *
                     * @psalm-assert-if-true iterable<mixed,T> $i
                     *
                     * @param iterable<mixed,mixed> $i
                     * @param class-string<T> $type
                     */
                    function allInstanceOf(iterable $i, string $type): bool {
                        /** @psalm-suppress MixedAssignment */
                        foreach ($i as $elt) {
                            if (!$elt instanceof $type) {
                                return false;
                            }
                        }
                        return true;
                    }

                    interface IBlogPost { public function getId(): int; }

                    function getData(): iterable {
                        return [];
                    }

                    $data = getData();

                    assert(allInstanceOf($data, IBlogPost::class));

                    foreach ($data as $post) {
                        echo $post->getId();
                    }',
            ],
            'assertUnionInNamespace' => [
                '<?php
                    namespace Foo\Bar\Baz;

                    /**
                      * @psalm-template ExpectedType of object
                      * @param mixed $value
                      * @psalm-param class-string<ExpectedType> $interface
                      * @psalm-assert ExpectedType|class-string<ExpectedType> $value
                      */
                    function implementsInterface($value, $interface, string $message = ""): void {}

                    /**
                      * @psalm-template ExpectedType of object
                      * @param mixed $value
                      * @psalm-param class-string<ExpectedType> $interface
                      * @psalm-assert null|ExpectedType|class-string<ExpectedType> $value
                      */
                    function nullOrImplementsInterface(?object $value, $interface, string $message = ""): void {}

                    interface A
                    {
                    }

                    /**
                     * @param mixed $value
                     *
                     * @psalm-return A|class-string<A>
                     */
                    function consume($value) {
                        implementsInterface($value, A::class);

                        return $value;
                    }

                    /**
                     * @param mixed $value
                     *
                     * @psalm-return A|class-string<A>|null
                     */
                    function consume2($value)
                    {
                        nullOrImplementsInterface($value, A::class);

                        return $value;
                    }'
            ],
            'assertTemplatedTemplateSimple' => [
                '<?php
                    /**
                     * @template T1
                     */
                    class Clazz
                    {
                        /**
                         * @param mixed $x
                         *
                         * @psalm-assert T1 $x
                         */
                        public function is($x) : void {}
                    }

                    /**
                     * @template T2
                     *
                     * @param Clazz<T2> $c
                     *
                     * @return T2
                     */
                    function example(Clazz $c) {
                        /** @var mixed */
                        $x = 0;
                        $c->is($x);
                        return $x;
                    }'
            ],
            'assertTemplatedTemplateIfTrue' => [
                '<?php
                    /**
                     * @template T1
                     */
                    class Clazz
                    {
                        /**
                         * @param mixed $x
                         *
                         * @return bool
                         *
                         * @psalm-assert-if-true T1 $x
                         */
                        public function is($x) : bool {
                            return true;
                        }
                    }

                    /**
                     * @template T2
                     *
                     * @param Clazz<T2> $c
                     *
                     * @return T2|false
                     */
                    function example(Clazz $c) {
                        /** @var mixed */
                        $x = 0;
                        return $c->is($x) ? $x : false;
                    }'
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse()
    {
        return [
            'detectRedundantCondition' => [
                '<?php
                    class A {}

                    /**
                     * @param class-string $expected
                     * @param mixed  $actual
                     * @param string $message
                     *
                     * @template T
                     * @template-typeof T $expected
                     * @psalm-assert T $actual
                     */
                    function assertInstanceOf($expected, $actual) : void {
                    }

                    function takesA(A $a) : void {
                        assertInstanceOf(A::class, $a);
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'detectAssertSameTypeDoesNotContainType' => [
                '<?php

                    /**
                     * Asserts that two variables are the same.
                     *
                     * @template T
                     * @param T      $expected
                     * @param mixed  $actual
                     * @psalm-assert =T $actual
                     */
                    function assertSame($expected, $actual) : void {}

                    $a = 5;
                    $b = "hello";
                    assertSame($a, $b);',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'detectAssertAlwaysSame' => [
                '<?php

                    /**
                     * Asserts that two variables are the same.
                     *
                     * @template T
                     * @param T      $expected
                     * @param mixed  $actual
                     * @psalm-assert =T $actual
                     */
                    function assertSame($expected, $actual) : void {}

                    $a = 5;
                    $b = 5;
                    assertSame($a, $b);',
                'error_message' => 'RedundantCondition',
            ],
            'detectNeverCanBeSameAfterAssertion' => [
                '<?php

                    /**
                     * Asserts that two variables are the same.
                     *
                     * @template T
                     * @param T      $expected
                     * @param mixed  $actual
                     * @psalm-assert =T $actual
                     */
                    function assertSame($expected, $actual) : void {}

                    $c = "helloa";
                    $d = rand(0, 1) ? "hello" : "goodbye";
                    assertSame($c, $d);',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'detectNeverCanBeNotSameAfterAssertion' => [
                '<?php

                    /**
                     * Asserts that two variables are the same.
                     *
                     * @template T
                     * @param T      $expected
                     * @param mixed  $actual
                     * @psalm-assert !=T $actual
                     */
                    function assertNotSame($expected, $actual) : void {}

                    $c = "helloa";
                    $d = rand(0, 1) ? "hello" : "goodbye";
                    assertNotSame($c, $d);',
                'error_message' => 'RedundantCondition',
            ],
            'detectNeverCanBeEqualAfterAssertion' => [
                '<?php

                    /**
                     * Asserts that two variables are the same.
                     *
                     * @template T
                     * @param T      $expected
                     * @param mixed  $actual
                     * @psalm-assert ~T $actual
                     */
                    function assertEqual($expected, $actual) : void {}

                    $c = "helloa";
                    $d = rand(0, 1) ? "hello" : "goodbye";
                    assertEqual($c, $d);',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'detectIntFloatNeverCanBeEqualAfterAssertion' => [
                '<?php

                    /**
                     * Asserts that two variables are the same.
                     *
                     * @template T
                     * @param T      $expected
                     * @param mixed  $actual
                     * @psalm-assert ~T $actual
                     */
                    function assertEqual($expected, $actual) : void {}

                    $c = 4;
                    $d = rand(0, 1) ? 5.0 : 6.0;
                    assertEqual($c, $d);',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'detectFloatIntNeverCanBeEqualAfterAssertion' => [
                '<?php

                    /**
                     * Asserts that two variables are the same.
                     *
                     * @template T
                     * @param T      $expected
                     * @param mixed  $actual
                     * @psalm-assert ~T $actual
                     */
                    function assertEqual($expected, $actual) : void {}

                    $c = 4.0;
                    $d = rand(0, 1) ? 5 : 6;
                    assertEqual($c, $d);',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'assertNotSameDifferentTypes' => [
                '<?php
                    /**
                     * @template T
                     * @param T      $expected
                     * @param mixed  $actual
                     * @param string $message
                     * @psalm-assert !=T $actual
                     * @return void
                     */
                    function assertNotSame($expected, $actual, $message = "") {}

                    function bar(string $i, array $j) : void {
                        assertNotSame($i, $j);
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'assertNotSameDifferentTypesExplicitString' => [
                '<?php
                    /**
                     * @template T
                     * @param T      $expected
                     * @param mixed  $actual
                     * @param string $message
                     * @psalm-assert !=T $actual
                     * @return void
                     */
                    function assertNotSame($expected, $actual, $message = "") {}

                    function bar(array $j) : void {
                        assertNotSame("hello", $j);
                    }',
                'error_message' => 'RedundantCondition',
            ],

        ];
    }
}
