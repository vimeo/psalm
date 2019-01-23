<?php
namespace Psalm\Tests;

class AssertTest extends TestCase
{
    use Traits\ValidCodeAnalysisTestTrait;
    use Traits\InvalidCodeAnalysisTestTrait;

    /**
     * @return array
     */
    public function providerValidCodeParse()
    {
        return [
            'assertInstanceOfB' => [
                '<?php
                    namespace Bar;

                    class A {}
                    class B extends A {
                        public function foo(): void {}
                    }

                    function assertInstanceOfB(A $var): void {
                        if (!$var instanceof B) {
                            throw new \Exception();
                        }
                    }

                    function takesA(A $a): void {
                        assertInstanceOfB($a);
                        $a->foo();
                    }',
            ],
            'dropInReplacementForAssert' => [
                '<?php
                    namespace Bar;

                    /**
                     * @param mixed $_b
                     * @psalm-assert !falsy $_b
                     */
                    function myAssert($_b) : void {
                        if (!$_b) {
                            throw new \Exception("bad");
                        }
                    }

                    function bar(?string $s) : string {
                        myAssert($s !== null);
                        return $s;
                    }'
            ],
            'assertInstanceOfInterface' => [
                '<?php
                    namespace Bar;

                    class A {
                        public function bar() : void {}
                    }
                    interface I {
                        public function foo(): void;
                    }
                    class B extends A implements I {
                        public function foo(): void {}
                    }

                    function assertInstanceOfI(A $var): void {
                        if (!$var instanceof I) {
                            throw new \Exception();
                        }
                    }

                    function takesA(A $a): void {
                        assertInstanceOfI($a);
                        $a->bar();
                        $a->foo();
                    }',
            ],
            'assertInstanceOfMultipleInterfaces' => [
                '<?php
                    namespace Bar;

                    class A {
                        public function bar() : void {}
                    }
                    interface I1 {
                        public function foo1(): void;
                    }
                    interface I2 {
                        public function foo2(): void;
                    }
                    class B extends A implements I1, I2 {
                        public function foo1(): void {}
                        public function foo2(): void {}
                    }

                    function assertInstanceOfInterfaces(A $var): void {
                        if (!$var instanceof I1 || !$var instanceof I2) {
                            throw new \Exception();
                        }
                    }

                    function takesA(A $a): void {
                        assertInstanceOfInterfaces($a);
                        $a->bar();
                        $a->foo1();
                    }',
            ],
            'assertInstanceOfBInClassMethod' => [
                '<?php
                    namespace Bar;

                    class A {}
                    class B extends A {
                        public function foo(): void {}
                    }

                    class C {
                        private function assertInstanceOfB(A $var): void {
                            if (!$var instanceof B) {
                                throw new \Exception();
                            }
                        }

                        private function takesA(A $a): void {
                            $this->assertInstanceOfB($a);
                            $a->foo();
                        }
                    }',
            ],
            'assertPropertyNotNull' => [
                '<?php
                    namespace Bar;

                    class A {
                        public function foo(): void {}
                    }

                    class B {
                        /** @var A|null */
                        public $a;

                        private function assertNotNullProperty(): void {
                            if (!$this->a) {
                                throw new \Exception();
                            }
                        }

                        public function takesA(A $a): void {
                            $this->assertNotNullProperty();
                            $a->foo();
                        }
                    }',
            ],
            'assertWithoutRedundantCondition' => [
                '<?php
                    namespace Bar;

                    /**
                     * @param mixed $data
                     * @throws \Exception
                     */
                    function assertIsLongString($data): void {
                        if (!is_string($data)) {
                            throw new \Exception;
                        }
                        if (strlen($data) < 100) {
                            throw new \Exception;
                        }
                    }

                    /**
                     * @throws \Exception
                     */
                    function f(string $s): void {
                        assertIsLongString($s);
                    }',
            ],
            'assertInstanceOfBAnnotation' => [
                '<?php
                    namespace Bar;

                    class A {}
                    class B extends A {
                        public function foo(): void {}
                    }

                    /** @psalm-assert B $var */
                    function myAssertInstanceOfB(A $var): void {
                        if (!$var instanceof B) {
                            throw new \Exception();
                        }
                    }

                    function takesA(A $a): void {
                        myAssertInstanceOfB($a);
                        $a->foo();
                    }',
            ],
            'assertIfTrueAnnotation' => [
                '<?php
                    namespace Bar;

                    /** @psalm-assert-if-true string $myVar */
                    function isValidString(?string $myVar) : bool {
                        return $myVar !== null && $myVar[0] === "a";
                    }

                    $myString = rand(0, 1) ? "abacus" : null;

                    if (isValidString($myString)) {
                        echo "Ma chaine " . $myString;
                    }'
            ],
            'assertIfFalseAnnotation' => [
                '<?php
                    namespace Bar;

                    /** @psalm-assert-if-false string $myVar */
                    function isInvalidString(?string $myVar) : bool {
                        return $myVar === null || $myVar[0] !== "a";
                    }

                    $myString = rand(0, 1) ? "abacus" : null;

                    if (isInvalidString($myString)) {
                        // do something
                    } else {
                        echo "Ma chaine " . $myString;
                    }'
            ],
            'assertServerVar' => [
                '<?php
                    namespace Bar;

                    /**
                     * @psalm-assert-if-true string $a
                     * @param mixed $a
                     */
                    function my_is_string($a) : bool
                    {
                        return is_string($a);
                    }

                    if (my_is_string($_SERVER["abc"])) {
                        $i = substr($_SERVER["abc"], 1, 2);
                    }',
            ],
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

                    // Returns concreate implmenetation of Foo, which in this case is Bar
                    function getImplementationOfFoo(): Foo {
                        return new Bar();
                    }

                    $bar = getImplementationOfFoo();
                    assertInstanceOf($bar, Bar::class);

                    $bar->sayHello();'
            ],
            'dontBleedBadAssertVarIntoContext' => [
                '<?php
                    namespace Bar;

                    class A {
                        public function foo() : bool {
                            return (bool) rand(0, 1);
                        }
                        public function bar() : bool {
                            return (bool) rand(0, 1);
                        }
                    }

                    /**
                     * Asserts that a condition is false.
                     *
                     * @param bool   $condition
                     * @param string $message
                     *
                     * @psalm-assert false $actual
                     */
                    function assertFalse($condition, $message = "") : void {}

                    function takesA(A $a) : void {
                        assertFalse($a->foo());
                        assertFalse($a->bar());
                    }'
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
            'assertAllStrings' => [
                '<?php
                    /**
                     * @psalm-assert iterable<mixed,string> $i
                     *
                     * @param iterable<mixed,mixed> $i
                     */
                    function assertAllStrings(iterable $i): void {
                        /** @psalm-suppress MixedAssignment */
                        foreach ($i as $s) {
                            if (!is_string($s)) {
                                throw new \UnexpectedValueException("");
                            }
                        }
                    }

                    function getArray(): array {
                        return [];
                    }

                    function getIterable(): iterable {
                        return [];
                    }

                    $array = getArray();
                    assertAllStrings($array);

                    $iterable = getIterable();
                    assertAllStrings($iterable);',
                [
                    '$array' => 'array<array-key, string>',
                    '$iterable' => 'iterable<mixed, string>',
                ]
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
                ]
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
                ]
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
        ];
    }

    /**
     * @return array
     */
    public function providerInvalidCodeParse()
    {
        return [
            'assertInstanceOfMultipleInterfaces' => [
                '<?php
                    class A {
                        public function bar() : void {}
                    }
                    interface I1 {
                        public function foo1(): void;
                    }
                    interface I2 {
                        public function foo2(): void;
                    }
                    class B extends A implements I1, I2 {
                        public function foo1(): void {}
                        public function foo2(): void {}
                    }

                    function assertInstanceOfInterfaces(A $var): void {
                        if (!$var instanceof I1 && !$var instanceof I2) {
                            throw new \Exception();
                        }
                    }

                    function takesA(A $a): void {
                        assertInstanceOfInterfaces($a);
                        $a->bar();
                        $a->foo1();
                    }',
                'error_message' => 'UndefinedMethod',
            ],
            'assertIfTrueNoAnnotation' => [
                '<?php
                    function isValidString(?string $myVar) : bool {
                        return $myVar !== null && $myVar[0] === "a";
                    }

                    $myString = rand(0, 1) ? "abacus" : null;

                    if (isValidString($myString)) {
                        echo "Ma chaine " . $myString;
                    }',
                'error_message' => 'PossiblyNullOperand',
            ],
            'assertIfFalseNoAnnotation' => [
                '<?php
                    function isInvalidString(?string $myVar) : bool {
                        return $myVar === null || $myVar[0] !== "a";
                    }

                    $myString = rand(0, 1) ? "abacus" : null;

                    if (isInvalidString($myString)) {
                        // do something
                    } else {
                        echo "Ma chaine " . $myString;
                    }',
                'error_message' => 'PossiblyNullOperand',
            ],
            'assertIfTrueMethodCall' => [
                '<?php
                    class C {
                        /**
                         * @param mixed $p
                         * @psalm-assert-if-true int $p
                         */
                        public function isInt($p): bool {
                            return is_int($p);
                        }
                        /**
                         * @param mixed $p
                         */
                        public function doWork($p): void {
                            if ($this->isInt($p)) {
                                strlen($p);
                            }
                        }
                    }',
                'error_message' => 'InvalidScalarArgument',
            ],
            'assertIfStaticTrueMethodCall' => [
                '<?php
                    class C {
                        /**
                         * @param mixed $p
                         * @psalm-assert-if-true int $p
                         */
                        public static function isInt($p): bool {
                            return is_int($p);
                        }
                        /**
                         * @param mixed $p
                         */
                        public function doWork($p): void {
                            if ($this->isInt($p)) {
                                strlen($p);
                            }
                        }
                    }',
                'error_message' => 'InvalidScalarArgument',
            ],
            'noFatalForUnknownAssertClass' => [
                '<?php
                    interface Foo {}

                    class Bar implements Foo {
                        public function sayHello(): void {
                            echo "Hello";
                        }
                    }

                    /**
                     * @param mixed $value
                     * @param class-string $type
                     * @psalm-assert SomeUndefinedClass $value
                     */
                    function assertInstanceOf($value, string $type): void {
                        // some code
                    }

                    // Returns concreate implmenetation of Foo, which in this case is Bar
                    function getImplementationOfFoo(): Foo {
                        return new Bar();
                    }

                    $bar = getImplementationOfFoo();
                    assertInstanceOf($bar, Bar::class);

                    $bar->sayHello();',
                'error_message' => 'UndefinedClass',
            ],
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
                'error_message' => 'RedundantCondition'
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
                'error_message' => 'TypeDoesNotContainType'
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
                'error_message' => 'RedundantCondition'
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
                'error_message' => 'TypeDoesNotContainType'
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
                'error_message' => 'RedundantCondition'
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
                'error_message' => 'TypeDoesNotContainType'
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
                'error_message' => 'TypeDoesNotContainType'
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
                'error_message' => 'TypeDoesNotContainType'
            ],
        ];
    }
}
