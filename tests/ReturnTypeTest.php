<?php
namespace Psalm\Tests;

class ReturnTypeTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'returnTypeAfterUselessNullCheck' => [
                '<?php
                    class One {}

                    class B {
                        /**
                         * @return One|null
                         */
                        public function barBar() {
                            $baz = rand(0,100) > 50 ? new One(): null;

                            // should have no effect
                            if ($baz === null) {
                                $baz = null;
                            }

                            return $baz;
                        }
                    }',
            ],
            'returnTypeNotEmptyCheck' => [
                '<?php
                    class B {
                        /**
                         * @param string|null $str
                         * @return string
                         */
                        public function barBar($str) {
                            if (empty($str)) {
                                $str = "";
                            }
                            return $str;
                        }
                    }',
            ],
            'returnTypeNotEmptyCheckInElseIf' => [
                '<?php
                    class B {
                        /**
                         * @param string|null $str
                         * @return string
                         */
                        public function barBar($str) {
                            if ($str === "badger") {
                                // do nothing
                            }
                            elseif (empty($str)) {
                                $str = "";
                            }
                            return $str;
                        }
                    }',
            ],
            'returnTypeNotEmptyCheckInElse' => [
                '<?php
                    class B {
                        /**
                         * @param string|null $str
                         * @return string
                         */
                        public function barBar($str) {
                            if (!empty($str)) {
                                // do nothing
                            }
                            else {
                                $str = "";
                            }
                            return $str;
                        }
                    }',
            ],
            'returnTypeAfterIf' => [
                '<?php
                    class B {
                        /**
                         * @return string|null
                         */
                        public function barBar() {
                            $str = null;
                            $bar1 = rand(0, 100) > 40;
                            if ($bar1) {
                                $str = "";
                            }
                            return $str;
                        }
                    }',
            ],
            'returnTypeAfterTwoIfsWithThrow' => [
                '<?php
                    class A1 {
                    }
                    class A2 {
                    }
                    class B {
                        /**
                         * @return A1
                         */
                        public function barBar(A1 $a1 = null, A2 $a2 = null) {
                            if (!$a1) {
                                throw new \Exception();
                            }
                            if (!$a2) {
                                throw new \Exception();
                            }
                            return $a1;
                        }
                    }',
            ],
            'returnTypeAfterIfElseIfWithThrow' => [
                '<?php
                    class A1 {
                    }
                    class A2 {
                    }
                    class B {
                        /**
                         * @return A1
                         */
                        public function barBar(A1 $a1 = null, A2 $a2 = null) {
                            if (!$a1) {
                                throw new \Exception();
                            }
                            elseif (!$a2) {
                                throw new \Exception();
                            }
                            return $a1;
                        }
                    }',
            ],
            'tryCatchReturnType' => [
                '<?php
                    class A {
                        /** @return bool */
                        public function fooFoo() {
                            try {
                                // do a thing
                                return true;
                            }
                            catch (\Exception $e) {
                                throw $e;
                            }
                        }
                    }',
            ],
            'switchReturnTypeWithFallthrough' => [
                '<?php
                    class A {
                        /** @return bool */
                        public function fooFoo() {
                            switch (rand(0,10)) {
                                case 1:
                                default:
                                    return true;
                            }
                        }
                    }',
            ],
            'switchReturnTypeWithFallthroughAndStatement' => [
                '<?php
                    class A {
                        /** @return bool */
                        public function fooFoo() {
                            switch (rand(0,10)) {
                                case 1:
                                    $a = 5;
                                default:
                                    return true;
                            }
                        }
                    }',
            ],
            'switchReturnTypeWithDefaultException' => [
                '<?php
                    class A {
                        /**
                         * @psalm-suppress TooManyArguments
                         * @return bool
                         */
                        public function fooFoo() {
                            switch (rand(0,10)) {
                                case 1:
                                case 2:
                                    return true;

                                default:
                                    throw new \Exception("badness");
                            }
                        }
                    }',
            ],
            'extendsStaticCallReturnType' => [
                '<?php
                    abstract class A {
                        /** @return static */
                        public static function load() {
                            return new static();
                        }
                    }

                    class B extends A {
                    }

                    $b = B::load();',
                'assertions' => [
                    '$b' => 'B',
                ],
            ],
            'extendsStaticCallArrayReturnType' => [
                '<?php
                    abstract class A {
                        /** @return array<int,static> */
                        public static function loadMultiple() {
                            return [new static()];
                        }
                    }

                    class B extends A {
                    }

                    $bees = B::loadMultiple();',
                'assertions' => [
                    '$bees' => 'array<int, B>',
                ],
            ],
            'issetReturnType' => [
                '<?php
                    /**
                     * @param  mixed $foo
                     * @return bool
                     */
                    function a($foo = null) {
                        return isset($foo);
                    }',
            ],
            'thisReturnType' => [
                '<?php
                    class A {
                        /** @return $this */
                        public function getThis() {
                            return $this;
                        }
                    }',
            ],
            'overrideReturnType' => [
                '<?php
                    class A {
                        /** @return string|null */
                        public function blah() {
                            return rand(0, 10) === 4 ? "blah" : null;
                        }
                    }

                    class B extends A {
                        /** @return string */
                        public function blah() {
                            return "blah";
                        }
                    }

                    $blah = (new B())->blah();',
                'assertions' => [
                    '$blah' => 'string',
                ],
            ],
            'interfaceReturnType' => [
                '<?php
                    interface A {
                        /** @return string|null */
                        public function blah();
                    }

                    class B implements A {
                        /** @return string|null */
                        public function blah() {
                            return rand(0, 10) === 4 ? "blah" : null;
                        }
                    }

                    $blah = (new B())->blah();',
                'assertions' => [
                    '$blah' => 'string|null',
                ],
            ],
            'overrideReturnTypeInGrandparent' => [
                '<?php
                    abstract class A {
                        /** @return string|null */
                        abstract public function blah();
                    }

                    abstract class B extends A {
                    }

                    class C extends B {
                        /** @return string|null */
                        public function blah() {
                            return rand(0, 10) === 4 ? "blahblah" : null;
                        }
                    }

                    $blah = (new C())->blah();',
                'assertions' => [
                    '$blah' => 'string|null',
                ],
            ],
            'backwardsReturnType' => [
                '<?php
                    class A {}
                    class B extends A {}

                    /** @return B|A */
                    function foo() {
                      return rand(0, 1) ? new A : new B;
                    }',
            ],
            'issetOnPropertyReturnType' => [
                '<?php
                    class Foo {
                        /** @var Foo|null */
                        protected $bar;

                        /**
                         * @return ?Foo
                         */
                        function getBarWithIsset() {
                            if (isset($this->bar)) return $this->bar;
                            return null;
                        }
                    }',
            ],
            'resourceReturnType' => [
                '<?php
                    /** @return resource */
                    function getOutput() {
                        $res = fopen("php://output", "w");

                        if ($res === false) {
                            throw new \Exception("Cannot write");
                        }

                        return $res;
                    }',
            ],
            'resourceReturnTypeWithOrDie' => [
                '<?php
                    /** @return resource */
                    function getOutput() {
                        $res = fopen("php://output", "w") or die();

                        return $res;
                    }',
            ],
            'resourceParamType' => [
                '<?php
                    /** @param resource $res */
                    function doSomething($res): void {
                    }',
            ],
            'returnArrayOfNullable' => [
                '<?php
                    /**
                     * @return array<?stdClass>
                     */
                    function getBarWithIsset() {
                        if (rand() % 2 > 0) return [new stdClass()];
                        return [null];
                    }',
            ],
            'selfReturnNoTypehints' => [
                '<?php
                    class A {
                        /**
                         * @return static
                         */
                        public function getMe()
                        {
                            return $this;
                        }
                    }

                    class B extends A
                    {
                        /**
                         * @return static
                         */
                        public function getMeAgain() {
                            return $this->getMe();
                        }
                    }',
            ],
            'selfReturnTypehints' => [
                '<?php
                    class A {
                        /**
                         * @return static
                         */
                        public function getMe(): self
                        {
                            return $this;
                        }
                    }

                    class B extends A
                    {
                        /**
                         * @return static
                         */
                        public function getMeAgain(): self {
                            return $this->getMe();
                        }
                    }',
            ],
            'returnTrueFromBool' => [
                '<?php
                    /** @return bool */
                    function foo(): bool {
                        return true;
                    }',
            ],
            'iteratorReturnTypeFromGenerator' => [
                '<?php
                    function foo1(): Generator {
                        foreach ([1, 2, 3] as $i) {
                            yield $i;
                        }
                    }

                    function foo2(): Iterator {
                        foreach ([1, 2, 3] as $i) {
                            yield $i;
                        }
                    }

                    function foo3(): Traversable {
                        foreach ([1, 2, 3] as $i) {
                            yield $i;
                        }
                    }

                    function foo4(): iterable {
                        foreach ([1, 2, 3] as $i) {
                            yield $i;
                        }
                    }

                    foreach (foo1() as $i) echo $i;
                    foreach (foo2() as $i) echo $i;
                    foreach (foo3() as $i) echo $i;
                    foreach (foo4() as $i) echo $i;',
                'assertions' => [],
                'error_levels' => ['MixedAssignment', 'MixedArgument'],
            ],
            'objectLikeArrayOptionalKeyReturn' => [
                '<?php
                    /** @return array{a: int, b?: int} */
                    function foo() : array {
                        return rand(0, 1) ? ["a" => 1, "b" => 2] : ["a" => 2];
                    }',
            ],
            'objectLikeArrayOptionalKeyReturnSeparateStatements' => [
                '<?php
                    /** @return array{a: int, b?: int} */
                    function foo() : array {
                        if (rand(0, 1)) {
                            return ["a" => 1, "b" => 2];
                        }

                        return ["a" => 2];
                    }',
            ],
            'badlyCasedReturnType' => [
                '<?php
                    namespace MyNS;

                    class Example {
                        /** @return array<int,example> */
                        public static function test() : array {
                            return [new Example()];
                        }

                        /** @return example */
                        public static function instance() {
                            return new Example();
                        }
                    }',
                'assertions' => [],
                'error_levels' => ['InvalidClass'],
            ],
            'arrayReturnTypeWithExplicitKeyType' => [
                '<?php
                    /** @return array<int|string, mixed> */
                    function returnsArray(array $arr) : array {
                        return $arr;
                    }',
            ],
            'namespacedScalarParamAndReturn' => [
                '<?php
                    namespace Foo;

                    /**
                    * @param scalar $scalar
                    *
                    * @return scalar
                    */
                    function ($scalar) {
                      switch(random_int(0, 3)) {
                        case 0:
                          return true;
                        case 1:
                          return "string";
                        case 2:
                          return 2;
                        case 3:
                          return 3.0;
                      }

                      return 0;
                    }'
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'wrongReturnType1' => [
                '<?php
                    function fooFoo(): string {
                        return 5;
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'wrongReturnType2' => [
                '<?php
                    function fooFoo(): string {
                        return rand(0, 5) ? "hello" : null;
                    }',
                'error_message' => 'NullableReturnStatement',
            ],
            'wrongReturnTypeInNamespace1' => [
                '<?php
                    namespace bar;

                    function fooFoo(): string {
                        return 5;
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'wrongReturnTypeInNamespace2' => [
                '<?php
                    namespace bar;

                    function fooFoo(): string {
                        return rand(0, 5) ? "hello" : null;
                    }',
                'error_message' => 'NullableReturnStatement',
            ],
            'missingReturnType' => [
                '<?php
                    function fooFoo() {
                        return rand(0, 5) ? "hello" : null;
                    }',
                'error_message' => 'MissingReturnType',
            ],
            'mixedInferredReturnType' => [
                '<?php
                    function fooFoo(array $arr): string {
                        /** @psalm-suppress MixedReturnStatement */
                        return array_pop($arr);
                    }',
                'error_message' => 'MixedInferredReturnType',
            ],
            'mixedInferredReturnStatement' => [
                '<?php
                    function fooFoo(array $arr): string {
                        return array_pop($arr);
                    }',
                'error_message' => 'MixedReturnStatement',
            ],
            'invalidReturnTypeClass' => [
                '<?php
                    function fooFoo(): A {
                        return new A;
                    }',
                'error_message' => 'UndefinedClass',
                'error_levels' => ['MixedInferredReturnType'],
            ],
            'invalidClassOnCall' => [
                '<?php
                    /**
                     * @psalm-suppress UndefinedClass
                     */
                    function fooFoo(): A {
                        return $_GET["a"];
                    }

                    fooFoo()->bar();',
                'error_message' => 'UndefinedClass',
                'error_levels' => ['MixedInferredReturnType', 'MixedReturnStatement'],
            ],
            'returnArrayOfNullableInvalid' => [
                '<?php
                    /**
                     * @return array<?stdClass>
                     */
                    function getBarWithIsset() {
                        if (rand() % 2 > 0) return [new stdClass()];
                        if (rand() % 2 > 0) return [null];
                        return [2];
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'resourceReturnType' => [
                '<?php
                    function getOutput(): resource {
                        $res = fopen("php://output", "w");

                        if ($res === false) {
                            throw new \Exception("Cannot write");
                        }

                        return $res;
                    }',
                'error_message' => 'ReservedWord',
            ],
            'resourceParamType' => [
                '<?php
                    function doSomething(resource $res): void {
                    }',
                'error_message' => 'ReservedWord',
            ],
            'voidParamType' => [
                '<?php
                    function f(void $p): void {}',
                'error_message' => 'ReservedWord',
            ],
            'voidClass' => [
                '<?php
                    class void {}',
                'error_message' => 'ReservedWord',
            ],
            'disallowReturningExplicitVoid' => [
                '<?php
                    function returnsVoid(): void {}

                    function alsoReturnsVoid(): void {
                      return returnsVoid();
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'complainAboutObjectLikeWhenArrayIsFound' => [
                '<?php
                    /** @return array{a:string,b:string,c:string} */
                    function foo(): array {
                      $arr = [];
                      foreach (["a", "b"] as $key) {
                        $arr[$key] = "foo";
                      }
                      return $arr;
                    }',
                'error_message' => 'LessSpecificReturnStatement',
            ],
            'invalidVoidStatementWhenMixedInferred' => [
                '<?php
                    /**
                     * @return mixed
                     */
                    function a()
                    {
                        return 1;
                    }

                    function b(): void
                    {
                        return a();
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'moreSpecificReturnType' => [
                '<?php
                    class A {}
                    class B extends A {}
                    interface I {
                        /** @return B[] */
                        public function foo();
                    }
                    class D implements I {
                        /** @return A[] */
                        public function foo() {
                            return [new A, new A];
                        }
                    }',
                'error_message' => 'MoreSpecificImplementedReturnType',
            ],
            'returnTypehintRequiresExplicitReturn' => [
                '<?php
                    function foo(): ?string {
                      if (rand(0, 1)) return "hello";
                    }',
                'error_message' => 'InvalidReturnType',
            ],
            'returnTypehintWithVoidReturnType' => [
                '<?php
                    function foo(): ?string {
                      if (rand(0, 1)) {
                        return;
                      }

                      return "hello";
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'invalidReturnStatementMoreAccurateThanFalsable' => [
                '<?php
                    class A1{}
                    class B1{}

                    function testFalseable() : A1 {
                        return (rand() % 2 === 0) ? (new B1()) : false;
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'invalidReturnTypeMoreAccurateThanFalsable' => [
                '<?php
                    class A1{}
                    class B1{}

                    function testFalseable() : A1 {
                        /**
                         * @psalm-suppress InvalidReturnStatement
                         * @psalm-suppress FalsableReturnStatement
                         */
                        return (rand() % 2 === 0) ? (new B1()) : false;
                    }',
                'error_message' => 'InvalidReturnType',
            ],
            'moreSpecificDocblockReturnType' => [
                '<?php
                    /** @return int[] */
                    function foo(array $arr) : array {
                      return $arr;
                    }',
                'error_message' => 'LessSpecificReturnStatement',
            ],
            'moreSpecificGenericReturnType' => [
                '<?php
                    /** @return Iterator<int, string> */
                    function foo(array $a) {
                        $obj = new ArrayObject($a);
                        return $obj->getIterator();
                    }',
                'error_message' => 'LessSpecificReturnStatement',
            ],
            'invalidGenericReturnType' => [
                '<?php
                    /** @return ArrayIterator<int, string> */
                    function foo(array $a) {
                        $obj = new ArrayObject([1, 2, 3, 4]);
                        return $obj->getIterator();
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'objectLikeArrayOptionalKeyWithNonOptionalReturn' => [
                '<?php
                    /** @return array{a: int, b: int} */
                    function foo() : array {
                        return rand(0, 1) ? ["a" => 1, "b" => 2] : ["a" => 2];
                    }',
                'error_message' => 'LessSpecificReturnStatement',
            ],
        ];
    }
}
