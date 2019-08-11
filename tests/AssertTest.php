<?php
namespace Psalm\Tests;

class AssertTest extends TestCase
{
    use Traits\ValidCodeAnalysisTestTrait;
    use Traits\InvalidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
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
                    }',
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
            'assertArrayReturnTypeNarrowed' => [
                '<?php
                    /** @return array{0:Exception} */
                    function f(array $a): array {
                        if ($a[0] instanceof Exception) {
                            return $a;
                        }

                        return [new Exception("bad")];
                    }',
            ],
            'assertTypeNarrowedByAssert' => [
                '<?php
                    /** @return array{0:Exception,1:Exception} */
                    function f(array $ret): array {
                        assert($ret[0] instanceof Exception);
                        assert($ret[1] instanceof Exception);
                        return $ret;
                    }',
            ],
            'assertTypeNarrowedByButOtherFetchesAreMixed' => [
                '<?php
                    /**
                     * @return array{0:Exception}
                     * @psalm-suppress MixedArgument
                     */
                    function f(array $ret): array {
                        assert($ret[0] instanceof Exception);
                        echo strlen($ret[1]);
                        return $ret;
                    }',
            ],
            'assertTypeNarrowedByNestedIsset' => [
                '<?php
                    /**
                     * @psalm-suppress MixedMethodCall
                     * @psalm-suppress MixedArgument
                     */
                    function foo(array $array = []): void {
                        if (array_key_exists("a", $array)) {
                            echo $array["a"];
                        }

                        if (array_key_exists("b", $array)) {
                            echo $array["b"]->format("Y-m-d");
                        }
                    }',
            ],
            'assertCheckOnNonZeroArrayOffset' => [
                '<?php
                    /**
                     * @param array{string,array|null} $a
                     * @return string
                     */
                    function f(array $a) {
                        assert(is_array($a[1]));
                        return $a[0];
                    }',
            ],
            'assertOnParseUrlOutput' => [
                '<?php
                    /**
                     * @param array<"a"|"b"|"c", mixed> $arr
                     */
                    function uriToPath(array $arr) : string {
                        if (!isset($arr["a"]) || $arr["b"] !== "foo") {
                            throw new \InvalidArgumentException("bad");
                        }

                        return (string) $arr["c"];
                    }',
            ],
            'combineAfterLoopAssert' => [
                '<?php
                    function foo(array $array) : void {
                        $c = 0;

                        if ($array["a"] === "a") {
                            foreach ([rand(0, 1), rand(0, 1)] as $i) {
                                if ($array["b"] === "c") {}
                                $c++;
                            }
                        }
                    }',
            ],
            'assertOnXml' => [
                '<?php
                    function f(array $array) : void {
                        if ($array["foo"] === "ok") {
                            if ($array["bar"] === "a") {}
                            if ($array["bar"] === "b") {}
                        }
                    }',
            ],
            'assertOnBacktrace' => [
                '<?php
                    function _validProperty(array $c, array $arr) : void {
                        if (empty($arr["a"])) {}

                        if ($c && $c["a"] !== "b") {}
                    }',
            ],
            'assertOnRemainderOfArray' => [
                '<?php
                    /**
                     * @psalm-suppress MixedInferredReturnType
                     * @psalm-suppress MixedReturnStatement
                     */
                    function foo(string $file_name) : int {
                        while ($data = getData()) {
                            if (is_numeric($data[0])) {
                                for ($i = 1; $i < count($data); $i++) {
                                    return $data[$i];
                                }
                            }
                        }

                        return 5;
                    }

                    function getData() : ?array {
                        return rand(0, 1) ? ["a", "b", "c"] : null;
                    }',
            ],
            'notEmptyCheck' => [
                '<?php
                    /**
                     * @psalm-suppress MixedAssignment
                     */
                    function load(string $objectName, array $config = []) : void {
                        if (isset($config["className"])) {
                            $name = $objectName;
                            $objectName = $config["className"];
                        }
                        if (!empty($config)) {}
                    }',
            ],
            'unsetAfterIssetCheck' => [
                '<?php
                    function checkbox(array $options = []) : void {
                        if ($options["a"]) {}

                        unset($options["a"], $options["b"]);
                    }',
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
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
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
                'error_message' => 'InvalidDocblock',
            ],
        ];
    }
}
