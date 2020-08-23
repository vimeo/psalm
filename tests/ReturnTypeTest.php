<?php
namespace Psalm\Tests;

use const DIRECTORY_SEPARATOR;

class ReturnTypeTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
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
                    /**
                     * @psalm-consistent-constructor
                     */
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
                    /**
                     * @psalm-consistent-constructor
                     */
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
                    '$blah' => 'null|string',
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
                    '$blah' => 'null|string',
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
                    function foo($scalar) {
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
                    }',
            ],
            'stopAfterFirstReturn' => [
                '<?php
                    function foo() : bool {
                        return true;

                        return false;
                    }',
            ],
            'neverReturnsSimple' => [
                '<?php
                    namespace Foo;
                    /**
                     * @return never-returns
                     */
                    function foo() : void {
                        exit();
                    }',
            ],
            'neverReturnsCovariance' => [
                '<?php
                    namespace Foo;
                    class A {
                        /**
                         * @return string
                         */
                        public function foo() {
                            return "hello";
                        }
                    }

                    class B extends A {
                        /**
                         * @return never-returns
                         */
                        public function foo() {
                            exit();
                        }
                    }',
            ],
            'noReturnCallReturns' => [
                '<?php
                    namespace Foo;
                    /**
                     * @return never-returns
                     */
                    function foo() : void {
                        exit();
                    }

                    /**
                     * @return never-returns
                     */
                    function bar() : void {
                        foo();
                    }',
            ],
            'suppressInvalidReturnType' => [
                '<?php
                    /**
                     * @psalm-suppress InvalidReturnType
                     */
                    function calculate(string $foo): int {
                        switch ($foo) {
                            case "a":
                                return 0;
                        }
                    }',
            ],
            'allowScalarReturningFalseAndTrue' => [
                '<?php
                    /** @return scalar */
                    function f() {
                        return false;
                    }
                    /** @return scalar */
                    function g() {
                        return true;
                    }',
            ],
            'allowThrowAndExitToOverrideReturnType' => [
                '<?php
                    interface Foo {
                        public function doFoo(): int;
                    }

                    class Bar implements Foo {
                        public function doFoo(): int {
                          print "Error\n";
                          exit(1);
                        }
                    }

                    class Baz implements Foo {
                        public function doFoo(): int {
                            throw new \Exception("bad");
                        }
                    }',
            ],
            'allowResourceInNamespace' => [
                '<?php

                    namespace Bar;

                    class Resource {
                        function get(string $key): ?string {
                            return "";
                        }
                    }

                    class Foo {
                        /** @var string[] */
                        private $references = [];

                        /** @var Resource */
                        private $resource;

                        public function __construct() {
                            $this->resource = new Resource();
                        }

                        public function foo(): array {
                            $types = [];

                            foreach ($this->references as $ref => $data) {
                                $types[$ref] = $this->resource->get($data);
                            }

                            return $types;
                        }
                    }',
            ],
            'allowIterableReturnTypeCrossover' => [
                '<?php
                    class Foo {
                        public const TYPE1 = "a";
                        public const TYPE2 = "b";

                        public const AVAILABLE_TYPES = [
                            self::TYPE1,
                            self::TYPE2,
                        ];

                        /**
                         * @return iterable<array-key, array{foo: value-of<self::AVAILABLE_TYPES>}>
                         */
                        public function foo() {
                            return [
                                ["foo" => self::TYPE1],
                                ["foo" => self::TYPE2]
                            ];
                        }
                    }',
            ],
            'suppressNeverReturnTypeInClass' => [
                '<?php
                    function may_exit() : void {
                        exit(0);
                    }

                    class InClass {
                        /**
                         * @psalm-suppress InvalidReturnType
                         * @psalm-return never-returns
                         */
                        function test() {
                            may_exit();
                        }
                    }',
            ],
            'infersClosureReturnTypes' => [
                '<?php
                    /**
                     * @template T
                     * @template U
                     * @param callable(T): U $predicate
                     * @return Closure(iterable<T>): iterable<U>
                     */
                    function map(callable $predicate): callable {
                        return function($iter) use ($predicate) {
                            foreach ($iter as $key => $value) {
                                yield $key => $predicate($value);
                            }
                        };
                    }

                    $res = map(function(int $i): string { return (string) $i; })([1,2,3]);
                ',
                'assertions' => [
                    '$res' => 'iterable<mixed, numeric-string>',
                ],
            ],
            'infersArrowClosureReturnTypes' => [
                '<?php
                    /**
                     * @param Closure(int, int): bool $op
                     * @return Closure(int): bool
                     */
                    function reflexive(Closure $op): Closure {
                        return fn ($x) => $op($x, $x);
                    }

                    $res = reflexive(fn(int $a, int $b): bool => $a === $b);
                ',
                'assertions' => [
                    '$res' => 'Closure(int):bool',
                ],
                'error_levels' => [],
                '7.4'
            ],
            'infersClosureReturnTypesWithPartialTypehinting' => [
                '<?php
                    /**
                     * @template T
                     * @template U
                     * @param callable(T): U $predicate
                     * @return Closure(iterable<T>): iterable<U>
                     */
                    function map(callable $predicate): callable {
                        return function(iterable $iter) use ($predicate): iterable {
                            foreach ($iter as $key => $value) {
                                yield $key => $predicate($value);
                            }
                        };
                    }

                    $res = map(function(int $i): string { return (string) $i; })([1,2,3]);
                ',
                'assertions' => [
                    '$res' => 'iterable<mixed, numeric-string>',
                ],
            ],
            'infersCallableReturnTypes' => [
                '<?php
                    /**
                     * @template T
                     * @template U
                     * @param callable(T): U $predicate
                     * @return callable(iterable<T>): iterable<U>
                     */
                    function map(callable $predicate): callable {
                        return function($iter) use ($predicate) {
                            foreach ($iter as $key => $value) {
                                yield $key => $predicate($value);
                            }
                        };
                    }

                    $res = map(function(int $i): string { return (string) $i; })([1,2,3]);
                ',
                'assertions' => [
                    '$res' => 'iterable<mixed, numeric-string>',
                ],
            ],
            'infersCallableReturnTypesWithPartialTypehinting' => [
                '<?php
                    /**
                     * @template T
                     * @template U
                     * @param callable(T): U $predicate
                     * @return callable(iterable<T>): iterable<U>
                     */
                    function map(callable $predicate): callable {
                        return function(iterable $iter) use ($predicate): iterable {
                            foreach ($iter as $key => $value) {
                                yield $key => $predicate($value);
                            }
                        };
                    }

                    $res = map(function(int $i): string { return (string) $i; })([1,2,3]);
                ',
                'assertions' => [
                    '$res' => 'iterable<mixed, numeric-string>',
                ],
            ],
            'mixedAssignmentWithUnderscore' => [
                '<?php
                    $gen = (function (): Generator {
                        yield 1 => \'a\';
                        yield 2 => \'b\';
                    })();

                    foreach ($gen as $k => $_) {
                        echo "$k\n";
                    }'
            ],
            'allowImplicitNever' => [
                '<?php
                    class TestCase
                    {
                        /** @psalm-return never-return */
                        public function markAsSkipped(): void
                        {
                            throw new \Exception();
                        }
                    }
                    class A extends TestCase
                    {
                        /**
                         * @return string[]
                         */
                        public function foo(): array
                        {
                            $this->markAsSkipped();
                        }
                    }

                    class B extends A
                    {
                        public function foo(): array
                        {
                            return ["foo"];
                        }
                    }'
            ],
            'compareObjectLikeToPotentiallyUnfilledArray' => [
                '<?php
                    /**
                     * @param array<"from"|"to", bool> $a
                     * @return array{from?: bool, to?: bool}
                     */
                    function foo(array $a) : array {
                        return $a;
                    }',
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse()
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
                'error_message' => 'LessSpecificImplementedReturnType',
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
            'mixedReturnTypeCoercion' => [
                '<?php
                    /** @return string[] */
                    function foo(array $a) : array {
                        return $a;
                    }',
                'error_message' => 'MixedReturnTypeCoercion',
            ],
            'detectMagicMethodBadReturnType' => [
                '<?php
                    class C {
                        public function __invoke(): int {}
                    }',
                'error_message' => 'InvalidReturnType',
            ],
            'callNeverReturns' => [
                '<?php
                    namespace Foo;
                    /**
                     * @return never-returns
                     */
                    function foo() : void {
                        exit();
                    }

                    $a = foo();',
                'error_message' => 'NoValue',
            ],
            'returnNeverReturns' => [
                '<?php
                    namespace Foo;
                    /**
                     * @return never-returns
                     */
                    function foo() : void {
                        exit();
                    }

                    function bar() : void {
                        return foo();
                    }',
                'error_message' => 'NoValue',
            ],
            'useNeverReturnsAsArg' => [
                '<?php
                    namespace Foo;
                    /**
                     * @return never-returns
                     */
                    function foo() : void {
                        exit();
                    }

                    function bar(string $s) : void {}

                    bar(foo());',
                'error_message' => 'NoValue',
            ],
            'invalidNoReturnType' => [
                '<?php
                    namespace Foo;
                    /**
                     * @return never-returns
                     */
                    function foo() : void {
                    }',
                'error_message' => 'InvalidReturnType',
            ],
            'invalidNoReturnStatement' => [
                '<?php
                    namespace Foo;
                    /**
                     * @return never-returns
                     */
                    function foo() : void {
                        return 5;
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'invalidReturnTypeCorrectLine' => [
                '<?php
                    function f1(
                        int $a
                    ): string {}',
                'error_message' => 'InvalidReturnType - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:24',
            ],
            'cannotInferReturnClosureWithoutReturn' => [
                '<?php
                /**
                 * @template T
                 * @template U
                 * @param callable(T): U $predicate
                 * @return callable(iterable<T>): iterable<U>
                 */
                function map(callable $predicate): callable {
                    $a = function($iter) use ($predicate) {
                        foreach ($iter as $key => $value) {
                            yield $key => $predicate($value);
                        }
                    };
                    return $a;
                }

                $res = map(function(int $i): string { return (string) $i; })([1,2,3]);
                ',
                'error_message' => 'MixedAssignment - src' . DIRECTORY_SEPARATOR . 'somefile.php:10:51 - Unable to determine the type that $value is being assigned to',
            ],
            'cannotInferReturnClosureWithMoreSpecificTypes' => [
                '<?php
                /**
                 * @template T
                 * @template U
                 * @param callable(T): U $predicate
                 * @return callable(iterable<T>): iterable<U>
                 */
                function map(callable $predicate): callable {
                    return
                    /** @param iterable<int> $iter */
                    function($iter) use ($predicate) {
                        foreach ($iter as $key => $value) {
                            yield $key => $predicate($value);
                        }
                    };
                }

                $res = map(function(int $i): string { return (string) $i; })([1,2,3]);
                ',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:13:54 - Argument 1 expects T:fn-map as mixed, int provided',
            ],
            'cannotInferReturnClosureWithDifferentReturnTypes' => [
                '<?php
                /**
                 * @template T
                 * @template U
                 * @param callable(T): U $predicate
                 * @return callable(iterable<T>): iterable<U>
                 */
                function map(callable $predicate): callable {
                    return function($iter) use ($predicate): int {
                        return 1;
                    };
                }

                $res = map(function(int $i): string { return (string) $i; })([1,2,3]);
                ',
                'error_message' => 'InvalidReturnStatement - src' . DIRECTORY_SEPARATOR . 'somefile.php:9:28 - The inferred type \'Closure(iterable<mixed, T:fn-map as mixed>):int(1)\' does not match the declared return type \'callable(iterable<mixed, T:fn-map as mixed>):iterable<mixed, U:fn-map as mixed>\' for map',
            ],
            'cannotInferReturnClosureWithDifferentTypes' => [
                '<?php
                class A {}
                class B {}
                /**
                 * @return callable(A): void
                 */
                function map(): callable {
                    return function(B $v): void {};
                }

                $res = map(function(int $i): string { return (string) $i; })([1,2,3]);
                ',
                'error_message' => 'InvalidReturnStatement - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:28 - The inferred type \'Closure(B):void\' does not match the declared return type \'callable(A):void\' for map',
            ],
            'compareObjectLikeToAlwaysFilledArray' => [
                '<?php
                    /**
                     * @param array<"from"|"to", bool> $a
                     * @return array{from: bool, to: bool}
                     */
                    function foo(array $a) : array {
                        return $a;
                    }',
                'error_message' => 'LessSpecificReturnStatement',
            ],
        ];
    }
}
