<?php

namespace Psalm\Tests\Template;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class FunctionTemplateAssertTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'assertTemplatedType' => [
                'code' => '<?php
                    namespace Bar;

                    interface Foo {}

                    class Bar implements Foo {
                        public function sayHello(): void {
                            echo "Hello";
                        }
                    }

                    /**
                     * @param mixed $value
                     * @param class-string<T> $type
                     * @template T
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
                'code' => '<?php
                    namespace Bar;

                    class C {
                        /**
                         * @template T as object
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
                'assertions' => [],
                'ignored_issues' => ['MixedArgument', 'MixedMethodCall'],
            ],
            'assertInstanceofTemplatedClassMethodUnknownStringClass' => [
                'code' => '<?php
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
                'assertions' => [],
                'ignored_issues' => ['MixedArgument', 'MixedMethodCall', 'ArgumentTypeCoercion'],
            ],
            'assertInstanceofTemplatedFunctionUnknownClass' => [
                'code' => '<?php
                    namespace Bar;

                    /**
                     * @template T as object
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
                'assertions' => [],
                'ignored_issues' => ['MixedArgument', 'MixedMethodCall'],
            ],
            'assertInstanceofTemplatedFunctionUnknownStringClass' => [
                'code' => '<?php
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
                'assertions' => [],
                'ignored_issues' => ['MixedArgument', 'MixedMethodCall', 'ArgumentTypeCoercion'],
            ],
            'assertTypedArray' => [
                'code' => '<?php
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
                'code' => '<?php
                    interface Foo {}

                    /**
                     * @template T as object
                     *
                     * @param mixed $value
                     * @param class-string<T> $type
                     *
                     * @psalm-assert T $value
                     */
                    function assertInstanceOf($value, string $type): void {
                        // some code
                    }

                    function getFoo() : Foo {
                        return new class implements Foo {};
                    }

                    $f = getFoo();
                    /**
                     * @var mixed
                     */
                    $class = "hello";

                    /** @psalm-suppress MixedArgument */
                    assertInstanceOf($f, $class);',
                'assertions' => [
                    '$f' => 'Foo',
                ],
            ],
            'suppressRedundantCondition' => [
                'code' => '<?php
                    namespace Bar;

                    class A {}

                    /**
                     * @param class-string<T> $expected
                     * @param mixed  $actual
                     * @param string $message
                     *
                     * @template T
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
                'code' => '<?php
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

                    class Hello {}
                    class Goodbye {}

                    $a = rand(0, 1) ? new Goodbye() : new Hello();
                    $b = rand(0, 1) ? new Hello() : new Goodbye();
                    assertSame($a, $b);

                    $c = new Hello();
                    $d = rand(0, 1) ? new Hello() : new Goodbye();
                    assertSame($c, $d);

                    $c = new Hello();
                    $d = rand(0, 1) ? new Hello() : new Goodbye();
                    assertSame($d, $c);

                    $c = 4;
                    $d = rand(0, 1) ? 4 : 5;
                    assertSame($d, $c);

                    $d = rand(0, 1) ? 4 : null;
                    assertSame(null, $d);

                    function assertStringsAreSame(string $a, string $b) : void {
                        assertSame($a, $b);
                    }

                    /** @param mixed $a */
                    function assertMaybeStringsAreSame($a, string $b) : void {
                        assertSame($a, $b);
                    }

                    /** @param mixed $b */
                    function alsoAssertMaybeStringsAreSame(string $a, $b) : void {
                        assertSame($a, $b);
                    }',
            ],
            'allowCanBeSameAfterStaticMethodAssertion' => [
                'code' => '<?php
                    namespace Bar;

                    class Assertion {
                        /**
                         * Asserts that two variables are the same.
                         *
                         * @template T
                         * @param T      $expected
                         * @param mixed  $actual
                         * @psalm-assert =T $actual
                         */
                        public static function assertSame($expected, $actual) : void {}
                    }

                    class Hello {}
                    class Goodbye {}

                    $a = rand(0, 1) ? new Goodbye() : new Hello();
                    $b = rand(0, 1) ? new Hello() : new Goodbye();
                    Assertion::assertSame($a, $b);',
            ],
            'allowCanBeNotSameAfterAssertionReverseUnion' => [
                'code' => '<?php
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

                    class Hello {}
                    class Goodbye {}

                    $goodbye_or_hello = rand(0, 1) ? new Goodbye() : new Hello();
                    $hello_or_goodbye = rand(0, 1) ? new Hello() : new Goodbye();
                    assertNotSame($goodbye_or_hello, $hello_or_goodbye);',
            ],
            'allowCanBeNotSameAfterAssertionAcmpAorB' => [
                'code' => '<?php
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

                    class Hello {}
                    class Goodbye {}

                    $hello = new Hello();
                    $hello_or_goodbye = rand(0, 1) ? new Hello() : new Goodbye();
                    assertNotSame($hello, $hello_or_goodbye);',
            ],
            'allowCanBeNotSameAfterAssertionAorBcmpA' => [
                'code' => '<?php
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

                    class Hello {}
                    class Goodbye {}

                    $hello = new Hello();
                    $hello_or_goodbye = rand(0, 1) ? new Hello() : new Goodbye();
                    assertNotSame($hello_or_goodbye, $hello);',
            ],
            'allowCanBeNotSameAfterAssertionScalar' => [
                'code' => '<?php
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

                    $c = 4;
                    $d = rand(0, 1) ? 4 : 5;
                    assertNotSame($d, $c);

                    function foo(string $a, string $b) : void {
                        assertNotSame($a, $b);
                    }',
            ],
            'allowCanBeEqualAfterAssertion' => [
                'code' => '<?php

                    /**
                     * Asserts that two variables are the same.
                     *
                     * @template T
                     * @param T      $expected
                     * @param mixed  $actual
                     * @psalm-assert ~T $actual
                     */
                    function assertEqual($expected, $actual) : void {}

                    class Hello {}
                    class Goodbye {}

                    $a = rand(0, 1) ? new Goodbye() : new Hello();
                    $b = rand(0, 1) ? new Hello() : new Goodbye();
                    assertEqual($a, $b);

                    $c = new Hello();
                    $d = rand(0, 1) ? new Hello() : new Goodbye();
                    assertEqual($c, $d);

                    $c = new Hello();
                    $d = rand(0, 1) ? new Hello() : new Goodbye();
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
                'code' => '<?php
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
                'assertions' => [
                    '$array' => 'array<array-key, A>',
                ],
            ],
            'assertAllIterableOfClass' => [
                'code' => '<?php
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
                'assertions' => [
                    '$iterable' => 'iterable<mixed, A>',
                ],
            ],
            'complicatedAssertAllInstanceOf' => [
                'code' => '<?php
                    /**
                     * @template T
                     *
                     * @psalm-assert-if-true iterable<mixed,T> $i
                     *
                     * @param iterable<mixed,mixed> $i
                     * @param class-string<T>|interface-string<T> $type
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
                'code' => '<?php
                    namespace Foo\Bar\Baz;

                    /**
                      * @psalm-template ExpectedType of object
                      * @param mixed $value
                      * @psalm-param interface-string<ExpectedType> $interface
                      * @psalm-assert ExpectedType|interface-string<ExpectedType> $value
                      */
                    function implementsInterface($value, $interface, string $message = ""): void {}

                    /**
                      * @psalm-template ExpectedType of object
                      * @param mixed $value
                      * @psalm-param interface-string<ExpectedType> $interface
                      * @psalm-assert null|ExpectedType|interface-string<ExpectedType> $value
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
                    }',
            ],
            'assertTemplatedTemplateSimple' => [
                'code' => '<?php
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
                    }',
            ],
            'assertTemplatedTemplateIfTrue' => [
                'code' => '<?php
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
                    }',
            ],
            'assertOnClass' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    abstract class Type
                    {
                        /**
                         * @param mixed $value
                         * @return bool
                         * @psalm-assert-if-true T $value
                         */
                        abstract public function matches($value): bool;

                        /**
                         * @param mixed $value
                         * @return mixed
                         * @psalm-return T
                         * @psalm-assert T $value
                         */
                        public function assert($value)
                        {
                            assert($this->matches($value));
                            return $value;
                        }
                    }',
            ],
            'noCrashWhenAsserting' => [
                'code' => '<?php
                    /**
                     * @psalm-template ExpectedClassType of object
                     * @psalm-param class-string<ExpectedClassType> $expectedType
                     * @psalm-assert class-string<ExpectedClassType> $actualType
                     */
                    function assertIsA(string $expectedType, string $actualType): void {
                        \assert(\is_a($actualType, $expectedType, true));
                    }

                    class Foo {
                        /**
                         * @psalm-template OriginalClass of object
                         * @psalm-param class-string<OriginalClass> $originalClass
                         * @psalm-return class-string<OriginalClass>|null
                         */
                        private function generateProxy(string $originalClass) : ?string {
                            $generatedClassName = self::class . \'\\\\\' . $originalClass;

                            if (class_exists($generatedClassName)) {
                                assertIsA($originalClass, $generatedClassName);

                                return $generatedClassName;
                            }

                            return null;
                        }
                    }',
            ],
            'castClassStringWithIsA' => [
                'code' => '<?php
                    /**
                     * @psalm-template RequestedClass of object
                     * @psalm-param class-string<RequestedClass> $templated_class_string
                     * @psalm-return class-string<RequestedClass>
                     */
                    function castStringToClassString(
                        string $templated_class_string,
                        string $input_string
                    ): string {
                        \assert(\is_a($input_string, $templated_class_string, true));
                        return $input_string;
                    }',
            ],
            'classTemplateAssert' => [
                'code' => '<?php
                    /**
                     * @template ActualFieldType
                     */
                    final class FieldValue
                    {
                        /** @var ActualFieldType */
                        public $value;

                        /** @param ActualFieldType $value */
                        public function __construct($value) {
                            $this->value = $value;
                        }
                    }

                    /**
                     * @template FieldDefinitionType
                     *
                     * @param string|bool|int|null $value
                     * @param FieldDefinition<FieldDefinitionType> $definition
                     *
                     * @return FieldValue<FieldDefinitionType>
                     */
                    function fromScalarAndDefinition($value, FieldDefinition $definition) : FieldValue
                    {
                        $definition->assertAppliesToValue($value);

                        return new FieldValue($value);
                    }

                    /**
                     * @template ExpectedFieldType
                     */
                    final class FieldDefinition
                    {
                        /**
                         * @param mixed $value
                         * @psalm-assert ExpectedFieldType $value
                         */
                        public function assertAppliesToValue($value): void
                        {
                          throw new \Exception("bad");
                        }
                    }',
            ],
            'assertThrowsInstanceOfFunction' => [
                'code' => '<?php
                    namespace Foo;

                    /**
                     * @template T of \Throwable
                     * @psalm-param class-string<T> $exceptionType
                     * @psalm-assert T $outerEx
                     */
                    function assertThrowsInstanceOf(\Throwable $outerEx, string $exceptionType) : void {
                        if (!($outerEx instanceof $exceptionType)) {
                            throw new \Exception("thrown instance of wrong type");
                        }
                    }',
            ],
            'dontBleedTemplateTypeInArray' => [
                'code' => '<?php
                    /**
                     * @psalm-template ExpectedType of object
                     * @psalm-param class-string<ExpectedType> $class
                     * @psalm-assert array<class-string<ExpectedType>> $value
                     *
                     * @param array<string> $value
                     * @param string                  $class
                     */
                    function allIsAOf($value, $class): void {}

                    /**
                     * @psalm-template T of object
                     *
                     * @param array<string> $value
                     * @param class-string<T> $class
                     *
                     * @return array<class-string<T>>
                     */
                    function f($value, $class) {
                        allIsAOf($value, $class);

                        return $value;
                    }',
            ],
            'noCrashOnListKeyAssertion' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param T $t
                     * @param mixed $other
                     * @psalm-assert =T $other
                     */
                    function assertSame($t, $other) : void {}

                    /** @param list<int> $list */
                    function takesList(array $list) : void {
                        foreach ($list as $i => $l) {
                            assertSame($i, $l);
                        }
                    }',
            ],
            'assertSameOnMemoizedMethodCall' => [
                'code' => '<?php
                    function testValidUsername(): void {
                        try {
                            validateUsername("123");
                            throw new Exception("Failed to throw exception for short username");
                        } catch (Exception $e) {
                            assertSame("a", $e->getMessage());
                        }

                        try {
                            validateUsername("invalid#1");
                        } catch (Exception $e) {
                            assertSame("b", $e->getMessage());
                        }
                    }

                    /**
                     * @psalm-template ExpectedType
                     * @psalm-param ExpectedType $expected
                     * @psalm-param mixed $actual
                     * @psalm-assert =ExpectedType $actual
                     */
                    function assertSame($expected, $actual): void {
                        if ($actual !== $expected) {
                            throw new Exception("Bad");
                        }
                    }

                    function validateUsername(string $username): void {
                        if (strlen($username) < 5) {
                            throw new Exception("Username must be at least 5 characters long");
                        }
                    }',
            ],
            'ifTrueListAssertionFromGeneric' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    final class Type
                    {
                        /**
                         * @param mixed $toCheck
                         * @psalm-assert-if-true T $toCheck
                         */
                        function is($toCheck): bool
                        {
                            throw new RuntimeException("???");
                        }
                    }

                    /**
                     * @param list<int> $_list
                     */
                    function acceptsIntList(array $_list): void {}

                    /** @var Type<list<int>> $numbersT */
                    $numbersT = new Type();

                    /** @var mixed $mixed */
                    $mixed = null;

                    if ($numbersT->is($mixed)) {
                        acceptsIntList($mixed);
                    }',
            ],
            'assertListFromGeneric' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    final class Type
                    {
                        /**
                         * @param mixed $toCheck
                         * @psalm-assert T $toCheck
                         */
                        function assert($toCheck): void
                        {
                        }
                    }

                    /**
                     * @param list<int> $_list
                     */
                    function acceptsIntList(array $_list): void {}

                    /** @var Type<list<int>> $numbersT */
                    $numbersT = new Type();

                    /** @var mixed $mixed */
                    $mixed = null;

                    $numbersT->assert($mixed);
                    acceptsIntList($mixed);',
            ],
            'assertArrayFromGeneric' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    final class Type
                    {
                        /**
                         * @param mixed $toCheck
                         * @psalm-assert T $toCheck
                         */
                        function assert($toCheck): void
                        {
                        }
                    }

                    /**
                     * @param array<string, int> $_list
                     */
                    function acceptsArray(array $_list): void {}

                    /** @var Type<array<string, int>> $numbersT */
                    $numbersT = new Type();

                    /** @var mixed $mixed */
                    $mixed = null;

                    $numbersT->assert($mixed);
                    acceptsArray($mixed);',
            ],
            'assertObjectShape' => [
                'code' => '<?php
                    final class Foo
                    {
                        public const STATUS_OK = "ok";
                        public const STATUS_FAIL = "fail";
                    }

                    $foo = new stdClass();

                    /** @psalm-assert object{status: Foo::STATUS_*} $bar */
                    function assertObjectShape(object $bar): void {
                    }

                    assertObjectShape($foo);
                    $status = $foo->status;
                ',
                'assertions' => [
                    '$status===' => "'fail'|'ok'",
                ],
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'detectRedundantCondition' => [
                'code' => '<?php
                    class A {}

                    /**
                     * @param class-string<T> $expected
                     * @param mixed  $actual
                     * @param string $message
                     *
                     * @template T
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
                'code' => '<?php

                    /**
                     * Asserts that two variables are the same.
                     *
                     * @template T
                     * @param T      $expected
                     * @param mixed  $actual
                     * @psalm-assert =T $actual
                     */
                    function assertSame($expected, $actual) : void {}

                    class Hello {}

                    $a = 5;
                    $b = new Hello();
                    assertSame($a, $b);',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'detectAssertAlwaysSame' => [
                'code' => '<?php

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
                'code' => '<?php

                    /**
                     * Asserts that two variables are the same.
                     *
                     * @template T
                     * @param T      $expected
                     * @param mixed  $actual
                     * @psalm-assert =T $actual
                     */
                    function assertSame($expected, $actual) : void {}

                    class Hello {}
                    class Helloa {}
                    class Goodbye {}

                    $c = new Helloa();
                    $d = rand(0, 1) ? new Hello() : new Goodbye();
                    assertSame($c, $d);',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'detectNeverCanBeNotSameAfterAssertion' => [
                'code' => '<?php

                    /**
                     * Asserts that two variables are the same.
                     *
                     * @template T
                     * @param T      $expected
                     * @param mixed  $actual
                     * @psalm-assert !=T $actual
                     */
                    function assertNotSame($expected, $actual) : void {}

                    class Hello {}
                    class Helloa {}
                    class Goodbye {}

                    $c = new Helloa();
                    $d = rand(0, 1) ? new Hello() : new Goodbye();
                    assertNotSame($c, $d);',
                'error_message' => 'RedundantCondition',
            ],
            'detectNeverCanBeEqualAfterAssertion' => [
                'code' => '<?php

                    /**
                     * Asserts that two variables are the same.
                     *
                     * @template T
                     * @param T      $expected
                     * @param mixed  $actual
                     * @psalm-assert ~T $actual
                     */
                    function assertEqual($expected, $actual) : void {}

                    class Hello {}
                    class Helloa {}
                    class Goodbye {}

                    $c = new Helloa();
                    $d = rand(0, 1) ? new Hello() : new Goodbye();
                    assertEqual($c, $d);',
                'error_message' => 'TypeDoesNotContainType',
            ],
            // Ignoring this to put the behaviour on par with regular equality checks
            'SKIPPED-detectIntFloatNeverCanBeEqualAfterAssertion' => [
                'code' => '<?php

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
                'code' => '<?php

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
            'assertTemplateUnionParadox' => [
                'code' => '<?php
                    /**
                     * Asserts that two variables are not the same.
                     *
                     * @template T
                     * @param T      $expected
                     * @param mixed  $actual
                     * @psalm-assert T $actual
                     */
                    function assertSame($expected, $actual) : void {}

                    $expected = rand(0, 1) ? 4 : 5;
                    $actual = 6;
                    assertSame($expected, $actual);',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'assertNotSameDifferentTypes' => [
                'code' => '<?php
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
            'assertNotSameClasses' => [
                'code' => '<?php
                    /**
                     * Asserts that two variables are the same.
                     *
                     * @template T
                     * @param T      $expected
                     * @param mixed  $actual
                     * @psalm-assert =T $actual
                     */
                    function assertSame($expected, $actual) : void {}

                    class a {}
                    class b {}
                    final class c {}

                    $expected = rand(0, 1) ? new a : new b;
                    $actual = new c;
                    assertSame($expected, $actual);',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'assertNotSameDifferentTypesExplicitString' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param T      $expected
                     * @param mixed  $actual
                     * @param string $message
                     * @psalm-assert !=T $actual
                     * @return void
                     */
                    function assertNotSame($expected, $actual, $message = "") {}

                    class Hello {}

                    function bar(array $j) : void {
                        assertNotSame(new Hello(), $j);
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'dontBleedTemplateTypeInArrayAgain' => [
                'code' => '<?php
                    /**
                     * @psalm-template T
                     * @psalm-param array<T> $array
                     * @psalm-assert array<string, T> $array
                     */
                    function isMap(array $array) : void {}

                    /**
                     * @param array<string> $arr
                     */
                    function bar(array $arr): void {
                        isMap($arr);
                        /** @psalm-trace $arr */
                        $arr;
                    }',
                'error_message' => 'string, string',
            ],
            'SKIPPED-noCrashWhenOnUnparsableTemplatedAssertion' => [
                'code' => '<?php
                    /**
                     * @template TCandidateKey as array-key
                     * @param array $arr
                     * @param TCandidateKey $key
                     * @psalm-assert has-array-key<TCandidateKey> $arr
                     */
                    function keyExists(array $arr, $key) : void {
                        if (!array_key_exists($key, $arr)) {
                            throw new \Exception("bad");
                        }
                    }

                    function fromArray(array $data) : void {
                        keyExists($data, "id");
                        if (is_string($data["id"])) {}
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'assertObjectShapeOnFinalClass' => [
                'code' => '<?php
                    final class Foo
                    {
                    }

                    $foo = new Foo();

                    /** @psalm-assert object{status: string} $bar */
                    function assertObjectShape(object $bar): void {
                    }

                    assertObjectShape($foo);
                    $status = $foo->status;
                ',
                'error_message' => 'Type Foo for $foo is never',
            ],
        ];
    }
}
