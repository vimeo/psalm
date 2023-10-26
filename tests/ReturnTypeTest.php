<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

class ReturnTypeTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'arrayCombine' => [
                'code' => '<?php
                    class a {}

                    /**
                     * @return list{0, 0}|list<a>
                     */
                    function ret() {
                        return [new a, new a, new a];
                    }

                    $result = ret();
                ',
                'assertions' => [
                    '$result===' => 'list{0?: 0|a, 1?: 0|a, ...<a>}',
                ],
            ],
            'arrayCombineInv' => [
                'code' => '<?php
                    class a {}

                    /**
                     * @return list<a>|list{0, 0}
                     */
                    function ret() {
                        return [new a, new a, new a];
                    }

                    $result = ret();
                ',
                'assertions' => [
                    '$result===' => 'list{0?: 0|a, 1?: 0|a, ...<a>}',
                ],
            ],
            'arrayCombine2' => [
                'code' => '<?php
                    class a {}

                    /**
                     * @return array{test1: 0, test2: 0}|list<a>
                     */
                    function ret() {
                        return [new a, new a, new a];
                    }

                    $result = ret();
                ',
                'assertions' => [
                    '$result===' => 'array{0?: a, test1?: 0, test2?: 0, ...<int<0, max>, a>}',
                ],
            ],
            'returnTypeAfterUselessNullCheck' => [
                'code' => '<?php
                    class One {}

                    class B {
                        /**
                         * @return One|null
                         */
                        public function barBar() {
                            $baz = rand(0,100) > 50 ? new One() : null;

                            // should have no effect
                            if ($baz === null) {
                                $baz = null;
                            }

                            return $baz;
                        }
                    }',
            ],
            'returnTypeNotEmptyCheck' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
            'extendsStaticConstReturnType' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        private const FOO = 1;

                        /** @return static::FOO */
                        public function getFoo() {
                            return self::FOO;
                        }
                    }

                    class B extends A {
                        /** @var int */
                        private const FOO = 2;

                        public function getFoo() {
                            return self::FOO;
                        }
                    }',
            ],
            'issetReturnType' => [
                'code' => '<?php
                    /**
                     * @param  mixed $foo
                     * @return bool
                     */
                    function a($foo = null) {
                        return isset($foo);
                    }',
            ],
            'thisReturnType' => [
                'code' => '<?php
                    class A {
                        /** @return $this */
                        public function getThis() {
                            return $this;
                        }
                    }',
            ],
            'overrideReturnType' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    class A {}
                    class B extends A {}

                    /** @return B|A */
                    function foo() {
                      return rand(0, 1) ? new A : new B;
                    }',
            ],
            'issetOnPropertyReturnType' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    /** @return resource */
                    function getOutput() {
                        $res = fopen("php://output", "w") or die();

                        return $res;
                    }',
            ],
            'resourceParamType' => [
                'code' => '<?php
                    /** @param resource $res */
                    function doSomething($res): void {
                    }',
            ],
            'returnArrayOfNullable' => [
                'code' => '<?php
                    /**
                     * @return array<?stdClass>
                     */
                    function getBarWithIsset() {
                        if (rand() % 2 > 0) return [new stdClass()];
                        return [null];
                    }',
            ],
            'selfReturnNoTypehints' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    /** @return bool */
                    function foo(): bool {
                        return true;
                    }',
            ],
            'iteratorReturnTypeFromGenerator' => [
                'code' => '<?php
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
                'ignored_issues' => ['MixedAssignment', 'MixedArgument'],
            ],
            'objectLikeArrayOptionalKeyReturn' => [
                'code' => '<?php
                    /** @return array{a: int, b?: int} */
                    function foo() : array {
                        return rand(0, 1) ? ["a" => 1, "b" => 2] : ["a" => 2];
                    }',
            ],
            'objectLikeArrayOptionalKeyReturnSeparateStatements' => [
                'code' => '<?php
                    /** @return array{a: int, b?: int} */
                    function foo() : array {
                        if (rand(0, 1)) {
                            return ["a" => 1, "b" => 2];
                        }

                        return ["a" => 2];
                    }',
            ],
            'arrayReturnTypeWithExplicitKeyType' => [
                'code' => '<?php
                    /** @return array<int|string, mixed> */
                    function returnsArray(array $arr) : array {
                        return $arr;
                    }',
            ],
            'namespacedScalarParamAndReturn' => [
                'code' => '<?php
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
                'code' => '<?php
                    function foo() : bool {
                        return true;

                        return false;
                    }',
            ],
            'neverReturnsSimple' => [
                'code' => '<?php
                    namespace Foo;
                    /**
                     * @return never-returns
                     */
                    function foo() : void {
                        exit();
                    }',
            ],
            'neverReturnsCovariance' => [
                'code' => '<?php
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
                'code' => '<?php
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
            'noReturnCallReturnsNever' => [
                'code' => '<?php
                    namespace Foo;
                    /**
                     * @return never
                     */
                    function foo() : void {
                        exit();
                    }

                    /**
                     * @return never
                     */
                    function bar() : void {
                        foo();
                    }',
            ],
            'suppressInvalidReturnType' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php

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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    /**
                     * @template T
                     * @template U
                     * @param callable(T): U $predicate
                     * @return Closure(iterable<int, T>): iterable<int, U>
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
                    '$res===' => 'iterable<int, numeric-string>',
                ],
            ],
            'infersArrowClosureReturnTypes' => [
                'code' => '<?php
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
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'infersClosureReturnTypesWithPartialTypehinting' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @template U
                     * @param callable(T): U $predicate
                     * @return Closure(iterable<int, T>): iterable<int, U>
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
                    '$res===' => 'iterable<int, numeric-string>',
                ],
            ],
            'infersCallableReturnTypes' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @template U
                     * @param callable(T): U $predicate
                     * @return callable(iterable<int, T>): iterable<int, U>
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
                    '$res===' => 'iterable<int, numeric-string>',
                ],
            ],
            'infersCallableReturnTypesWithPartialTypehinting' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @template U
                     * @param callable(T): U $predicate
                     * @return callable(iterable<int, T>): iterable<int, U>
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
                    '$res===' => 'iterable<int, numeric-string>',
                ],
            ],
            'infersObjectShapeOfCastScalar' => [
                'code' => '<?php
                    function returnsInt(): int {
                        return 1;
                    }

                    $obj = (object)returnsInt();
                ',
                'assertions' => [
                    '$obj' => 'object{scalar:int}',
                ],
            ],
            'infersObjectShapeOfCastArray' => [
                'code' => '<?php
                    /**
                     * @return array{a:1}
                     */
                    function returnsArray(): array {
                        return ["a" => 1];
                    }

                    $obj = (object)returnsArray();
                ',
                'assertions' => [
                    '$obj' => 'object{a:int}',
                ],
            ],
            'mixedAssignmentWithUnderscore' => [
                'code' => '<?php
                    $gen = (function (): Generator {
                        yield 1 => \'a\';
                        yield 2 => \'b\';
                    })();

                    foreach ($gen as $k => $_) {
                        echo "$k\n";
                    }',
            ],
            'allowImplicitNever' => [
                'code' => '<?php
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
                    }',
            ],
            'compareTKeyedArrayToPotentiallyUnfilledArray' => [
                'code' => '<?php
                    /**
                     * @param array<"from"|"to", bool> $a
                     * @return array{from?: bool, to?: bool}
                     */
                    function foo(array $a) : array {
                        return $a;
                    }',
            ],
            'returnStaticThis' => [
                'code' => '<?php
                    namespace Foo;

                    class A {
                        public function getThis() : static {
                            return $this;
                        }
                    }

                    class B extends A {
                        public function foo() : void {}
                    }

                    (new B)->getThis()->foo();',
            ],
            'returnMixed' => [
                'code' => '<?php
                    namespace Foo;

                    class A {
                        public function getThis() : mixed {
                            return $this;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'returnsNullSometimes' => [
                'code' => '<?php
                    /** @return null */
                    function f() {
                        if (rand(0, 1)) {
                            return null;
                        }
                        throw new RuntimeException;
                    }
                ',
            ],
            'scalarLiteralsInferredAfterUndefinedClass' => [
                'code' => '<?php
                    /** @param object $arg */
                    function test($arg): ?string
                    {
                        /** @psalm-suppress UndefinedClass */
                        if ($arg instanceof SomeClassThatDoesNotExist) {
                            return null;
                        }

                        return "b";
                    }
                ',
            ],
            'docblockNeverReturn' => [
                'code' => '<?php
                    /** @return never */
                    function returnsNever() {
                        exit();
                    }

                    /** @return false */
                    function foo() : bool {
                        if (rand(0, 1)) {
                            return false;
                        }

                        returnsNever();
                    }',
            ],
            'return0' => [
                'code' => '<?php
                    /**
                     * @return 0
                     */
                    function takesAnInt() {
                        return 0;
                    }',
            ],
            'neverReturnClosure' => [
                'code' => '<?php
                    set_error_handler(
                    function() {
                        print_r(func_get_args());
                        exit(1);
                    });',
            ],
            'ExitInBothBranches' => [
                'code' => '<?php
                    function never_returns(int $a) : bool
                    {
                        if ($a == 1) {
                            throw new \Exception("one");
                        } else {
                            exit(0);
                        }
                    }',
            ],
            'NeverAndVoid' => [
                'code' => '<?php
                    function foo(): void
                    {
                        foreach ([0, 1, 2] as $_i) {
                            return;
                        }

                        throw new \Exception();
                    }',
            ],
            'neverAndVoidOnConditional' => [
                'code' => '<?php
                    /**
                     * @template T as bool
                     * @param T $end
                     * @return (T is true ? never : void)
                     */
                    function a($end): void{
                        if($end){
                            die();
                        }
                    }',
            ],
            'returnTypeOfAbstractAndConcreteMethodFromTemplatedTraits' => [
                'code' => '<?php
                    /** @template T */
                    trait ImplementerTrait {
                        /** @var T */
                        private $value;

                        /** @psalm-return T */
                        public function getValue() {
                            return $this->value;
                        }
                    }

                    /** @template T */
                    trait GuideTrait {
                        /** @psalm-return T */
                        abstract public function getValue();
                    }

                    class Test {
                        /** @use ImplementerTrait<int> */
                        use ImplementerTrait;

                        /** @use GuideTrait<int> */
                        use GuideTrait;

                        public function __construct() {
                            $this->value = 123;
                        }
                    }',
            ],
            'returnTypeOfAbstractMethodFromTemplatedTraitAndImplementationFromNonTemplatedTrait' => [
                'code' => '<?php
                    trait ImplementerTrait {
                        /** @var int */
                        private $value;

                        public function getValue(): int {
                            return $this->value;
                        }
                    }

                    /** @psalm-template T */
                    trait GuideTrait {
                        /** @psalm-return T */
                        abstract public function getValue();
                    }

                    class Test {
                        use ImplementerTrait;

                        /** @template-use GuideTrait<int> */
                        use GuideTrait;

                        public function __construct() {
                            $this->value = 123;
                        }
                    }',
            ],
            'nestedArrayMapReturnTypeDoesntCrash' => [
                'code' => '<?php
                    function bar(array $a): array {
                        return $a;
                    }

                    /**
                     * @param array[] $x
                     *
                     * @return array[]
                     */
                    function foo(array $x): array {
                        return array_map(
                            "array_merge",
                            array_map(
                                "bar",
                                $x
                            ),
                            $x
                        );
                    }
                ',
            ],
            'returningExplicitStringableForStringableObjectReturnType' => [
                'code' => '<?php
                    class C implements Stringable { public function __toString(): string { return __CLASS__; } }

                    /** @return stringable-object */
                    function f(): object {
                        return new C;
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'returningImplicitStringableForStringableObjectReturnType' => [
                'code' => '<?php
                    class C { public function __toString(): string { return __CLASS__; } }

                    /** @return stringable-object */
                    function f(): object {
                        return new C;
                    }
                ',
            ],
            'returningStringableObjectForStringableReturnType' => [
                'code' => '<?php
                    class C implements Stringable { public function __toString(): string { return __CLASS__; } }

                    /** @param stringable-object $p */
                    function f(object $p): Stringable {
                        return $p;
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'newReturnTypesInPhp82' => [
                'code' => '<?php
                    function alwaysTrue(): true {
                        return true;
                    }

                    function alwaysFalse(): false {
                        return false;
                    }

                    function alwaysNull(): null {
                        return null;
                    }
                    $true = alwaysTrue();
                    $false = alwaysFalse();
                    $null = alwaysNull();
                ',
                'assertions' => [
                    '$true===' => 'true',
                    '$false===' => 'false',
                    '$null===' => 'null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'returnListMixedVsListStringIsAMixedError' => [
                'code' => '<?php

                    /**
                     * @psalm-suppress MixedReturnTypeCoercion
                     * @return list<string>
                     */
                    function foo(){
                        /**
                         * @var list<mixed>
                         * @psalm-suppress MixedReturnTypeCoercion
                         */
                        return [];
                    }
                    ',
            ],
            'MixedErrorInArrayShouldBeReportedAsMixedError' => [
                'code' => '<?php
                    /**
                     * @param mixed $configuration
                     * @return array{a?: string, b?: int}
                     * @psalm-suppress MixedReturnTypeCoercion
                     */
                    function produceParameters(array $configuration): array
                    {
                        $parameters = [];

                        foreach (["a", "b"] as $parameter) {
                            /** @psalm-suppress MixedAssignment */
                            $parameters[$parameter] = $configuration;
                        }

                        /** @psalm-suppress MixedReturnTypeCoercion */
                        return $parameters;
                    }
                    ',
            ],
            'NewFromTemplateObject' => [
                'code' => '<?php
                    /** @psalm-consistent-constructor */
                    class AggregateResult {}

                    /**
                     * @template T as AggregateResult
                     * @param T $type
                     * @return T
                     */
                    function aggregate($type) {
                        $t = new $type;
                        return $t;
                    }',
            ],
            'returnByReferenceVariableInStaticMethod' => [
                'code' => <<<'PHP'
                    <?php
                    class Foo {
                        private static string $foo = "foo";

                        public static function &foo(): string {
                            return self::$foo;
                        }
                    }
                    PHP,
            ],
            'returnByReferenceVariableInInstanceMethod' => [
                'code' => <<<'PHP'
                    <?php
                    class Foo {
                        private float $foo = 3.3;

                        public function &foo(): float {
                            return $this->foo;
                        }
                    }
                    PHP,
            ],
            'returnByReferenceVariableInFunction' => [
                'code' => <<<'PHP'
                    <?php
                    function &foo(): array {
                        /** @var array $x */
                        static $x = [1, 2, 3];
                        return $x;
                    }
                    PHP,
            ],
            'neverReturnType' => [
                'code' => '<?php
                    function exitProgram(bool $die): never
                    {
                        if ($die) {
                            die;
                        }

                        exit;
                    }

                    function throwError(): never
                    {
                        throw new Exception();
                    }
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
            'wrongReturnType1' => [
                'code' => '<?php
                    function fooFoo(): string {
                        return 5;
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'wrongReturnType2' => [
                'code' => '<?php
                    function fooFoo(): string {
                        return rand(0, 5) ? "hello" : null;
                    }',
                'error_message' => 'NullableReturnStatement',
            ],
            'wrongReturnTypeInNamespace1' => [
                'code' => '<?php
                    namespace bar;

                    function fooFoo(): string {
                        return 5;
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'wrongReturnTypeInNamespace2' => [
                'code' => '<?php
                    namespace bar;

                    function fooFoo(): string {
                        return rand(0, 5) ? "hello" : null;
                    }',
                'error_message' => 'NullableReturnStatement',
            ],
            'missingReturnType' => [
                'code' => '<?php
                    function fooFoo() {
                        return rand(0, 5) ? "hello" : null;
                    }',
                'error_message' => 'MissingReturnType',
            ],
            'mixedInferredReturnType' => [
                'code' => '<?php
                    function fooFoo(array $arr): string {
                        /** @psalm-suppress MixedReturnStatement */
                        return array_pop($arr);
                    }',
                'error_message' => 'MixedInferredReturnType',
            ],
            'mixedInferredReturnStatement' => [
                'code' => '<?php
                    function fooFoo(array $arr): string {
                        return array_pop($arr);
                    }',
                'error_message' => 'MixedReturnStatement',
            ],
            'invalidReturnTypeClass' => [
                'code' => '<?php
                    function fooFoo(): A {
                        return new A;
                    }',
                'error_message' => 'UndefinedClass',
                'ignored_issues' => ['MixedInferredReturnType'],
            ],
            'invalidClassOnCall' => [
                'code' => '<?php
                    /**
                     * @psalm-suppress UndefinedClass
                     */
                    function fooFoo(): A {
                        return $GLOBALS["a"];
                    }

                    fooFoo()->bar();',
                'error_message' => 'UndefinedClass',
                'ignored_issues' => ['MixedInferredReturnType', 'MixedReturnStatement'],
            ],
            'returnArrayOfNullableInvalid' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    function doSomething(resource $res): void {
                    }',
                'error_message' => 'ReservedWord',
            ],
            'voidParamType' => [
                'code' => '<?php
                    function f(void $p): void {}',
                'error_message' => 'ReservedWord',
            ],
            'voidClass' => [
                'code' => '<?php
                    class void {}',
                'error_message' => 'ReservedWord',
            ],
            'disallowReturningExplicitVoid' => [
                'code' => '<?php
                    function returnsVoid(): void {}

                    function alsoReturnsVoid(): void {
                      return returnsVoid();
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'complainAboutTKeyedArrayWhenArrayIsFound' => [
                'code' => '<?php
                    /** @return array{a:string,b:string,c:string} */
                    function foo(): array {
                      $arr = [];
                      foreach (["a", "b"] as $key) {
                        $arr[$key] = "foo";
                      }
                      return $arr;
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'invalidVoidStatementWhenMixedInferred' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    function foo(): ?string {
                      if (rand(0, 1)) return "hello";
                    }',
                'error_message' => 'InvalidReturnType',
            ],
            'returnTypehintWithVoidReturnType' => [
                'code' => '<?php
                    function foo(): ?string {
                      if (rand(0, 1)) {
                        return;
                      }

                      return "hello";
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'invalidReturnStatementMoreAccurateThanFalsable' => [
                'code' => '<?php
                    class A1{}
                    class B1{}

                    function testFalseable() : A1 {
                        return (rand() % 2 === 0) ? (new B1()) : false;
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'invalidReturnTypeMoreAccurateThanFalsable' => [
                'code' => '<?php
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
                'code' => '<?php
                    /** @return ArrayIterator<int, string> */
                    function foo(array $a) {
                        $obj = new ArrayObject([1, 2, 3, 4]);
                        return $obj->getIterator();
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'objectLikeArrayOptionalKeyWithNonOptionalReturn' => [
                'code' => '<?php
                    /** @return array{a: int, b: int} */
                    function foo() : array {
                        return rand(0, 1) ? ["a" => 1, "b" => 2] : ["a" => 2];
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'mixedReturnTypeCoercion' => [
                'code' => '<?php
                    /** @return string[] */
                    function foo(array $a) : array {
                        return $a;
                    }',
                'error_message' => 'MixedReturnTypeCoercion',
            ],
            'detectMagicMethodBadReturnType' => [
                'code' => '<?php
                    class C {
                        public function __invoke(): int {}
                    }',
                'error_message' => 'InvalidReturnType',
            ],
            'callNeverReturns' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    namespace Foo;
                    /**
                     * @return never-returns
                     */
                    function foo() : void {
                    }',
                'error_message' => 'InvalidReturnType',
            ],
            'invalidNoReturnStatement' => [
                'code' => '<?php
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
                'code' => '<?php
                    function f1(
                        int $a
                    ): string {}',
                'error_message' => 'InvalidReturnType - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:24',
            ],
            'cannotInferReturnClosureWithoutReturn' => [
                'code' => '<?php
                /**
                 * @template T
                 * @template U
                 * @param callable(T): U $predicate
                 * @return callable(iterable<int, T>): iterable<int, U>
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
                'error_message' => 'MixedAssignment - src' . DIRECTORY_SEPARATOR . 'somefile.php:10:43 - Unable to determine the type that $key is being assigned to',
            ],
            'cannotInferReturnClosureWithMoreSpecificTypes' => [
                'code' => '<?php
                /**
                 * @template T
                 * @template U
                 * @param callable(T): U $predicate
                 * @return callable(iterable<int, T>): iterable<int, U>
                 */
                function map(callable $predicate): callable {
                    return
                    /** @param iterable<int, int> $iter */
                    function($iter) use ($predicate) {
                        foreach ($iter as $key => $value) {
                            yield $key => $predicate($value);
                        }
                    };
                }

                $res = map(function(int $i): string { return (string) $i; })([1,2,3]);
                ',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:13:54 - Argument 1 expects T:fn-map as mixed, but int provided',
            ],
            'cannotInferReturnClosureWithDifferentReturnTypes' => [
                'code' => '<?php
                /**
                 * @template T
                 * @template U
                 * @param callable(T): U $predicate
                 * @return callable(iterable<int, T>): iterable<int, U>
                 */
                function map(callable $predicate): callable {
                    return function($iter) use ($predicate): int {
                        return 1;
                    };
                }',
                'error_message' => 'InvalidReturnStatement - src' . DIRECTORY_SEPARATOR . 'somefile.php:9:28 - The inferred type \'pure-Closure(iterable<int, T:fn-map as mixed>):1\' does not match the declared return type \'callable(iterable<int, T:fn-map as mixed>):iterable<int, U:fn-map as mixed>\' for map',
            ],
            'cannotInferReturnClosureWithDifferentTypes' => [
                'code' => '<?php
                class A {}
                class B {}
                /**
                 * @return callable(A): void
                 */
                function map(): callable {
                    return function(B $v): void {};
                }',
                'error_message' => 'InvalidReturnStatement - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:28 - The inferred type \'pure-Closure(B):void\' does not match the declared return type \'callable(A):void\' for map',
            ],
            'compareTKeyedArrayToAlwaysFilledArray' => [
                'code' => '<?php
                    /**
                     * @param array<"from"|"to", bool> $a
                     *
                     * This is unsealed because there is no way to mark a TArray as sealed.
                     *
                     * @return array{from: bool, to: bool}
                     */
                    function foo(array $a) : array {
                        return $a;
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'docblockishTypeMustReturn' => [
                'code' => '<?php
                    /**
                     * @return "a"|"b"|null
                     */
                    function foo() : ?string {
                        if (rand(0, 1)) {
                            return "a";
                        }

                        if (rand(0, 1)) {
                            return "b";
                        }
                    }',
                'error_message' => 'InvalidReturnType',
            ],
            'objectWhereObjectWithPropertiesIsExpected' => [
                'code' => '<?php
                    function makeObj(): object {
                        return (object)["a" => 42];
                    }

                    /** @return object{hmm:float} */
                    function f(): object {
                        return makeObj();
                    }
                ',
                'error_message' => 'LessSpecificReturnStatement',
            ],
            'objectCastFromArrayWithMissingKey' => [
                'code' => '<?php
                    /** @return object{status: string} */
                    function foo(): object {
                        return (object) [
                            "notstatus" => "failed",
                        ];
                    }
                ',
                'error_message' => 'InvalidReturnStatement',
            ],
            'lessSpecificImplementedReturnTypeFromTemplatedTraitMethod' => [
                'code' => '<?php
                    /** @template T */
                    trait ImplementerTrait {
                        /** @var T */
                        private $value;

                        /** @psalm-return T */
                        public function getValue() {
                            return $this->value;
                        }
                    }

                    /** @template T */
                    trait GuideTrait {
                        /** @psalm-return T */
                        abstract public function getValue();
                    }

                    /** @template T */
                    class Test {
                        /** @use ImplementerTrait<T> */
                        use ImplementerTrait;

                        /** @use GuideTrait<int> */
                        use GuideTrait;

                        public function __construct() {
                            $this->value = 123;
                        }
                    }',
                    'error_message' => 'LessSpecificImplementedReturnType',
            ],
            'badlyCasedReturnType' => [
                'code' => '<?php
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
                'error_message' => 'InvalidClass',
            ],
            'listItems' => [
                'code' => <<<'PHP'
                    <?php

                    /** @return list<int> */
                    function f(): array
                    {
                        return[ 1, new stdClass, "zzz"];
                    }
                    PHP,
                'error_message' => 'InvalidReturnStatement',
            ],
            'invalidReturnStatementDetectedInOverriddenMethod' => [
                'code' => <<<'PHP'
                    <?php
                    /** @template T */
                    interface I
                    {
                        /** @return T */
                        public function process(): mixed;
                    }
                    /** @implements I<int> */
                    final class B implements I
                    {
                        public function process(): mixed
                        {
                            return '';
                        }
                    }
                    PHP,
                'error_message' => 'InvalidReturnStatement',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'returnByReferenceNonVariableInStaticMethod' => [
                'code' => <<<'PHP'
                    <?php
                    class Foo {
                        public static function &foo(string $x): string {
                            return $x . "bar";
                        }
                    }
                    PHP,
                'error_message' => 'NonVariableReferenceReturn',
            ],
            'returnByReferenceNonVariableInInstanceMethod' => [
                'code' => <<<'PHP'
                    <?php
                    class Foo {
                        public function &foo(): iterable {
                            return [] + [1, 2];
                        }
                    }
                    PHP,
                'error_message' => 'NonVariableReferenceReturn',
            ],
            'returnByReferenceNonVariableInFunction' => [
                'code' => <<<'PHP'
                    <?php
                    function &foo(): array {
                        return [1, 2, 3];
                    }
                    PHP,
                'error_message' => 'NonVariableReferenceReturn',
            ],
            'implicitReturnFromFunctionWithNeverReturnType' => [
                'code' => <<<'PHP'
                    <?php
                    function foo(): never
                    {
                        if (rand(0, 1)) {
                            exit();
                        }
                    }
                    PHP,
                'error_message' => 'InvalidReturnType',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'implicitReturnFromFunctionWithNeverReturnType2' => [
                'code' => <<<'PHP'
                    <?php
                    function foo(bool $x): never
                    {
                        while (true) {
                            if ($x) {
                                break;
                            }
                        }
                    }
                    PHP,
                'error_message' => 'InvalidReturnType',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
        ];
    }
}
