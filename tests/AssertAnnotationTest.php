<?php

namespace Psalm\Tests;

use const DIRECTORY_SEPARATOR;

class AssertAnnotationTest extends TestCase
{
    use Traits\ValidCodeAnalysisTestTrait;
    use Traits\InvalidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'implictAssertInstanceOfB' => [
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
            'implicitAssertEqualsNull' => [
                '<?php
                    function takesInt(int $int): void { echo $int; }

                    function getIntOrNull(): ?int {
                        return rand(0,1) === 0 ? null : 1;
                    }

                    /** @param mixed $value */
                    function assertNotNull($value): void {
                        if (null === $value) {
                            throw new Exception();
                        }
                    }

                    $value = getIntOrNull();
                    assertNotNull($value);
                    takesInt($value);',
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
                    }',
            ],
            'dropInReplacementForAntiAssert' => [
                '<?php
                    /**
                     * @param mixed $foo
                     * @psalm-assert falsy $foo
                     */
                    function abort_if($foo): void
                    {
                        if ($foo) {
                            throw new \RuntimeException();
                        }
                    }

                    /**
                     * @param string|null $foo
                     */
                    function removeNullable($foo): string
                    {
                        abort_if(is_null($foo));
                        return $foo;
                    }'
            ],
            'sortOfReplacementForAssert' => [
                '<?php
                    namespace Bar;

                    /**
                     * @param mixed $_b
                     * @psalm-assert true $_b
                     */
                    function myAssert($_b) : void {
                        if ($_b !== true) {
                            throw new \Exception("bad");
                        }
                    }

                    function bar(?string $s) : string {
                        myAssert($s !== null);
                        return $s;
                    }',
            ],
            'implictAssertInstanceOfInterface' => [
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
            'implicitAssertInstanceOfMultipleInterfaces' => [
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
            'implicitAssertInstanceOfBInClassMethod' => [
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
            'implicitAssertPropertyNotNull' => [
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
            'implicitAssertWithoutRedundantCondition' => [
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
                    }',
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
                    }',
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
                ],
            ],
            'assertStaticMethodIfFalse' => [
                '<?php
                    class StringUtility {
                        /**
                         * @psalm-assert-if-false !null $yStr
                         */
                        public static function isNull(?string $yStr): bool {
                            if ($yStr === null) {
                                return true;
                            }
                            return false;
                        }
                    }

                    function test(?string $in) : void {
                        $str = "test";
                        if(!StringUtility::isNull($in)) {
                            $str .= $in;
                        }
                    }',
            ],
            'assertStaticMethodIfTrue' => [
                '<?php
                    class StringUtility {
                        /**
                         * @psalm-assert-if-true !null $yStr
                         */
                        public static function isNotNull(?string $yStr): bool {
                            if ($yStr === null) {
                                return true;
                            }
                            return false;
                        }
                    }

                    function test(?string $in) : void {
                        $str = "test";
                        if(StringUtility::isNotNull($in)) {
                            $str .= $in;
                        }
                    }',
            ],
            'assertUnion' => [
                '<?php
                    class Foo{
                        public function bar() : void {}
                    }

                    /**
                     * @param mixed $b
                     * @psalm-assert int|Foo $b
                     */
                    function assertIntOrFoo($b) : void {
                        if (!is_int($b) && !(is_object($b) && $b instanceof Foo)) {
                            throw new \Exception("bad");
                        }
                    }

                    /** @psalm-suppress MixedAssignment */
                    $a = $_GET["a"];

                    assertIntOrFoo($a);

                    if (!is_int($a)) $a->bar();',
            ],
            'assertThisType' => [
                '<?php
                    class Type {
                        /**
                         * @psalm-assert FooType $this
                         */
                        public function isFoo() : bool {
                            if (!$this instanceof FooType) {
                                throw new \Exception();
                            }

                            return true;
                        }
                    }

                    class FooType extends Type {
                        public function bar(): void {}
                    }

                    function takesType(Type $t) : void {
                        $t->isFoo();
                        $t->bar();
                    }'
            ],
            'assertThisTypeIfTrue' => [
                '<?php
                    class Type {
                        /**
                         * @psalm-assert-if-true FooType $this
                         */
                        public function isFoo() : bool {
                            return $this instanceof FooType;
                        }
                    }

                    class FooType extends Type {
                        public function bar(): void {}
                    }

                    function takesType(Type $t) : void {
                        if ($t->isFoo()) {
                            $t->bar();
                        }
                    }'
            ],
            'assertThisTypeCombined' => [
                '<?php
                    class Type {
                        /**
                         * @psalm-assert FooType $this
                         */
                        public function assertFoo() : void {
                            if (!$this instanceof FooType) {
                                throw new \Exception();
                            }
                        }

                        /**
                         * @psalm-assert BarType $this
                         */
                        public function assertBar() : void {
                            if (!$this instanceof BarType) {
                                throw new \Exception();
                            }
                        }
                    }

                    interface FooType {
                        public function foo(): void;
                    }

                    interface BarType {
                        public function bar(): void;
                    }

                    function takesType(Type $t) : void {
                        $t->assertFoo();
                        $t->assertBar();
                        $t->foo();
                        $t->bar();
                    }'
            ],
            'assertThisTypeCombinedInsideMethod' => [
                '<?php
                    class Type {
                        /**
                         * @psalm-assert FooType $this
                         */
                        public function assertFoo() : void {
                            if (!$this instanceof FooType) {
                                throw new \Exception();
                            }
                        }

                        /**
                         * @psalm-assert BarType $this
                         */
                        public function assertBar() : void {
                            if (!$this instanceof BarType) {
                                throw new \Exception();
                            }
                        }

                        function takesType(Type $t) : void {
                            $t->assertFoo();
                            $t->assertBar();
                            $t->foo();
                            $t->bar();
                        }
                    }

                    interface FooType {
                        public function foo(): void;
                    }

                    interface BarType {
                        public function bar(): void;
                    }
'
            ],
            'assertThisTypeSimpleCombined' => [
                '<?php
                    class Type {
                        /**
                         * @psalm-assert FooType $this
                         */
                        public function assertFoo() : void {
                            if (!$this instanceof FooType) {
                                throw new \Exception();
                            }
                            return;
                        }

                        /**
                         * @psalm-assert BarType $this
                         */
                        public function assertBar() : void {
                            if (!$this instanceof BarType) {
                                throw new \Exception();
                            }
                            return;
                        }
                    }

                    interface FooType {
                        public function foo(): void;
                    }

                    interface BarType {
                        public function bar(): void;
                    }

                    /** @param Type&FooType $t */
                    function takesType(Type $t) : void {
                        $t->assertBar();
                        $t->foo();
                        $t->bar();
                    }'
            ],
            'assertThisTypeIfTrueCombined' => [
                '<?php
                    class Type {
                        /**
                         * @psalm-assert-if-true FooType $this
                         */
                        public function assertFoo() : bool {
                            return $this instanceof FooType;
                        }

                        /**
                         * @psalm-assert-if-true BarType $this
                         */
                        public function assertBar() : bool {
                            return $this instanceof BarType;
                        }
                    }

                    interface FooType {
                        public function foo(): void;
                    }

                    interface BarType {
                        public function bar(): void;
                    }

                    function takesType(Type $t) : void {
                        if ($t->assertFoo() && $t->assertBar()) {
                            $t->foo();
                            $t->bar();
                        }
                    }'
            ],
            'assertThisTypeSimpleAndIfTrueCombined' => [
                '<?php
                    class Type {
                        /**
                         * @psalm-assert BarType $this
                         * @psalm-assert-if-true FooType $this
                         */
                        public function isFoo() : bool {
                            if (!$this instanceof BarType) {
                                throw new \Exception();
                            }
                            return $this instanceof FooType;
                        }
                    }

                    interface FooType {
                        public function foo(): void;
                    }

                    interface BarType {
                        public function bar(): void;
                    }

                    function takesType(Type $t) : void {
                        if ($t->isFoo()) {
                            $t->foo();
                        }
                        $t->bar();
                    }'
            ],
            'assertThisTypeSwitchTrue' => [
                '<?php
                    class Type {
                        /**
                         * @psalm-assert-if-true FooType $this
                         */
                        public function isFoo() : bool {
                            return $this instanceof FooType;
                        }
                    }

                    class FooType extends Type {
                        public function bar(): void {}
                    }

                    function takesType(Type $t) : void {
                        switch (true) {
                            case $t->isFoo():
                                $t->bar();
                        }
                    }'
            ],
            'assertNotArray' => [
                '<?php
                    /**
                     * @param  mixed $value
                     * @psalm-assert !array $value
                     */
                    function myAssertNotArray($value) : void {}

                     /**
                     * @param  mixed $value
                     * @psalm-assert !iterable $value
                     */
                    function myAssertNotIterable($value) : void {}

                    /**
                     * @param  int|array $v
                     */
                    function takesIntOrArray($v) : int {
                        myAssertNotArray($v);
                        return $v;
                    }

                    /**
                     * @param  int|iterable $v
                     */
                    function takesIntOrIterable($v) : int {
                        myAssertNotIterable($v);
                        return $v;
                    }'
            ],
            'assertIfTrueOnProperty' => [
                '<?php
                    class A {
                        public function foo() : void {}
                    }

                    class B {
                        private ?A $a = null;

                        public function bar() : void {
                            if ($this->assertProperty()) {
                                $this->a->foo();
                            }
                        }

                        /**
                         * @psalm-assert-if-true !null $this->a
                         */
                        public function assertProperty() : bool {
                            return $this->a !== null;
                        }
                    }'
            ],
            'assertIfFalseOnProperty' => [
                '<?php
                    class A {
                        public function foo() : void {}
                    }

                    class B {
                        private ?A $a = null;

                        public function bar() : void {
                            if ($this->assertProperty()) {
                                $this->a->foo();
                            }
                        }

                        /**
                         * @psalm-assert-if-false null $this->a
                         */
                        public function assertProperty() : bool {
                            return $this->a !== null;
                        }
                    }'
            ],
            'assertIfTrueOnPropertyNegated' => [
                '<?php
                    class A {
                        public function foo() : void {}
                    }

                    class B {
                        private ?A $a = null;

                        public function bar() : void {
                            if (!$this->assertProperty()) {
                                $this->a->foo();
                            }
                        }

                        /**
                         * @psalm-assert-if-true null $this->a
                         */
                        public function assertProperty() : bool {
                            return $this->a !== null;
                        }
                    }'
            ],
            'assertIfFalseOnPropertyNegated' => [
                '<?php
                    class A {
                        public function foo() : void {}
                    }

                    class B {
                        private ?A $a = null;

                        public function bar() : void {
                            if (!$this->assertProperty()) {
                                $this->a->foo();
                            }
                        }

                        /**
                         * @psalm-assert-if-false !null $this->a
                         */
                        public function assertProperty() : bool {
                            return $this->a !== null;
                        }
                    }'
            ],
            'assertPropertyVisibleOutside' => [
                '<?php
                    class A {
                        public ?int $x = null;

                        public function maybeAssignX() : void {
                            if (rand(0, 0) == 0) {
                                $this->x = 0;
                            }
                        }

                        /**
                         * @psalm-assert !null $this->x
                         */
                        public function assertProperty() : void {
                            if (is_null($this->x)) {
                                throw new RuntimeException();
                            }
                        }
                    }

                    $a = new A();
                    $a->maybeAssignX();
                    $a->assertProperty();
                    echo (2 * $a->x);',
            ],
            'parseAssertion' => [
                '<?php
                    /**
                     * @psalm-assert array<string, string[]> $data
                     * @param mixed $data
                     */
                    function isArrayOfStrings($data): void {}

                    function foo(array $arr) : void {
                        isArrayOfStrings($arr);
                        foreach ($arr as $a) {
                            foreach ($a as $b) {
                                echo $b;
                            }
                        }
                    }'
            ],
            'noExceptionOnShortArrayAssertion' => [
                '<?php
                    /**
                     * @param mixed[] $a
                     */
                    function one(array $a): void {
                      isInts($a);
                    }

                    /**
                     * @psalm-assert int[] $value
                     * @param mixed $value
                     */
                    function isInts($value): void {}',
            ],
            'simpleArrayAssertion' => [
                '<?php
                    /**
                     * @psalm-assert array $data
                     * @param mixed $data
                     */
                    function isArray($data): void {}

                    /**
                     * @param iterable<string> $arr
                     * @return array<string>
                     */
                    function foo(iterable $arr) : array {
                        isArray($arr);
                        return $arr;
                    }'
            ],
            'listAssertion' => [
                '<?php
                    /**
                     * @psalm-assert list $data
                     * @param mixed $data
                     */
                    function isList($data): void {}

                    /**
                     * @param array<string> $arr
                     * @return list<string>
                     */
                    function foo(array $arr) : array {
                        isList($arr);
                        return $arr;
                    }'
            ],
            'scanAssertionTypes' => [
                '<?php
                    /**
                     * @param mixed $_p
                     * @psalm-assert-if-true Exception $_p
                     * @psalm-assert-if-false Error $_p
                     * @psalm-assert Throwable $_p
                     */
                    function f($_p): bool {
                        return true;
                    }

                    $q = null;
                    if (rand(0, 1) && f($q)) {}
                    if (!f($q)) {}'
            ],
            'assertDifferentTypeOfArray' => [
                '<?php
                    /**
                     * @psalm-assert array{0: string, 1: string} $value
                     * @param mixed $value
                     */
                    function isStringTuple($value): void {
                        if (!is_array($value)
                            || !isset($value[0])
                            || !isset($value[1])
                            || !is_string($value[0])
                            || !is_string($value[1])
                        ) {
                            throw new \Exception("bad");
                        }
                    }

                    $s = "";

                    $parts = explode(":", $s, 2);

                    isStringTuple($parts);

                    echo $parts[0];
                    echo $parts[1];'
            ],
            'assertStringOrIntOnString' => [
                '<?php
                    /**
                     * @param mixed $v
                     * @psalm-assert string|int $v
                     */
                    function assertStringOrInt($v) : void {}

                    function gimmeAString(?string $v): string {
                        /** @psalm-suppress TypeDoesNotContainType */
                        assertStringOrInt($v);

                        return $v;
                    }',
            ],
            'assertIfTrueWithSpace' => [
                '<?php
                    /**
                     * @param mixed $data
                     * @return bool
                     * @psalm-assert-if-true array{type: string} $data
                     */
                    function isBar($data) {
                        return isset($data["type"]);
                    }

                    /**
                     * @param mixed $data
                     * @return string
                     */
                    function doBar($data) {
                        if (isBar($data)) {
                            return $data["type"];
                        }

                        throw new \Exception();
                    }'
            ],
            'assertOnNestedProperty' => [
                '<?php
                    /** @psalm-immutable */
                    class B {
                        public ?array $arr = null;

                        public function __construct(?array $arr) {
                            $this->arr = $arr;
                        }
                    }

                    /** @psalm-immutable */
                    class A {
                        public B $b;
                        public function __construct(B $b) {
                            $this->b = $b;
                        }

                        /** @psalm-assert-if-true !null $this->b->arr */
                        public function hasArray() : bool {
                            return $this->b->arr !== null;
                        }
                    }

                    function foo(A $a) : void {
                        if ($a->hasArray()) {
                            echo count($a->b->arr);
                        }
                    }'
            ],
            'assertOnNestedMethod' => [
                '<?php
                    /** @psalm-immutable */
                    class B {
                        private ?array $arr = null;

                        public function __construct(?array $arr) {
                            $this->arr = $arr;
                        }

                        public function getArray() : ?array {
                            return $this->arr;
                        }
                    }

                    /** @psalm-immutable */
                    class A {
                        public B $b;
                        public function __construct(B $b) {
                            $this->b = $b;
                        }

                        /** @psalm-assert-if-true !null $this->b->getarray() */
                        public function hasArray() : bool {
                            return $this->b->getArray() !== null;
                        }
                    }

                    function foo(A $a) : void {
                        if ($a->hasArray()) {
                            echo count($a->b->getArray());
                        }
                    }'
            ],
            'assertOnThisMethod' => [
                '<?php
                    /** @psalm-immutable */
                    class A {
                        private ?array $arr = null;

                        public function __construct(?array $arr) {
                            $this->arr = $arr;
                        }

                        /** @psalm-assert-if-true !null $this->getarray() */
                        public function hasArray() : bool {
                            return $this->arr !== null;
                        }

                        public function getArray() : ?array {
                            return $this->arr;
                        }
                    }

                    function foo(A $a) : void {
                        if (!$a->hasArray()) {
                            return;
                        }

                        echo count($a->getArray());
                    }'
            ],
            'preventErrorWhenAssertingOnArrayUnion' => [
                '<?php
                    /**
                     * @psalm-assert array<string,string|object> $data
                     */
                    function validate(array $data): void {}'
            ],
            'nonEmptyList' => [
                '<?php
                    /**
                     * @psalm-assert non-empty-list $array
                     *
                     * @param mixed  $array
                     */
                    function isNonEmptyList($array): void {}

                    /**
                     * @psalm-param mixed $value
                     *
                     * @psalm-return non-empty-list<mixed>
                     */
                    function consume1($value): array {
                        isNonEmptyList($value);
                        return $value;
                    }

                    /**
                     * @psalm-param list<string> $values
                     */
                    function consume2(array $values): void {
                        isNonEmptyList($values);
                        foreach ($values as $str) {}
                        echo $str;
                    }'
            ],
            'nonEmptyListOfStrings' => [
                '<?php
                    /**
                     * @psalm-assert non-empty-list<string> $array
                     *
                     * @param mixed  $array
                     */
                    function isNonEmptyListOfStrings($array): void {}

                    /**
                     * @psalm-param list<string> $values
                     */
                    function consume2(array $values): void {
                        isNonEmptyListOfStrings($values);
                        foreach ($values as $str) {}
                        echo $str;
                    }'
            ],
            'assertResource' => [
                '<?php
                    /**
                     * @param  mixed $foo
                     * @psalm-assert resource $foo
                     */
                    function assertResource($foo) : void {
                        if (!is_resource($foo)) {
                            throw new \Exception("bad");
                        }
                    }
                    /**
                     * @param mixed $value
                     *
                     * @return resource
                     */
                    function consume($value)
                    {
                        assertResource($value);

                        return $value;
                    }'
            ],
            'parseLongAssertion' => [
                '<?php
                    /**
                     * @psalm-assert array{
                     *      extensions: array<string, array{
                     *          version?: string,
                     *          type?: "bundled"|"pecl",
                     *          require?: list<string>,
                     *          env?: array<string, array{
                     *              deps?: list<string>,
                     *              buildDeps?: list<string>,
                     *              configure?: string
                     *          }>
                     *      }>
                     * } $data
                     *
                     * @param mixed $data
                     */
                    function assertStructure($data): void {}'
            ],
            'intersectArraysAfterAssertion' => [
                '<?php
                    /**
                     * @psalm-assert array{foo: string} $v
                     */
                    function hasFoo(array $v): void {}

                    /**
                     * @psalm-assert array{bar: int} $v
                     */
                    function hasBar(array $v): void {}

                    function process(array $data): void {
                        hasFoo($data);
                        hasBar($data);

                        echo sprintf("%s %d", $data["foo"], $data["bar"]);
                    }'
            ],
            'assertListIsIterableOfStrings' => [
                '<?php
                    /**
                     * @psalm-assert iterable<string> $value
                     *
                     * @param mixed  $value
                     *
                     * @throws InvalidArgumentException
                     */
                    function allString($value): void {}

                    function takesAnArray(array $a): void {
                        $keys = array_keys($a);
                        allString($keys);
                    }',
            ],
            'assertListIsListOfStrings' => [
                '<?php
                    /**
                     * @psalm-assert list<string> $value
                     *
                     * @param mixed  $value
                     *
                     * @throws InvalidArgumentException
                     */
                    function allString($value): void {}

                    function takesAnArray(array $a): void {
                        $keys = array_keys($a);
                        allString($keys);
                    }',
            ],
            'multipleAssertIfTrue' => [
                '<?php
                    /**
                     * @param mixed $a
                     * @param mixed $b
                     * @psalm-assert-if-true string $a
                     * @psalm-assert-if-true string $b
                     */
                    function assertAandBAreStrings($a, $b): bool {
                        if (!is_string($a)) { return false;}
                        if (!is_string($b)) { return false;}

                        return true;
                    }

                    /**
                     * @param mixed $a
                     * @param mixed $b
                     */
                    function test($a, $b): string {
                        if (!assertAandBAreStrings($a, $b)) {
                            throw new \Exception();
                        }

                        return substr($a, 0, 1) . substr($b, 0, 1);
                    }'
            ],
            'convertConstStringType' => [
                '<?php
                    class A {
                        const T1  = 1;
                        const T2 = 2;

                        /**
                         * @param self::T* $t
                         */
                        public static function bar(int $t):void {}

                        /**
                         * @psalm-assert-if-true self::T* $t
                         */
                        public static function isValid(int $t): bool {
                            return in_array($t, [self::T1, self::T2], true);
                        }
                    }


                    function takesA(int $a) : void {
                        if (A::isValid($a)) {
                            A::bar($a);
                        }
                    }'
            ],
            'multipleAssertIfTrueOnSameVariable' => [
                '<?php
                    class A {}

                    function foo(string|null|A $a) : A {
                        if (isComputed($a)) {
                            return $a;
                        }

                        throw new Exception("bad");
                    }

                    /**
                     * @psalm-assert-if-true !null $value
                     * @psalm-assert-if-true !string $value
                     */
                    function isComputed(mixed $value): bool {
                        return $value !== null && !is_string($value);
                    }',
                [],
                [],
                '8.0'
            ],
            'assertStaticSelf' => [
                '<?php
                    final class C {
                        /** @var null|int */
                        private static $q = null;

                        /** @psalm-assert int self::$q */
                        private static function prefillQ(): void {
                            self::$q = 123;
                        }

                        public static function getQ(): int {
                            self::prefillQ();
                            return self::$q;
                        }
                    }
                ?>'
            ],
            'assertIfTrueStaticSelf' => [
                '<?php
                    final class C {
                        /** @var null|int */
                        private static $q = null;

                        /** @psalm-assert-if-true int self::$q */
                        private static function prefillQ(): bool {
                            if (rand(0,1)) {
                                self::$q = 123;
                                return true;
                            }
                            return false;
                        }

                        public static function getQ(): int {
                            if (self::prefillQ()) {
                                return self::$q;
                            }
                            return -1;
                        }
                    }
                ?>'
            ],
            'assertIfFalseStaticSelf' => [
                '<?php
                    final class C {
                        /** @var null|int */
                        private static $q = null;

                        /** @psalm-assert-if-false int self::$q */
                        private static function prefillQ(): bool {
                            if (rand(0,1)) {
                                self::$q = 123;
                                return false;
                            }
                            return true;
                        }

                        public static function getQ(): int {
                            if (self::prefillQ()) {
                                return -1;
                            }
                            return self::$q;
                        }
                    }
                ?>'
            ],
            'assertStaticByInheritedMethod' => [
                '<?php
                    class A {
                        /** @var null|int */
                        protected static $q = null;

                        /** @psalm-assert int self::$q */
                        protected static function prefillQ(): void {
                            self::$q = 123;
                        }
                    }

                    class B extends A {
                        public static function getQ(): int {
                            self::prefillQ();
                            return self::$q;
                        }
                    }
                ?>'
            ],
            'assertInheritedStatic' => [
                '<?php
                    class A {
                        /** @var null|int */
                        protected static $q = null;
                    }

                    class B extends A {
                        /** @psalm-assert int self::$q */
                        protected static function prefillQ(): void {
                            self::$q = 123;
                        }
                        public static function getQ(): int {
                            self::prefillQ();
                            return self::$q;
                        }
                    }
                ?>'
            ],
            'assertStaticOnUnrelatedClass' => [
                '<?php
                    class A {
                        /** @var null|int */
                        public static $q = null;
                    }

                    class B {
                        /** @psalm-assert int A::$q */
                        private static function prefillQ(): void {
                            A::$q = 123;
                        }
                        public static function getQ(): int {
                            self::prefillQ();
                            return A::$q;
                        }
                    }
                ?>'
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse(): iterable
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

                    // Returns concreate implementation of Foo, which in this case is Bar
                    function getImplementationOfFoo(): Foo {
                        return new Bar();
                    }

                    $bar = getImplementationOfFoo();
                    assertInstanceOf($bar, Bar::class);

                    $bar->sayHello();',
                'error_message' => 'UndefinedDocblockClass',
            ],
            'assertValueImpossible' => [
                '<?php
                    /**
                     * @psalm-assert "foo"|"bar"|"foo-bar" $s
                     */
                    function assertFooBar(string $s) : void {
                    }

                    $a = "";
                    assertFooBar($a);',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'sortOfReplacementForAssert' => [
                '<?php
                    namespace Bar;

                    /**
                     * @param mixed $_b
                     * @psalm-assert true $_b
                     */
                    function myAssert($_b) : void {
                        if ($_b !== true) {
                            throw new \Exception("bad");
                        }
                    }

                    function bar(?string $s) : string {
                        myAssert($s);
                        return $s;
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'assertScalarAndEmpty' => [
                '<?php
                    /**
                     * @param mixed $value
                     * @psalm-assert scalar $value
                     * @psalm-assert !empty $value
                     */
                    function assertScalarNotEmpty($value) : void {}

                    /** @param scalar $s */
                    function takesScalar($s) : void {}

                    /**
                     * @param mixed $bar
                     */
                    function foo($bar) : void {
                        assertScalarNotEmpty($bar);
                        takesScalar($bar);

                        if ($bar) {}
                    }',
                'error_message' => 'RedundantConditionGivenDocblockType - src'
                                    . DIRECTORY_SEPARATOR . 'somefile.php:19:29',
            ],
            'assertOneOfStrings' => [
                '<?php
                    /**
                     * @psalm-assert "a"|"b" $s
                     */
                    function foo(string $s) : void {}

                    function takesString(string $s) : void {
                        foo($s);
                        if ($s === "c") {}
                    }',
                'error_message' => 'DocblockTypeContradiction',
            ],
            'assertThisType' => [
                '<?php
                    class Type {
                        /**
                         * @psalm-assert FooType $this
                         */
                        public function isFoo() : bool {
                            if (!$this instanceof FooType) {
                                throw new \Exception();
                            }

                            return true;
                        }
                    }

                    class FooType extends Type {
                        public function bar(): void {}
                    }

                    function takesType(Type $t) : void {
                        $t->bar();
                        $t->isFoo();
                    }',
                'error_message' => 'UndefinedMethod',
            ],
            'invalidUnionAssertion' => [
                '<?php
                    interface I {
                        /**
                         * @psalm-assert null|!ExpectedType $value
                         */
                        public static function foo($value);
                    }',
                'error_message' => 'InvalidDocblock',
            ],
        ];
    }
}
