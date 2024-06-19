<?php

namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

class AssertAnnotationTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    public function testDontForgetAssertionAfterMutationFreeCall(): void
    {
        Config::getInstance()->remember_property_assignments_after_call = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class Foo
                {
                    public ?string $bar = null;

                    /** @psalm-mutation-free */
                    public function mutationFree(): void {}
                }

                /**
                 * @psalm-assert-if-true !null $foo->bar
                 */
                function assertBarNotNull(Foo $foo): bool
                {
                    return $foo->bar !== null;
                }

                $foo = new Foo();

                if (assertBarNotNull($foo)) {
                    $foo->mutationFree();
                    requiresString($foo->bar);
                }

                function requiresString(string $str): void {}
            ',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testForgetAssertionAfterNonMutationFreeCall(): void
    {
        $this->expectExceptionMessage('PossiblyNullArgument');
        $this->expectException(CodeException::class);
        Config::getInstance()->remember_property_assignments_after_call = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class Foo
                {
                    public ?string $bar = null;

                    public function nonMutationFree(): void {}
                }

                /**
                 * @psalm-assert-if-true !null $foo->bar
                 */
                function assertBarNotNull(Foo $foo): bool
                {
                    return $foo->bar !== null;
                }

                $foo = new Foo();

                if (assertBarNotNull($foo)) {
                    $foo->nonMutationFree();
                    requiresString($foo->bar);
                }

                function requiresString(string $_str): void {}
            ',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testAssertsAlongCallStaticMethodWork(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php

            class ImportedAssert
            {
                /** @psalm-assert non-empty-string $b */
                public static function notEmptyStrOnly(string $b): void
                {
                    if ("" === $b) throw new \Exception("");
                }

                public function __callStatic() {}
            }

            /** @return non-empty-string */
            function returnNonEmpty(string $b): string
            {
                ImportedAssert::notEmptyStrOnly($b);

                return $b;
            }
            ',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testAssertInvalidDocblockMessageDoesNotIncludeTrace(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessageMatches(
            '!^InvalidDocblock - ' . 'somefile\\.php:10:5 - Invalid @psalm-assert union type: Invalid type \'\\$expected\'$!',
        );

        $this->addFile(
            'somefile.php',
            <<<'PHP'
            <?php
                /**
                 * Asserts that two variables are not the same.
                 *
                 * @template T
                 * @param T      $expected
                 * @param mixed  $actual
                 * @psalm-assert !=$expected $actual
                 */
                function assertNotSame($expected, $actual) : void {}
            PHP,
        );

        $this->analyzeFile('somefile.php', new Context());
    }


    public function providerValidCodeParse(): iterable
    {
        return [
            'implicitAssertInstanceOfB' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                    }',
            ],
            'sortOfReplacementForAssert' => [
                'code' => '<?php
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
            'implicitAssertInstanceOfInterface' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    namespace Bar;

                    /**
                     * @param mixed $data
                     * @throws \Exception
                     */
                    function assertIsLongString($data): void {
                        if (!\is_string($data)) {
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
            'assertSessionVar' => [
                'code' => '<?php
                    namespace Bar;

                    /**
                     * @psalm-assert-if-true string $a
                     * @param mixed $a
                     */
                    function my_is_string($a) : bool
                    {
                        return is_string($a);
                    }

                    if (my_is_string($_SESSION["abc"])) {
                        $i = substr($_SESSION["abc"], 1, 2);
                    }',
            ],
            'dontBleedBadAssertVarIntoContext' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'assertions' => [
                    '$array' => 'array<array-key, string>',
                    '$iterable' => 'iterable<mixed, string>',
                ],
            ],
            'assertStaticMethodIfFalse' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                    $a = $GLOBALS["a"];

                    assertIntOrFoo($a);

                    if (!is_int($a)) $a->bar();',
            ],
            'assertThisType' => [
                'code' => '<?php
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
                    }',
            ],
            'assertThisTypeIfTrue' => [
                'code' => '<?php
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
                    }',
            ],
            'assertThisTypeCombined' => [
                'code' => '<?php
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
                    }',
            ],
            'assertThisTypeCombinedInsideMethod' => [
                'code' => '<?php
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
',
            ],
            'assertThisTypeSimpleCombined' => [
                'code' => '<?php
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
                    }',
            ],
            'assertThisTypeIfTrueCombined' => [
                'code' => '<?php
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
                    }',
            ],
            'assertThisTypeSimpleAndIfTrueCombined' => [
                'code' => '<?php
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
                    }',
            ],
            'assertThisTypeSwitchTrue' => [
                'code' => '<?php
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
                    }',
            ],
            'assertNotArray' => [
                'code' => '<?php
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
                    }',
            ],
            'assertIfTrueOnProperty' => [
                'code' => '<?php
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
                    }',
            ],
            'assertIfFalseOnProperty' => [
                'code' => '<?php
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
                    }',
            ],
            'assertIfTrueOnPropertyNegated' => [
                'code' => '<?php
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
                    }',
            ],
            'assertIfFalseOnPropertyNegated' => [
                'code' => '<?php
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
                    }',
            ],
            'assertPropertyVisibleOutside' => [
                'code' => '<?php
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
                'code' => '<?php
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
                    }',
            ],
            'noExceptionOnShortArrayAssertion' => [
                'code' => '<?php
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
                'code' => '<?php
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
                    }',
            ],
            'listAssertion' => [
                'code' => '<?php
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
                    }',
            ],
            'scanAssertionTypes' => [
                'code' => '<?php
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
                    if (!f($q)) {}',
            ],
            'assertDifferentTypeOfArray' => [
                'code' => '<?php
                    /**
                     * @psalm-assert list{string, string} $value
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

                    $s = "Hello World!";

                    $parts = explode(":", $s, 2);

                    isStringTuple($parts);

                    echo $parts[0];
                    echo $parts[1];',
            ],
            'assertStringOrIntOnString' => [
                'code' => '<?php
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
                'code' => '<?php
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
                    }',
            ],
            'assertOnNestedProperty' => [
                'code' => '<?php
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
                    }',
            ],
            'assertOnNestedMethod' => [
                'code' => '<?php
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
                    }',
            ],
            'assertOnThisMethod' => [
                'code' => '<?php
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
                    }',
            ],
            'preventErrorWhenAssertingOnArrayUnion' => [
                'code' => '<?php
                    /**
                     * @psalm-assert array<string,string|object> $data
                     */
                    function validate(array $data): void {}',
            ],
            'nonEmptyList' => [
                'code' => '<?php
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
                    }',
            ],
            'nonEmptyListOfStrings' => [
                'code' => '<?php
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
                    }',
            ],
            'assertResource' => [
                'code' => '<?php
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
                    }',
            ],
            'parseLongAssertion' => [
                'code' => '<?php
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
                    function assertStructure($data): void {}',
            ],
            'intersectArraysAfterAssertion' => [
                'code' => '<?php
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
                    }',
            ],
            'assertListIsIterableOfStrings' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                    }',
            ],
            'convertConstStringType' => [
                'code' => '<?php
                    class A {
                        const T1 = 1;
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
                    }',
            ],
            'multipleAssertIfTrueOnSameVariable' => [
                'code' => '<?php
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
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'assertStaticSelf' => [
                'code' => '<?php
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
                ?>',
            ],
            'assertIfTrueStaticSelf' => [
                'code' => '<?php
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
                ?>',
            ],
            'assertIfFalseStaticSelf' => [
                'code' => '<?php
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
                ?>',
            ],
            'assertStaticByInheritedMethod' => [
                'code' => '<?php
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
                ?>',
            ],
            'assertInheritedStatic' => [
                'code' => '<?php
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
                ?>',
            ],
            'assertStaticOnUnrelatedClass' => [
                'code' => '<?php
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
                ?>',
            ],
            'implicitComplexAssertionNoCrash' => [
                'code' => '<?php
                    class Foo {
                        private string $status = "";

                        public function assertValidStatusTransition(string $status): void
                        {
                            if (
                                ("canceled" === $this->status && "complete" === $status)
                                || ("canceled" === $this->status && "pending" === $status)
                                || ("complete" === $this->status && "canceled" === $status)
                                || ("complete" === $this->status && "pending" === $status)
                            ) {
                                throw new \LogicException();
                            }
                        }
                    }',
            ],
            'assertArrayIteratorIsIterableOfStrings' => [
                'code' => '<?php
                    /**
                     * @psalm-assert iterable<string> $value
                     * @param mixed $value
                     *
                     * @return void
                     */
                    function assertAllString($value) : void {
                        throw new \Exception(\var_export($value, true));
                    }

                    /**
                     * @param ArrayIterator<string, mixed> $value
                     *
                     * @return ArrayIterator<string, string>
                     */
                    function preserveContainerAllArrayIterator($value) {
                        assertAllString($value);
                        return $value;
                    }',
            ],
            'implicitReflectionParameterAssertion' => [
                'code' => '<?php
                    $method = new ReflectionMethod(stdClass::class);
                    $parameters = $method->getParameters();
                    foreach ($parameters as $parameter) {
                        if ($parameter->hasType()) {
                            $parameter->getType()->__toString();
                        }
                    }',
            ],
            'reflectionNameTypeClassStringIfNotBuiltin' => [
                'code' => '<?php
                    /** @return class-string|null */
                    function getPropertyType(\ReflectionProperty $reflectionItem): ?string {
                        $type = $reflectionItem->getType();
                        return ($type instanceof \ReflectionNamedType) && !$type->isBuiltin() ? $type->getName() : null;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'withHasTypeCall' => [
                'code' => '<?php
                    /**
                     * @psalm-immutable
                     */
                    class Param {
                        /**
                         * @psalm-assert-if-true ReflectionType $this->getType()
                         */
                        public function hasType() : bool {
                            return true;
                        }

                        public function getType() : ?ReflectionType {
                            return null;
                        }
                    }

                    function takesParam(Param $p) : void {
                        if ($p->hasType()) {
                            echo $p->getType()->__toString();
                        }
                    }',
            ],
            'assertTemplatedIterable' => [
                'code' => '<?php
                    class Foo{}

                    /**
                     * @param array<Foo> $foos
                     * @return array<Foo>
                     */
                    function foo(array $foos) : array {
                        allIsInstanceOf($foos, Foo::class);
                        return $foos;
                    }

                    /**
                     * @template ExpectedType of object
                     *
                     * @param mixed $value
                     * @param class-string<ExpectedType> $class
                     * @psalm-assert iterable<ExpectedType> $value
                     */
                    function allIsInstanceOf($value, $class): void {}',
            ],
            'implicitReflectionPropertyAssertion' => [
                'code' => '<?php
                    $class = new ReflectionClass(stdClass::class);
                    $properties = $class->getProperties();
                    foreach ($properties as $property) {
                        if ($property->hasType()) {
                            $property->getType()->allowsNull();
                        }
                    }',
                    'assertions' => [],
                    'ignored_issues' => [],
                    'php_version' => '7.4',
            ],
            'onPropertyOfImmutableArgument' => [
                'code' => '<?php
                    /** @psalm-immutable */
                    class Aclass {
                        public ?string $b;
                        public function __construct(?string $b) {
                            $this->b = $b;
                        }
                    }

                    /** @psalm-assert !null $item->b */
                    function c(\Aclass $item): void {
                        if (null === $item->b) {
                            throw new \InvalidArgumentException("");
                        }
                    }

                    /** @var \Aclass $a */
                    c($a);
                    echo strlen($a->b);',
            ],
            'inTrueOnPropertyOfImmutableArgument' => [
                'code' => '<?php
                    /** @psalm-immutable */
                    class A {
                        public ?int $b;
                        public function __construct(?int $b) {
                            $this->b = $b;
                        }
                    }

                    /** @psalm-assert-if-true !null $item->b */
                    function c(A $item): bool {
                        return null !== $item->b;
                    }

                    function check(int $a): void {}

                    /** @var A $a */

                    if (c($a)) {
                        check($a->b);
                    }',
            ],
            'inFalseOnPropertyOfAImmutableArgument' => [
                'code' => '<?php
                    /** @psalm-immutable */
                    class A {
                        public ?int $b;
                        public function __construct(?int $b) {
                            $this->b = $b;
                        }
                    }

                    /** @psalm-assert-if-false !null $item->b */
                    function c(A $item): bool {
                        return null === $item->b;
                    }

                    function check(int $a): void {}

                    /** @var A $a */

                    if (!c($a)) {
                        check($a->b);
                    }',
            ],
            'ifTrueOnNestedPropertyOfArgument' => [
                'code' => '<?php
                    class B {
                        public ?string $c;
                        public function __construct(?string $c) {
                            $this->c = $c;
                        }
                    }

                    /** @psalm-immutable */
                    class Aclass {
                        public B $b;
                        public function __construct(B $b) {
                            $this->b = $b;
                        }
                    }

                    /** @psalm-assert-if-true !null $item->b->c */
                    function c(\Aclass $item): bool {
                        return null !== $item->b->c;
                    }

                    $a = new \Aclass(new \B(null));
                    if (c($a)) {
                        echo strlen($a->b->c);
                    }',
            ],
            'ifFalseOnNestedPropertyOfArgument' => [
                'code' => '<?php
                    class B {
                        public ?string $c;
                        public function __construct(?string $c) {
                            $this->c = $c;
                        }
                    }

                    /** @psalm-immutable */
                    class Aclass {
                        public B $b;
                        public function __construct(B $b) {
                            $this->b = $b;
                        }
                    }

                    /** @psalm-assert-if-false !null $item->b->c */
                    function c(\Aclass $item): bool {
                        return null !== $item->b->c;
                    }

                    $a = new \Aclass(new \B(null));
                    if (!c($a)) {
                        echo strlen($a->b->c);
                    }',
            ],
            'assertOnKeyedArrayWithClassStringOffset' => [
                'code' => '<?php

                    class A
                    {
                        function test(): void
                        {
                            $a = [stdClass::class => ""];

                            /** @var array<class-string, mixed> $b */
                            $b = [];

                            $this->assertSame($a, $b);
                        }

                        /**
                         * @template T
                         * @param T      $expected
                         * @param mixed  $actual
                         * @psalm-assert =T $actual
                         */
                        public function assertSame($expected, $actual): void
                        {
                            return;
                        }
                    }',
            ],
            'assertOnKeyedArrayWithSpecialCharsInNames' => [
                'code' => '<?php

                    class Foo {
                        /** @var array<string, int> */
                        public array $bar;

                        /**
                         * @param array<string, int> $bar
                         */
                        public function __construct(array $bar) {
                            $this->bar = $bar;
                        }
                    }

                    $expected = [
                        "#[]" => 21,
                        "<<>>" => 6,
                    ];

                    $foo = new Foo($expected);
                    assertSame($expected, $foo->bar);

                    /**
                     * @psalm-template ExpectedType
                     * @psalm-param ExpectedType $expected
                     * @psalm-param mixed $actual
                     * @psalm-assert =ExpectedType $actual
                     */
                    function assertSame($expected, $actual): void {
                        if ($expected !== $actual) {
                            throw new Exception("Expected doesn\'t match actual");
                        }
                    }',
            ],
            'dontForgetAssertionAfterIrrelevantNonMutationFreeCall' => [
                'code' => '<?php
                    class Foo
                    {
                        public ?string $bar = null;

                        public function nonMutationFree(): void {}
                    }

                    /**
                     * @psalm-assert-if-true !null $foo->bar
                     */
                    function assertBarNotNull(Foo $foo): bool
                    {
                        return $foo->bar !== null;
                    }

                    $foo = new Foo();

                    if (assertBarNotNull($foo)) {
                        $foo->nonMutationFree();
                        requiresString($foo->bar);
                    }

                    function requiresString(string $_str): void {}
                ',
            ],
            'referencesDontBreakAssertions' => [
                'code' => '<?php
                    /** @var string|null */
                    $foo = "";
                    $bar = &$foo;
                    $baz = &$foo;

                    if (assertNotNull($foo)) {
                        requiresString($foo);
                    }

                    /**
                     * @param mixed $foo
                     * @psalm-assert-if-true !null $foo
                     */
                    function assertNotNull($foo): bool
                    {
                        return $foo !== null;
                    }

                    function requiresString(string $_str): void {}
                ',
            ],
            'applyAssertionsToReferences' => [
                'code' => '<?php
                    /** @var string|null */
                    $foo = "";
                    $bar = &$foo;

                    if (assertNotNull($foo)) {
                        requiresString($bar);
                    }

                    /**
                     * @param mixed $foo
                     * @psalm-assert-if-true !null $foo
                     */
                    function assertNotNull($foo): bool
                    {
                        return $foo !== null;
                    }

                    function requiresString(string $_str): void {}
                ',
            ],
            'applyAssertionsFromReferences' => [
                'code' => '<?php
                    /** @var string|null */
                    $foo = "";
                    $bar = &$foo;

                    if (assertNotNull($bar)) {
                        requiresString($foo);
                    }

                    /**
                     * @param mixed $foo
                     * @psalm-assert-if-true !null $foo
                     */
                    function assertNotNull($foo): bool
                    {
                        return $foo !== null;
                    }

                    function requiresString(string $_str): void {}
                ',
            ],
            'applyAssertionsOnPropertiesToReferences' => [
                'code' => '<?php
                    class Foo
                    {
                        public ?string $bar = null;
                    }

                    /**
                     * @psalm-assert-if-true !null $foo->bar
                     */
                    function assertBarNotNull(Foo $foo): bool
                    {
                        return $foo->bar !== null;
                    }

                    $foo = new Foo();
                    $bar = &$foo;

                    if (assertBarNotNull($foo)) {
                        requiresString($bar->bar);
                    }

                    function requiresString(string $_str): void {}
                ',
            ],
            'applyAssertionsOnPropertiesFromReferences' => [
                'code' => '<?php
                    class Foo
                    {
                        public ?string $bar = null;
                    }

                    /**
                     * @psalm-assert-if-true !null $foo->bar
                     */
                    function assertBarNotNull(Foo $foo): bool
                    {
                        return $foo->bar !== null;
                    }

                    $foo = new Foo();
                    $bar = &$foo;

                    if (assertBarNotNull($bar)) {
                        requiresString($foo->bar);
                    }

                    function requiresString(string $_str): void {}
                ',
            ],
            'applyAssertionsOnPropertiesToReferencesWithConditionalOperator' => [
                'code' => '<?php
                    class Foo
                    {
                        public ?string $bar = null;
                    }

                    /**
                     * @psalm-assert-if-true !null $foo->bar
                     */
                    function assertBarNotNull(Foo $foo): bool
                    {
                        return $foo->bar !== null;
                    }

                    $foo = new Foo();
                    $bar = &$foo;

                    requiresString(assertBarNotNull($foo) ? $bar->bar : "bar");

                    function requiresString(string $_str): void {}
                ',
            ],
            'assertInArrayWithTemplateDontCrash' => [
                'code' => '<?php
                    class A{
                        /**
                         * @template T
                         * @param array<T> $objects
                         * @return array<T>
                         */
                        private function uniquateObjects(array $objects) : array
                        {
                            $uniqueObjects = [];
                            foreach ($objects as $object) {
                                if (in_array($object, $uniqueObjects, true)) {
                                    continue;
                                }
                                $uniqueObjects[] = $object;
                            }

                            return $uniqueObjects;
                        }
                    }
                ',
            ],
            'assertionOnMagicProperty' => [
                'code' => '<?php
                    /**
                     * @property ?string $b
                     */
                    class A {
                        /** @psalm-mutation-free */
                        public function __get(string $key) {return "";}
                        public function __set(string $key, string $value): void {}
                    }

                    $a = new A;

                    /** @psalm-assert-if-true  string $arg->b */
                    function assertString(A $arg): bool {return $arg->b !== null;}

                    if (assertString($a)) {
                        requiresString($a->b);
                    }

                    function requiresString(string $_str): void {}
                ',
            ],
            'assertionOnPropertyReturnedByMethod' => [
                'code' => '<?php
                    class a {
                        public ?int $id = null;
                        /**
                         * @psalm-mutation-free
                         *
                         * @psalm-assert-if-true !null $this->id
                         */
                        public function isExists(): bool {
                            return $this->id !== null;
                        }
                    }

                    class b {
                        public ?int $id = null;
                        public function __construct(private a $a) {
                            if ($this->getA()->isExists()) {
                                /** @psalm-check-type-exact $this->id = ?int */
                            }
                        }
                        public function getA(): a { return $this->a; }
                    }',
            ],
            'assertWithEmptyStringOnKeyedArray' => [
                'code' => '<?php
                    class A
                    {
                        function test(): void
                        {
                            $a = ["" => ""];

                            /** @var array<string, mixed> $b */
                            $b = [];

                            $this->assertSame($a, $b);
                        }

                        /**
                         * @template T
                         * @param T      $expected
                         * @param mixed $actual
                         * @psalm-assert =T $actual
                         */
                        public function assertSame($expected, $actual): void
                        {
                            return;
                        }
                    }
                ',
            ],
            'assertNonEmptyStringWithLowercaseString' => [
                'code' => '<?php

                    /** @psalm-assert non-empty-string $input */
                    function assertLowerCase(string $input): void { throw new \Exception($input . " irrelevant"); }

                    /**
                     * @param lowercase-string $input
                     * @return non-empty-lowercase-string
                     */
                    function makeLowerNonEmpty(string $input): string
                    {
                        assertLowerCase($input);

                        return $input;
                    }',
            ],
            'assertOneOfValuesWithinArray' => [
                'code' => '<?php

                    /**
                     * @template T
                     * @param mixed $input
                     * @param array<array-key,T> $values
                     * @psalm-assert =T $input
                     */
                    function assertOneOf($input, array $values): void {}

                    /** @param "a" $value */
                    function consumeSpecificStringValue(string $value): void {}

                    /** @param literal-string $value */
                    function consumeLiteralStringValue(string $value): void {}

                    function consumeAnyIntegerValue(int $value): void {}

                    function consumeAnyFloatValue(float $value): void {}

                    /** @var string $string */
                    $string;

                    /** @var string $anotherString */
                    $anotherString;

                    /** @var null|string $nullableString */
                    $nullableString;

                    /** @var mixed $maybeInt */
                    $maybeInt;
                    /** @var mixed $maybeFloat */
                    $maybeFloat;

                    assertOneOf($string, ["a"]);
                    consumeSpecificStringValue($string);

                    assertOneOf($anotherString, ["a", "b", "c"]);
                    consumeLiteralStringValue($anotherString);

                    assertOneOf($nullableString, ["a", "b", "c"]);
                    assertOneOf($nullableString, ["a", "c"]);

                    assertOneOf($maybeInt, [1, 2, 3]);
                    consumeAnyIntegerValue($maybeInt);

                    assertOneOf($maybeFloat, [1.5, 2.5, 3.5]);
                    consumeAnyFloatValue($maybeFloat);

                    /** @var "a"|"b"|"c" $abc */
                    $abc;

                    /** @param "a"|"b" $aOrB */
                    function consumeAOrB(string $aOrB): void {}
                    assertOneOf($abc, ["a", "b"]);
                    consumeAOrB($abc);
                ',
            ],
            'assertDocblockTypeContradictionCorrectType' => [
                'code' => '<?php
                    function takesAnInt(int $i): void {}

                    function takesAFloat(float $i): void {}

                    $foo = rand() / 2;

                    /** @psalm-suppress TypeDoesNotContainType */
                    if (is_int($foo) || !is_float($foo)) {
                        takesAnInt($foo);
                        exit;
                    }

                    takesAFloat($foo);',
            ],
            'assertOnPropertyValue' => [
                'code' => <<<'PHP'
                    <?php
                    class Foo {
                        public array $foo = [];
                    };


                    /** @psalm-assert array{a:1} $o->foo */
                    function change(Foo $o): void
                    {
                        $o->foo = ["a" => 1];
                    }
                    $o = new Foo;
                    change($o);
                    PHP,
                'assertions' => [
                    '$o->foo===' => 'array{a: 1}',
                ],
            ],
            'assertionOfBackedEnumValuesWithValueOf' => [
                'code' => '<?php
                    enum StringEnum: string
                    {
                        case FOO = "foo";
                        case BAR = "bar";
                        case BAZ = "baz";
                    }

                    enum IntEnum: int
                    {
                        case FOO = 1;
                        case BAR = 2;
                        case BAZ = 3;
                    }

                    /** @psalm-assert value-of<StringEnum::BAR|StringEnum::FOO> $foo */
                    function assertSomeString(string $foo): void
                    {}

                    /** @psalm-assert value-of<IntEnum::BAR|IntEnum::FOO> $foo */
                    function assertSomeInt(int $foo): void
                    {}

                    /** @psalm-assert value-of<StringEnum|IntEnum> $foo */
                    function assertAnyEnumValue(string|int $foo): void
                    {}

                    /** @param "foo"|"bar" $foo */
                    function takesSomeStringFromEnum(string $foo): StringEnum
                    {
                        return StringEnum::from($foo);
                    }

                    /** @param 1|2 $foo */
                    function takesSomeIntFromEnum(int $foo): IntEnum
                    {
                        return IntEnum::from($foo);
                    }

                    /** @var non-empty-string $string */
                    $string = null;
                    /** @var positive-int $int */
                    $int = null;

                    assertSomeString($string);
                    takesSomeStringFromEnum($string);

                    assertSomeInt($int);
                    takesSomeIntFromEnum($int);

                    /** @var string|int $potentialEnumValue */
                    $potentialEnumValue = null;
                    assertAnyEnumValue($potentialEnumValue);
                ',
                'assertions' => [
                    '$potentialEnumValue===' => "'bar'|'baz'|'foo'|1|2|3",
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'assertStringIsNonEmptyString' => [
                'code' => '<?php
                    /** @var string $str */;
                    /** @var string|int $stringOrInt */;

                    if (isNonEmptyString($str)) {
                        /** @psalm-check-type-exact $str = non-empty-string */;
                    } else {
                        /** @psalm-check-type-exact $str = string */;
                    }

                    if (isNonEmptyString($stringOrInt)) {
                        /** @psalm-check-type-exact $stringOrInt = non-empty-string */;
                    } else {
                        /** @psalm-check-type-exact $stringOrInt = string|int */;
                    }

                    /**
                     * @param mixed $_str
                     * @psalm-assert-if-true non-empty-string $_str
                     */
                    function isNonEmptyString($_str): bool
                    {
                        return true;
                    }
                    ',
            ],
            'assertStringIsNonEmptyStringInNamespace' => [
                'code' => '<?php
                    namespace X;
                    /** @var string $str */;
                    /** @var string|int $stringOrInt */;

                    if (isNonEmptyString($str)) {
                        /** @psalm-check-type-exact $str = non-empty-string */;
                    } else {
                        /** @psalm-check-type-exact $str = string */;
                    }

                    if (isNonEmptyString($stringOrInt)) {
                        /** @psalm-check-type-exact $stringOrInt = non-empty-string */;
                    } else {
                        /** @psalm-check-type-exact $stringOrInt = string|int */;
                    }

                    /**
                     * @param mixed $_str
                     * @psalm-assert-if-true non-empty-string $_str
                     */
                    function isNonEmptyString($_str): bool
                    {
                        return true;
                    }
                    ',
            ],
            'assertObjectWithClosedInheritance' => [
                'code' => '<?php
                    /**
                     * @psalm-inheritors FirstChoice|SecondChoice|ThirdChoice
                     */
                    interface Choice
                    {
                    }

                    final class FirstChoice implements Choice
                    {
                    }

                    final class SecondChoice implements Choice
                    {
                    }

                    final class ThirdChoice implements Choice
                    {
                    }

                    /**
                     * @psalm-assert-if-true FirstChoice $choice
                     */
                    function isFirstChoice(Choice $choice): bool
                    {
                        return $choice instanceof FirstChoice;
                    }

                    /**
                     * @psalm-assert-if-true SecondChoice $choice
                     */
                    function isSecondChoice(Choice $choice): bool
                    {
                        return $choice instanceof SecondChoice;
                    }

                    function testFirstChoice(Choice $choice): void
                    {
                        if (isFirstChoice($choice)) {
                            /** @psalm-check-type-exact $choice = FirstChoice */
                        } else {
                            /** @psalm-check-type-exact $choice = SecondChoice|ThirdChoice */
                        }
                    }

                    function testFirstAndSecondChoice(Choice $choice): void
                    {
                        if (isFirstChoice($choice)) {
                            /** @psalm-check-type-exact $choice = FirstChoice */
                        } elseif (isSecondChoice($choice)) {
                            /** @psalm-check-type-exact $choice = SecondChoice */
                        } else {
                            /** @psalm-check-type-exact $choice = ThirdChoice */
                        }
                    }',
            ],
            'assertObjectWithClosedInheritanceWithMatch' => [
                'code' => '<?php
                    /**
                     * @psalm-inheritors FirstChoice|SecondChoice|ThirdChoice
                     */
                    interface Choice
                    {
                    }

                    final class FirstChoice implements Choice {}
                    final class SecondChoice implements Choice {}
                    final class ThirdChoice implements Choice {}

                    /**
                     * @psalm-assert-if-true FirstChoice $choice
                     */
                    function isFirstChoice(Choice $choice): bool
                    {
                        return $choice instanceof FirstChoice;
                    }

                    /**
                     * @psalm-assert-if-true SecondChoice $choice
                     */
                    function isSecondChoice(Choice $choice): bool
                    {
                        return $choice instanceof SecondChoice;
                    }

                    function testFirstChoice(FirstChoice $_first): string
                    {
                        return "first";
                    }

                    function testSecondOrThirdChoice(SecondChoice|ThirdChoice $_first): string
                    {
                        return "second or third";
                    }

                    function getLabel(Choice $choice): string
                    {
                        return match (true) {
                            isFirstChoice($choice) => testFirstChoice($choice),
                            default => testSecondOrThirdChoice($choice),
                        };
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'assertTemplatedObjectWithClosedInheritance' => [
                'code' => '<?php
                    /**
                     * @template-covariant E
                     * @template-covariant A
                     * @psalm-inheritors Left<E> | Right<A>
                     */
                    interface Either {
                        /** @psalm-assert-if-true Left<E> $this */
                        public function isLeft(): bool;

                        /** @psalm-assert-if-true Right<A> $this */
                        public function isRight(): bool;
                    }

                    /**
                     * @template E
                     * @implements Either<E, never>
                     */
                    final class Left implements Either {
                        public function isLeft(): bool
                        {
                            return true;
                        }
                        public function isRight(): bool
                        {
                            return false;
                        }
                    }

                    /**
                     * @template A
                     * @implements Either<never, A>
                     */
                    final class Right implements Either {
                        public function isLeft(): bool
                        {
                            return false;
                        }
                        public function isRight(): bool
                        {
                            return true;
                        }
                    }

                    /**
                     * @template E
                     * @template A
                     * @param Either<E, A> $either
                     * @psalm-assert-if-true Left<E> $either
                     */
                    function isLeft(Either $either): bool
                    {
                        return $either instanceof Left;
                    }

                    /**
                     * @template E
                     * @template A
                     * @param Either<E, A> $either
                     * @psalm-assert-if-true Right<A> $either
                     */
                    function isRight(Either $either): bool
                    {
                        return $either instanceof Right;
                    }

                    /**
                     * @return Either<OutOfRangeException, int>
                     */
                    function getEither(): Either
                    {
                        throw new RuntimeException("???");
                    }

                    /**
                     * @param Left<OutOfRangeException> $_left
                     */
                    function testLeft(Left $_left): void {}

                    /**
                     * @param Right<int> $_right
                     */
                    function testRight(Right $_right): void {}

                    /** @param Either<OutOfRangeException, int> $either */
                    function isLeftFunctionIfElse(Either $either): void
                    {
                        if (isLeft($either)) {
                            /** @psalm-check-type-exact $either = Left<OutOfRangeException> */
                            testLeft($either);
                        } else {
                            /** @psalm-check-type-exact $either = Right<int> */
                            testRight($either);
                        }
                    }

                    /** @param Either<OutOfRangeException, int> $either */
                    function isRightFunctionIfElse(Either $either): void
                    {
                        if (isRight($either)) {
                            testRight($either);
                            /** @psalm-check-type-exact $either = Right<int> */
                        } else {
                            /** @psalm-check-type-exact $either = Left<OutOfRangeException> */
                            testLeft($either);
                        }
                    }

                    /** @param Either<OutOfRangeException, int> $either */
                    function testRightFunctionTernary(Either $either): void
                    {
                        isRight($either) ? testRight($either) : testLeft($either);
                    }

                    /** @param Either<OutOfRangeException, int> $either */
                    function testLeftFunctionTernary(Either $either): void
                    {
                        isLeft($either) ? testLeft($either) : testRight($either);
                    }

                    /** @param Either<OutOfRangeException, int> $either */
                    function isLeftMethodIfElse(Either $either): void
                    {
                        if ($either->isLeft()) {
                            /** @psalm-check-type-exact $either = Left<OutOfRangeException> */
                            testLeft($either);
                        } else {
                            /** @psalm-check-type-exact $either = Right<int> */
                            testRight($either);
                        }
                    }

                    /** @param Either<OutOfRangeException, int> $either */
                    function isRightMethodIfElse(Either $either): void
                    {
                        if ($either->isRight()) {
                            testRight($either);
                            /** @psalm-check-type-exact $either = Right<int> */
                        } else {
                            /** @psalm-check-type-exact $either = Left<OutOfRangeException> */
                            testLeft($either);
                        }
                    }

                    /** @param Either<OutOfRangeException, int> $either */
                    function testRightMethodTernary(Either $either): void
                    {
                        $either->isRight() ? testRight($either) : testLeft($either);
                    }

                    /** @param Either<OutOfRangeException, int> $either */
                    function testLeftMethodTernary(Either $either): void
                    {
                        $either->isLeft() ? testLeft($either) : testRight($either);
                    }',
            ],
            'assertArrayListIfTrueFalseCompareTrue' => [
                'code' => '<?php
                    /**
                     * @param array<string, string|int|float>|list<string> $arg
                     * @return bool
                     *
                     * @psalm-assert-if-false array<string, string|int|float> $arg
                     * @psalm-assert-if-true list<string> $arg
                     */
                    function is_array_or_list($arg) {
                        // should be array_is_list($arg), but tests run in non-PHP 8 environment
                        if (array_values($arg) === $arg) {
                            return true;
                        }
                        return false;
                    }
                    /**
                     * @param list<string> $arg
                     * @return void
                     */
                    function takesAList($arg) {}
                    /**
                     * @param array<string, string|int|float> $arg
                     * @return void
                     */
                    function takesAnArray($arg) {}
                    /**
                     * @var array<string, string|int|float>|list<string> $foo
                     */
                    $foo;
                    if (is_array_or_list($foo) === true) {
                        takesAList($foo);
                    } else {
                        takesAnArray($foo);
                    }',
            ],
            'assertArrayListIfTrueFalseCompareTruthy' => [
                'code' => '<?php
                    /**
                     * @param array<string, string|int|float>|list<string> $arg
                     * @return bool
                     *
                     * @psalm-assert-if-false array<string, string|int|float> $arg
                     * @psalm-assert-if-true list<string> $arg
                     */
                    function is_array_or_list($arg) {
                        // should be array_is_list($arg), but tests run in non-PHP 8 environment
                        if (array_values($arg) === $arg) {
                            return true;
                        }
                        return false;
                    }
                    /**
                     * @param list<string> $arg
                     * @return void
                     */
                    function takesAList($arg) {}
                    /**
                     * @param array<string, string|int|float> $arg
                     * @return void
                     */
                    function takesAnArray($arg) {}
                    /**
                     * @var array<string, string|int|float>|list<string> $foo
                     */
                    $foo;
                    if (is_array_or_list($foo)) {
                        takesAList($foo);
                    } else {
                        takesAnArray($foo);
                    }',
            ],
            'assertArrayListIfTrueFalseCompareNotTrue' => [
                'code' => '<?php
                    /**
                     * @param array<string, string|int|float>|list<string> $arg
                     * @return bool
                     *
                     * @psalm-assert-if-false array<string, string|int|float> $arg
                     * @psalm-assert-if-true list<string> $arg
                     */
                    function is_array_or_list($arg) {
                        if (array_values($arg) === $arg) {
                            return true;
                        }
                        return false;
                    }
                    /**
                     * @param list<string> $arg
                     * @return void
                     */
                    function takesAList($arg) {}
                    /**
                     * @param array<string, string|int|float> $arg
                     * @return void
                     */
                    function takesAnArray($arg) {}
                    /**
                     * @var array<string, string|int|float>|list<string> $foo
                     */
                    $foo;
                    if (is_array_or_list($foo) !== true) {
                        takesAnArray($foo);
                    } else {
                        takesAList($foo);
                    }',
            ],
            'assertArrayListIfTrueFalseCompareFalse' => [
                'code' => '<?php
                    /**
                     * @param array<string, string|int|float>|list<string> $arg
                     * @return bool
                     *
                     * @psalm-assert-if-false array<string, string|int|float> $arg
                     * @psalm-assert-if-true list<string> $arg
                     */
                    function is_array_or_list($arg) {
                        if (array_values($arg) === $arg) {
                            return true;
                        }
                        return false;
                    }
                    /**
                     * @param list<string> $arg
                     * @return void
                     */
                    function takesAList($arg) {}
                    /**
                     * @param array<string, string|int|float> $arg
                     * @return void
                     */
                    function takesAnArray($arg) {}
                    /**
                     * @var array<string, string|int|float>|list<string> $foo
                     */
                    $foo;
                    if (is_array_or_list($foo) === false) {
                        takesAnArray($foo);
                    } else {
                        takesAList($foo);
                    }',
            ],
            'assertArrayListIfTrueFalseCompareNotFalse' => [
                'code' => '<?php
                    /**
                     * @param array<string, string|int|float>|list<string> $arg
                     * @return bool
                     *
                     * @psalm-assert-if-false array<string, string|int|float> $arg
                     * @psalm-assert-if-true list<string> $arg
                     */
                    function is_array_or_list($arg) {
                        if (array_values($arg) === $arg) {
                            return true;
                        }
                        return false;
                    }
                    /**
                     * @param list<string> $arg
                     * @return void
                     */
                    function takesAList($arg) {}
                    /**
                     * @param array<string, string|int|float> $arg
                     * @return void
                     */
                    function takesAnArray($arg) {}
                    /**
                     * @var array<string, string|int|float>|list<string> $foo
                     */
                    $foo;
                    if (is_array_or_list($foo) !== false) {
                        takesAList($foo);
                    } else {
                        takesAnArray($foo);
                    }',
            ],
            'assertArrayListIfTrueFalseCompareFalsy' => [
                'code' => '<?php
                    /**
                     * @param array<string, string|int|float>|list<string> $arg
                     * @return bool
                     *
                     * @psalm-assert-if-false array<string, string|int|float> $arg
                     * @psalm-assert-if-true list<string> $arg
                     */
                    function is_array_or_list($arg) {
                        if (array_values($arg) === $arg) {
                            return true;
                        }
                        return false;
                    }
                    /**
                     * @param list<string> $arg
                     * @return void
                     */
                    function takesAList($arg) {}
                    /**
                     * @param array<string, string|int|float> $arg
                     * @return void
                     */
                    function takesAnArray($arg) {}
                    /**
                     * @var array<string, string|int|float>|list<string> $foo
                     */
                    $foo;
                    if (!is_array_or_list($foo)) {
                        takesAnArray($foo);
                    } else {
                        takesAList($foo);
                    }',
            ],
            'assertArrayArrayIfTrueFalseCompareFalsy' => [
                'code' => '<?php
                    /**
                     * @param array<string, string>|array<int, float> $arg
                     * @return bool
                     *
                     * @psalm-suppress InvalidReturnType
                     *
                     * @psalm-assert-if-false array<string, string> $arg
                     * @psalm-assert-if-true array<int, float> $arg
                     */
                    function is_array_a_or_b($arg) {}
                    /**
                     * @param array<string, string> $arg
                     * @return void
                     */
                    function takesAnArrayA($arg) {}
                    /**
                     * @param array<int, float> $arg
                     * @return void
                     */
                    function takesAnArrayB($arg) {}
                    /**
                     * @var array<string, string>|array<int, float> $foo
                     */
                    $foo;
                    if (!is_array_a_or_b($foo)) {
                        takesAnArrayA($foo);
                    } else {
                        takesAnArrayB($foo);
                    }',
            ],
            'assertListListIfTrueFalseCompareFalsy' => [
                'code' => '<?php
                    /**
                     * @param list<string>|list<int> $arg
                     * @return bool
                     *
                     * @psalm-suppress InvalidReturnType
                     *
                     * @psalm-assert-if-false list<string> $arg
                     * @psalm-assert-if-true list<int> $arg
                     */
                    function is_list_string_or_int($arg) {}
                    /**
                     * @param list<string> $arg
                     * @return void
                     */
                    function takesAListString($arg) {}
                    /**
                     * @param list<int> $arg
                     * @return void
                     */
                    function takesAListInt($arg) {}
                    /**
                     * @var list<string>|list<int> $foo
                     */
                    $foo;
                    if (!is_list_string_or_int($foo)) {
                        takesAListString($foo);
                    } else {
                        takesAListInt($foo);
                    }',
            ],
            'assertKeyedArrayKeyedArrayIfTrueFalseCompareFalsy' => [
                'code' => '<?php
                    /**
                     * @param array{hello: string}|array{world: string} $arg
                     * @return bool
                     *
                     * @psalm-suppress InvalidReturnType
                     *
                     * @psalm-assert-if-false array{hello: string} $arg
                     * @psalm-assert-if-true array{world: string} $arg
                     */
                    function is_array_a_or_b($arg) {}
                    /**
                     * @param array{hello: string} $arg
                     * @return void
                     */
                    function takesAnArrayA($arg) {}
                    /**
                     * @param array{world: string} $arg
                     * @return void
                     */
                    function takesAnArrayB($arg) {}
                    /**
                     * @var array{hello: string}|array{world: string} $foo
                     */
                    $foo;
                    if (!is_array_a_or_b($foo)) {
                        takesAnArrayA($foo);
                    } else {
                        takesAnArrayB($foo);
                    }',
            ],
            'assertTemplateKeyedArrayTemplateKeyedArrayIfTrueFalseCompareFalsy' => [
                'code' => '<?php
                    /**
                     * @template Ta of array{hello: string}
                     * @template Tb of array{world: string}
                     * @param Ta|Tb $arg
                     * @return bool
                     *
                     * @psalm-suppress InvalidReturnType
                     *
                     * @psalm-assert-if-false Ta $arg
                     * @psalm-assert-if-true Tb $arg
                     */
                    function is_array_a_or_b($arg) {}
                    /**
                     * @param array{hello: string} $arg
                     * @return void
                     */
                    function takesAnArrayA($arg) {}
                    /**
                     * @param array{world: string} $arg
                     * @return void
                     */
                    function takesAnArrayB($arg) {}
                    /**
                     * @var array{hello: string}|array{world: string} $foo
                     */
                    $foo;
                    if (!is_array_a_or_b($foo)) {
                        takesAnArrayA($foo);
                    } else {
                        takesAnArrayB($foo);
                    }',
            ],
            'iterableToNonEmptyList' => [
                'code' => '<?php
                final class WhateverAssert
                {
                    /**
                     * @param mixed $value
                     * @psalm-assert non-empty-list $value
                     */
                    public static function doAssert($value): void
                    {}
                }

                /** @var iterable<mixed,string> $iterable */
                $iterable = [];

                WhateverAssert::doAssert($iterable);',
                'assertions' => [
                    '$iterable===' => 'non-empty-list<string>',
                ],
            ],
            'assertFromInheritedDocBlock' => [
                'code' => '<?php
                    namespace Namespace1 {

                    /** @template InstanceType */
                    interface PluginManagerInterface
                    {
                        /** @psalm-assert InstanceType $value */
                        public function validate(mixed $value): void;
                    }

                    /**
                     * @template InstanceType
                     * @template-implements PluginManagerInterface<InstanceType>
                     */
                    abstract class AbstractPluginManager implements PluginManagerInterface
                    {
                    }

                    /**
                     * @template InstanceType of object
                     * @template-extends AbstractPluginManager<InstanceType>
                     */
                    abstract class AbstractSingleInstancePluginManager extends AbstractPluginManager
                    {
                        public function validate(mixed $value): void
                        {
                        }
                    }
                }

                namespace Namespace2 {
                    use InvalidArgumentException;use Namespace1\AbstractSingleInstancePluginManager;
                    use Namespace1\AbstractPluginManager;
                    use stdClass;

                    /** @template-extends AbstractSingleInstancePluginManager<stdClass> */
                    final class Qoo extends AbstractSingleInstancePluginManager
                    {
                    }

                    /** @template-extends AbstractPluginManager<callable> */
                    final class Ooq extends AbstractPluginManager
                    {
                        public function validate(mixed $value): void
                        {
                        }
                    }
                }

                namespace {
                    $baz = new \Namespace2\Qoo();

                    /** @var mixed $object */
                    $object = null;
                    $baz->validate($object);

                    $ooq = new \Namespace2\Ooq();
                    /** @var mixed $callable */
                    $callable = null;
                    $ooq->validate($callable);
                }
                ',
                'assertions' => [
                    '$object===' => 'stdClass',
                    '$callable===' => 'callable',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'objectShapeAssertion' => [
                'code' => '<?php
                    /** @psalm-assert object{foo:string,bar:int} $value */
                    function assertObjectShape(mixed $value): void
                    {}

                    /** @var mixed $value */
                    $value = null;
                    assertObjectShape($value);
                ',
                'assertions' => [
                    '$value===' => 'object{foo:string, bar:int}',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'assertInstanceOfMultipleInterfaces' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    interface I {
                        /**
                         * @psalm-assert null|!ExpectedType $value
                         */
                        public static function foo($value);
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'assertNotEmptyOnBool' => [
                'code' => '<?php
                    /**
                     * @param mixed $value
                     * @psalm-assert !empty $value
                     */
                    function assertNotEmpty($value) : void {}

                    function foo(bool $bar) : void {
                        assertNotEmpty($bar);
                        if ($bar) {}
                    }',
                'error_message' => 'RedundantConditionGivenDocblockType',
            ],
            'withoutHasTypeCall' => [
                'code' => '<?php
                    $method = new ReflectionMethod(stdClass::class);
                    $parameters = $method->getParameters();
                    foreach ($parameters as $parameter) {
                        $parameter->getType()->__toString();
                    }',
                'error_message' => 'PossiblyNullReference',
            ],
            'forgetAssertionAfterRelevantNonMutationFreeCall' => [
                'code' => '<?php
                    class Foo
                    {
                        public ?string $bar = null;

                        public function nonMutationFree(): void
                        {
                            $this->bar = null;
                        }
                    }

                    /**
                     * @psalm-assert-if-true !null $foo->bar
                     */
                    function assertBarNotNull(Foo $foo): bool
                    {
                        return $foo->bar !== null;
                    }

                    $foo = new Foo();

                    if (assertBarNotNull($foo)) {
                        $foo->nonMutationFree();
                        requiresString($foo->bar);
                    }

                    function requiresString(string $_str): void {}
                ',
                'error_message' => 'PossiblyNullArgument',
            ],
            'forgetAssertionAfterRelevantNonMutationFreeCallOnReference' => [
                'code' => '<?php
                    class Foo
                    {
                        public ?string $bar = null;

                        public function nonMutationFree(): void
                        {
                            $this->bar = null;
                        }
                    }

                    /**
                     * @psalm-assert-if-true !null $foo->bar
                     */
                    function assertBarNotNull(Foo $foo): bool
                    {
                        return $foo->bar !== null;
                    }

                    $foo = new Foo();
                    $fooRef = &$foo;

                    if (assertBarNotNull($foo)) {
                        $fooRef->nonMutationFree();
                        requiresString($foo->bar);
                    }

                    function requiresString(string $_str): void {}
                ',
                'error_message' => 'PossiblyNullArgument',
            ],
            'forgetAssertionAfterReferenceModification' => [
                'code' => '<?php
                    class Foo
                    {
                        public ?string $bar = null;
                    }

                    /**
                     * @psalm-assert-if-true !null $foo->bar
                     */
                    function assertBarNotNull(Foo $foo): bool
                    {
                        return $foo->bar !== null;
                    }

                    $foo = new Foo();
                    $barRef = &$foo->bar;

                    if (assertBarNotNull($foo)) {
                        $barRef = null;
                        requiresString($foo->bar);
                    }

                    function requiresString(string $_str): void {}
                ',
                'error_message' => 'NullArgument',
                'ignored_issues' => ['UnsupportedPropertyReferenceUsage'],
            ],
            'assertionOnMagicPropertyWithoutMutationFreeGet' => [
                'code' => '<?php
                    /**
                     * @property ?string $b
                     */
                    class A {
                        public function __get(string $key) {return "";}
                        public function __set(string $key, string $value): void {}
                    }

                    $a = new A;

                    /** @psalm-assert-if-true  string $arg->b */
                    function assertString(A $arg): bool {return $arg->b !== null;}

                    if (assertString($a)) {
                        requiresString($a->b);
                    }

                    function requiresString(string $_str): void {}
                ',
                'error_message' => 'A::__get is not mutation-free',
            ],
            'randomValueFromMagicGetterIsNotMutationFree' => [
                'code' => '<?php
                    /**
                     * @property int<1, 10> $b
                     */
                    class A {
                        /** @psalm-mutation-free */
                        public function __get(string $key)
                        {
                            if ($key === "b") {
                                return random_int(1, 10);
                            }

                            return null;
                        }

                        public function __set(string $key, string $value): void
                        {
                            throw new \Exception("Setting not supported!");
                        }
                    }

                    $a = new A;

                    /** @psalm-assert-if-true =1 $arg->b */
                    function assertBIsOne(A $arg): bool
                    {
                        return $arg->b === 1;
                    }

                    if (assertBIsOne($a)) {
                        takesOne($a->b);
                    }

                    /** @param 1 $_arg */
                    function takesOne(int $_arg): void {}
                ',
                'error_message' => 'ImpureFunctionCall - src' . DIRECTORY_SEPARATOR . 'somefile.php:10:40',
            ],
        ];
    }
}
