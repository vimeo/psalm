<?php
namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class CallableTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'byRefUseVar' => [
                '<?php
                    /** @return void */
                    function run_function(\Closure $fnc) {
                        $fnc();
                    }

                    /**
                     * @return void
                     * @psalm-suppress MixedArgument
                     */
                    function f() {
                        run_function(
                            /**
                             * @return void
                             */
                            function() use(&$data) {
                                $data = 1;
                            }
                        );
                        echo $data;
                    }

                    f();',
            ],
            'inferredArg' => [
                '<?php
                    $bar = ["foo", "bar"];

                    $bam = array_map(
                        function(string $a) {
                            return $a . "blah";
                        },
                        $bar
                    );',
            ],
            'inferredArgArrowFunction' => [
                '<?php
                    $bar = ["foo", "bar"];

                    $bam = array_map(
                        fn(string $a) => $a . "blah",
                        $bar
                    );',
                'assertions' => [],
                'error_levels' => [],
                '7.4',
            ],
            'inferArgFromClassContext' => [
                '<?php
                    final class Calc
                    {
                        /**
                         * @param Closure(int, int): int $_fn
                         */
                        public function __invoke(Closure $_fn): void
                        {
                            return $_fn(42, 42);
                        }
                    }

                    $calc = new Calc();

                    $a = $calc(fn($a, $b) => $a + $b);',
                'assertions' => [
                    '$a' => 'int',
                ],
                'error_levels' => [],
                '7.4',
            ],
            'varReturnType' => [
                '<?php
                    $add_one = function(int $a) : int {
                        return $a + 1;
                    };

                    $a = $add_one(1);',
                'assertions' => [
                    '$a' => 'int',
                ],
            ],
            'varReturnTypeArray' => [
                '<?php
                    $add_one = fn(int $a) : int => $a + 1;

                    $a = $add_one(1);',
                'assertions' => [
                    '$a' => 'int',
                ],
                'error_levels' => [],
                '7.4',
            ],
            'varCallableParamReturnType' => [
                '<?php
                    $add_one = function(int $a): int {
                        return $a + 1;
                    };

                    /**
                     * @param  callable(int) : int $c
                     */
                    function bar(callable $c) : int {
                        return $c(1);
                    }

                    bar($add_one);',
            ],
            'callableToClosure' => [
                '<?php
                    /**
                     * @return callable
                     */
                    function foo() {
                        return function(string $a): string {
                            return $a . "blah";
                        };
                    }',
            ],
            'callableToClosureArrow' => [
                '<?php
                    /**
                     * @return callable
                     */
                    function foo() {
                        return fn(string $a): string => $a . "blah";
                    }',
                'assertions' => [],
                'error_levels' => [],
                '7.4',
            ],
            'callable' => [
                '<?php
                    function foo(callable $c): void {
                        echo (string)$c();
                    }',
            ],
            'callableClass' => [
                '<?php
                    class C {
                        public function __invoke(): string {
                            return "You ran?";
                        }
                    }

                    function foo(callable $c): void {
                        echo (string)$c();
                    }

                    foo(new C());

                    $c2 = new C();
                    $c2();',
            ],
            'correctParamType' => [
                '<?php
                    $take_string = function(string $s): string { return $s; };
                    $take_string("string");',
            ],
            'callableMethodStringCallable' => [
                '<?php
                    class A {
                        public static function bar(string $a): string {
                            return $a . "b";
                        }
                    }

                    function foo(callable $c): void {}

                    foo("A::bar");
                    foo(A::class . "::bar");',
            ],
            'callableMethodArrayCallable' => [
                '<?php
                    class A {
                        public static function bar(string $a): string {
                            return $a . "b";
                        }
                    }

                    function foo(callable $c): void {}

                    foo(["A", "bar"]);
                    foo([A::class, "bar"]);
                    $a = new A();
                    foo([$a, "bar"]);',
            ],
            'callableMethodArrayCallableMissingTypes' => [
                '<?php
                    function foo(callable $c): void {}

                    /** @psalm-suppress MissingParamType */
                    function bar($a, $b) : void {
                        foo([$a, $b]);
                    }',
            ],
            'arrayMapCallableMethod' => [
                '<?php
                    class A {
                        public static function bar(string $a): string {
                            return $a . "b";
                        }
                    }

                    function baz(string $a): string {
                        return $a . "b";
                    }

                    $a = array_map("A::bar", ["one", "two"]);
                    $b = array_map(["A", "bar"], ["one", "two"]);
                    $c = array_map([A::class, "bar"], ["one", "two"]);
                    $d = array_map([new A(), "bar"], ["one", "two"]);
                    $a_instance = new A();
                    $e = array_map([$a_instance, "bar"], ["one", "two"]);
                    $f = array_map("baz", ["one", "two"]);',
                'assertions' => [
                    '$a' => 'array{string, string}',
                    '$b' => 'array{string, string}',
                    '$c' => 'array{string, string}',
                    '$d' => 'array{string, string}',
                    '$e' => 'array{string, string}',
                    '$f' => 'array{string, string}',
                ],
            ],
            'arrayCallableMethod' => [
                '<?php
                    class A {
                        public static function bar(string $a): string {
                            return $a . "b";
                        }
                    }

                    function foo(callable $c): void {}

                    foo(["A", "bar"]);',
            ],
            'callableFunction' => [
                '<?php
                    function foo(callable $c): void {}

                    foo("trim");',
            ],
            'inlineCallableFunction' => [
                '<?php
                    class A {
                        function bar(): void {
                            function foobar(int $a, int $b): int {
                                return $a > $b ? 1 : 0;
                            }

                            $arr = [5, 4, 3, 1, 2];

                            usort($arr, "fooBar");
                        }
                    }',
            ],
            'closureSelf' => [
                '<?php
                    class A
                    {
                        /**
                         * @var self[]
                         */
                        private $subitems;

                        /**
                         * @param self[] $in
                         */
                        public function __construct(array $in = [])
                        {
                            array_map(function(self $i): self { return $i; }, $in);

                            $this->subitems = array_map(
                              function(self $i): self {
                                return $i;
                              },
                              $in
                            );
                        }
                    }

                    new A([new A, new A]);',
            ],
            'possiblyUndefinedFunction' => [
                '<?php
                      /**
                       * @param string|callable $middlewareOrPath
                       */
                      function pipe($middlewareOrPath, ?callable $middleware = null): void {  }

                    pipe("zzzz", function() : void {});',
            ],
            'callableWithNonInvokable' => [
                '<?php
                    function asd(): void {}
                    class B {}

                    /**
                     * @param callable|B $p
                     */
                    function passes($p): void {}

                    passes("asd");',
            ],
            'callableWithInvokable' => [
                '<?php
                    function asd(): void {}
                    class A { public function __invoke(): void {} }

                    /**
                     * @param callable|A $p
                     */
                    function fails($p): void {}

                    fails("asd");',
            ],
            'isCallableArray' => [
                '<?php
                    class A
                    {
                        public function callMeMaybe(string $method): void
                        {
                            $handleMethod = [$this, $method];

                            if (is_callable($handleMethod)) {
                                $handleMethod();
                            }
                        }

                        public function foo(): void {}
                    }
                    $a = new A();
                    $a->callMeMaybe("foo");',
            ],
            'isCallableString' => [
                '<?php
                    function foo(): void {}

                    function callMeMaybe(string $method): void {
                        if (is_callable($method)) {
                            $method();
                        }
                    }

                    callMeMaybe("foo");',
            ],
            'allowVoidCallable' => [
                '<?php
                    /**
                     * @param callable():void $p
                     */
                    function doSomething($p): void {}
                    doSomething(function(): bool { return false; });',
            ],
            'callableProperties' => [
                '<?php
                    class C {
                        /** @psalm-var callable():bool */
                        private $callable;

                        /**
                         * @psalm-param callable():bool $callable
                         */
                        public function __construct(callable $callable) {
                            $this->callable = $callable;
                        }

                        public function callTheCallableDirectly(): bool {
                            return ($this->callable)();
                        }

                        public function callTheCallableIndirectly(): bool {
                            $r = $this->callable;
                            return $r();
                        }
                    }',
            ],
            'invokableProperties' => [
                '<?php
                    class A {
                        public function __invoke(): bool { return true; }
                    }

                    class C {
                        /** @var A $invokable */
                        private $invokable;

                        public function __construct(A $invokable) {
                            $this->invokable = $invokable;
                        }

                        public function callTheInvokableDirectly(): bool {
                            return ($this->invokable)();
                        }

                        public function callTheInvokableIndirectly(): bool {
                            $r = $this->invokable;
                            return $r();
                        }
                    }',
            ],
            'nullableReturnTypeShorthand' => [
                '<?php
                    class A {}
                    /** @param callable(mixed):?A $a */
                    function foo(callable $a): void {}',
            ],
            'callablesCanBeObjects' => [
                '<?php
                    function foo(callable $c) : void {
                        if (is_object($c)) {
                            $c();
                        }
                    }',
            ],
            'objectsCanBeCallable' => [
                '<?php
                    function foo(object $c) : void {
                        if (is_callable($c)) {
                            $c();
                        }
                    }',
            ],
            'unionCanBeCallable' => [
                '<?php
                    class A {}
                    class B {
                        public function __invoke() : string {
                            return "hello";
                        }
                    }
                    /**
                     * @param A|B $c
                     */
                    function foo($c) : void {
                        if (is_callable($c)) {
                            $c();
                        }
                    }',
            ],
            'goodCallableArgs' => [
                '<?php
                    /**
                     * @param callable(string,string):int $_p
                     */
                    function f(callable $_p): void {}

                    class C {
                        public static function m(string $a, string $b): int { return $a <=> $b; }
                    }

                    f("strcmp");
                    f([new C, "m"]);
                    f([C::class, "m"]);',
            ],
            'callableWithSpaces' => [
                '<?php
                    /**
                     * @param callable(string, string) : int $p
                     */
                    function f(callable $p): void {}',
            ],
            'fileExistsCallable' => [
                '<?php
                    /** @return string[] */
                    function foo(string $prospective_file_path) : array {
                        return array_filter(
                            glob($prospective_file_path),
                            "file_exists"
                        );
                    }',
            ],
            'callableSelfArg' => [
                '<?php
                    class C extends B {}

                    $b = new B();
                    $c = new C();

                    $b->func2(function(B $x): void {});
                    $c->func2(function(B $x): void {});

                    class A {}

                    class B extends A {
                        /**
                         * @param callable(self) $f
                         */
                        function func2(callable $f): void {
                            $f($this);
                        }
                    }',
            ],
            'callableParentArg' => [
                '<?php
                    class C extends B {}

                    $b = new B();
                    $c = new C();

                    $b->func3(function(A $x): void {});
                    $c->func3(function(A $x): void {});

                    class A {}

                    class B extends A {
                        /**
                         * @param callable(parent) $f
                         */
                        function func3(callable $f): void {
                            $f($this);
                        }
                    }',
            ],
            'callableStaticArg' => [
                '<?php
                    class C extends B {}

                    $b = new B();
                    $c = new C();

                    $b->func1(function(B $x): void {});
                    $c->func1(function(C $x): void {});

                    class A {}

                    class B extends A {
                        /**
                         * @param callable(static) $f
                         */
                        function func1(callable $f): void {
                            $f($this);
                        }
                    }',
            ],
            'callableStaticReturn' => [
                '<?php
                    class A {}

                    class B extends A {
                        /**
                         * @param callable():static $f
                         */
                        function func1(callable $f): void {}
                    }

                    final class C extends B {}

                    $c = new C();

                    $c->func1(function(): C { return new C(); });',
            ],
            'callableSelfReturn' => [
                '<?php
                    class A {}

                    class B extends A {
                        /**
                         * @param callable():self $f
                         */
                        function func2(callable $f): void {}
                    }

                    final class C extends B {}

                    $b = new B();
                    $c = new C();

                    $b->func2(function() { return new B(); });
                    $c->func2(function() { return new C(); });',
            ],
            'callableParentReturn' => [
                '<?php
                    class A {}

                    class B extends A {
                        /**
                         * @param callable():parent $f
                         */
                        function func3(callable $f): void {}
                    }

                    $b = new B();

                    $b->func3(function() { return new A(); });',
            ],
            'selfArrayMapCallableWrongClass' => [
                '<?php
                    class Foo {
                        public function __construct(int $param) {}

                        public static function foo(int $param): Foo {
                            return new self($param);
                        }
                        public static function baz(int $param): self {
                            return new self($param);
                        }
                    }

                    class Bar {
                        /**
                         * @return array<int, Foo>
                         */
                        public function bar() {
                            return array_map([Foo::class, "foo"], [1,2,3]);
                        }
                        /** @return array<int, Foo> */
                        public function bat() {
                            return array_map([Foo::class, "baz"], [1]);
                        }
                    }',
            ],
            'dynamicCallableArray' => [
                '<?php
                    class A {
                        /** @var string */
                        private $value = "default";

                        private function modify(string $name, string $value): void {
                            call_user_func([$this, "modify" . $name], $value);
                        }

                        public function modifyFoo(string $value): void {
                            $this->value = $value;
                        }
                    }',
            ],
            'callableIsArrayAssertion' => [
                '<?php
                    function foo(callable $c) : void {
                        if (is_array($c)) {
                            echo $c[1];
                        }
                    }',
            ],
            'callableOrArrayIsArrayAssertion' => [
                '<?php
                    /**
                     * @param callable|array $c
                     */
                    function foo($c) : void {
                        if (is_array($c) && isset($c[1]) && is_string($c[1])) {
                            echo $c[1];
                        }
                    }',
            ],
            'dontInferMethodIdWhenFormatDoesntFit' => [
                '<?php
                    /** @param string|callable $p */
                    function f($p): array {
                      return [];
                    }
                    f("#b::a");'
            ],
            'removeCallableAssertionAfterReassignment' => [
                '<?php
                    function foo(string $key) : void {
                        $setter = "a" . $key;
                        if (is_callable($setter)) {
                            return;
                        }
                        $setter = "b" . $key;
                        if (is_callable($setter)) {}
                    }'
            ],
            'noExceptionOnSelfString' => [
                '<?php
                    class Fish {
                        public static function example(array $vals): void {
                            usort($vals, ["self", "compare"]);
                        }

                        /**
                         * @param mixed $a
                         * @param mixed $b
                         */
                        public static function compare($a, $b): int {
                            return -1;
                        }
                    }',
            ],
            'noFatalErrorOnClassWithSlash' => [
                '<?php
                    class Func {
                        public function __construct(string $name, callable $callable) {}
                    }

                    class Foo {
                        public static function bar(): string { return "asd"; }
                    }

                    new Func("f", ["\Foo", "bar"]);',
            ],
            'staticReturningCallable' => [
                '<?php
                    abstract class Id
                    {
                        /**
                         * @var string
                         */
                        private $id;

                        final protected function __construct(string $id)
                        {
                            $this->id = $id;
                        }

                        /**
                         * @return static
                         */
                        final public static function fromString(string $id): self
                        {
                            return new static($id);
                        }
                    }

                    final class CriterionId extends Id
                    {
                    }

                    final class CriterionIds
                    {
                        /**
                         * @psalm-var non-empty-list<CriterionId>
                         */
                        private $ids;

                        /**
                         * @psalm-param non-empty-list<CriterionId> $ids
                         */
                        private function __construct(array $ids)
                        {
                            $this->ids = $ids;
                        }

                        /**
                         * @psalm-param non-empty-list<string> $ids
                         */
                        public static function fromStrings(array $ids): self
                        {
                            return new self(array_map([CriterionId::class, "fromString"], $ids));
                        }
                    }'
            ],
            'offsetOnCallable' => [
                '<?php
                    function c(callable $c) : void {
                        if (is_array($c)) {
                            new ReflectionClass($c[0]);
                        }
                    }'
            ],
            'destructureCallableArray' => [
                '<?php
                    function getCallable(): callable {
                        return [DateTimeImmutable::class, "createFromFormat"];
                    }

                    $callable = getCallable();

                    if (!is_array($callable)) {
                      exit;
                    }

                    [$classOrObject, $method] = $callable;',
                [
                    '$classOrObject' => 'class-string|object',
                    '$method' => 'string'
                ]
            ],
            'callableInterface' => [
                '<?php
                    interface CallableInterface{
                        public function __invoke(): bool;
                    }

                    function takesInvokableInterface(CallableInterface $c): void{
                        takesCallable($c);
                    }

                    function takesCallable(callable $c): void {
                        $c();
                    }'
            ],
            'notCallableArrayNoUndefinedClass' => [
                '<?php
                    /**
                     * @psalm-param array|callable $_fields
                     */
                    function f($_fields): void {}

                    f(["instance_date" => "ASC", "start_time" => "ASC"]);'
            ],
            'callOnInvokableOrCallable' => [
                '<?php
                    interface Callback {
                        public function __invoke(): void;
                    }

                    /** @var Callback|callable */
                    $test = function (): void {};

                    $test();'
            ],
            'resolveTraitClosureReturn' => [
                '<?php
                    class B {
                        /**
                         * @psalm-param callable(mixed...):static $i
                         */
                        function takesACall(callable $i) : void {}

                        public function call() : void {
                            $this->takesACall(function() {return $this;});
                        }
                    }'
            ],
            'returnClosureReturningStatic' => [
                '<?php
                    /**
                     * @psalm-consistent-constructor
                     */
                    class C {
                        /**
                         * @return Closure():static
                         */
                        public static function foo() {
                            return function() {
                                return new static();
                            };
                        }
                    }',
            ],
            'returnsVoidAcceptableForNullable' => [
                '<?php
                    /** @param callable():?bool $c */
                    function takesCallable(callable $c) : void {}

                    takesCallable(function() { return; });',
            ],
            'byRefUsesAlwaysMixed' => [
                '<?php
                    $callback = function() use (&$isCalled) : void {
                        $isCalled = true;
                    };
                    $isCalled = false;
                    $callback();

                    if ($isCalled === true) {}'
            ],
            'notCallableListNoUndefinedClass' => [
                '<?php
                    /**
                     * @param array|callable $arg
                     */
                    function foo($arg): void {}

                    foo(["a", "b"]);'
            ],
            'abstractInvokeInTrait' => [
                '<?php
                    function testFunc(callable $func) : void {}

                    trait TestTrait {
                        abstract public function __invoke() : void;

                        public function apply() : void {
                            testFunc($this);
                        }
                    }

                    abstract class TestClass {
                        use TestTrait;
                    }'
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'undefinedCallableClass' => [
                '<?php
                    class A {
                        public function getFoo(): Foo
                        {
                            return new Foo([]);
                        }

                        /**
                         * @param  mixed $argOne
                         * @param  mixed $argTwo
                         * @return void
                         */
                        public function bar($argOne, $argTwo)
                        {
                            $this->getFoo()($argOne, $argTwo);
                        }
                    }',
                'error_message' => 'InvalidFunctionCall',
                'error_levels' => ['UndefinedClass', 'MixedInferredReturnType'],
            ],
            'undefinedCallableMethodFullString' => [
                '<?php
                    class A {
                        public static function bar(string $a): string {
                            return $a . "b";
                        }
                    }

                    function foo(callable $c): void {}

                    foo("A::barr");',
                'error_message' => 'UndefinedMethod',
            ],
            'undefinedCallableMethodClassConcat' => [
                '<?php
                    class A {
                        public static function bar(string $a): string {
                            return $a . "b";
                        }
                    }

                    function foo(callable $c): void {}

                    foo(A::class . "::barr");',
                'error_message' => 'UndefinedMethod',
            ],
            'undefinedCallableMethodArray' => [
                '<?php
                    class A {
                        public static function bar(string $a): string {
                            return $a . "b";
                        }
                    }

                    function foo(callable $c): void {}

                    foo([A::class, "::barr"]);',
                'error_message' => 'InvalidArgument',
            ],
            'undefinedCallableMethodArrayWithoutClass' => [
                '<?php
                    class A {
                        public static function bar(string $a): string {
                            return $a . "b";
                        }
                    }

                    function foo(callable $c): void {}

                    foo(["A", "::barr"]);',
                'error_message' => 'InvalidArgument',
            ],
            'undefinedCallableMethodClass' => [
                '<?php
                    class A {
                        public static function bar(string $a): string {
                            return $a . "b";
                        }
                    }

                    function foo(callable $c): void {}

                    foo("B::bar");',
                'error_message' => 'UndefinedClass',
            ],
            'undefinedCallableFunction' => [
                '<?php
                    function foo(callable $c): void {}

                    foo("trime");',
                'error_message' => 'UndefinedFunction',
            ],
            'stringFunctionCall' => [
                '<?php
                    $bad_one = "hello";
                    $a = $bad_one(1);',
                'error_message' => 'MixedAssignment',
            ],
            'wrongCallableReturnType' => [
                '<?php
                    $add_one = function(int $a): int {
                        return $a + 1;
                    };

                    /**
                     * @param callable(int) : int $c
                     */
                    function bar(callable $c) : string {
                        return $c(1);
                    }

                    bar($add_one);',
                'error_message' => 'InvalidReturnStatement',
            ],
            'checkCallableTypeString' => [
                '<?php
                    /**
                     * @param callable(int,int):int $_p
                     */
                    function f(callable $_p): void {}

                    f("strcmp");',
                'error_message' => 'InvalidScalarArgument',
            ],
            'checkCallableTypeArrayInstanceFirstArg' => [
                '<?php
                    /**
                     * @param callable(int,int):int $_p
                     */
                    function f(callable $_p): void {}

                    class C {
                        public static function m(string $a, string $b): int { return $a <=> $b; }
                    }

                    f([new C, "m"]);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'checkCallableTypeArrayClassStringFirstArg' => [
                '<?php
                    /**
                     * @param callable(int,int):int $_p
                     */
                    function f(callable $_p): void {}

                    class C {
                        public static function m(string $a, string $b): int { return $a <=> $b; }
                    }

                    f([C::class, "m"]);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'callableWithSpaceAfterColonBadVarArg' => [
                '<?php
                    class C {
                        /**
                         * @var callable(string, string): bool $p
                         */
                        public $p;

                        public function __construct() {
                            $this->p = function (string $s, string $t): stdClass {
                                return new stdClass;
                            };
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'callableWithSpaceBeforeColonBadVarArg' => [
                '<?php
                    class C {
                        /**
                         * @var callable(string, string) :bool $p
                         */
                        public $p;

                        public function __construct() {
                            $this->p = function (string $s, string $t): stdClass {
                                return new stdClass;
                            };
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'callableWithSpacesEitherSideOfColonBadVarArg' => [
                '<?php
                    class C {
                        /**
                         * @var callable(string, string) : bool $p
                         */
                        public $p;

                        public function __construct() {
                            $this->p = function (string $s, string $t): stdClass {
                                return new stdClass;
                            };
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'badArrayMapArrayCallable' => [
                '<?php
                    class one { public function two(string $_p): void {} }
                    array_map(["two", "three"], ["one", "two"]);',
                'error_message' => 'InvalidArgument',
            ],
            'noFatalErrorOnMissingClassWithSlash' => [
                '<?php
                    class Func {
                        public function __construct(string $name, callable $callable) {}
                    }

                    new Func("f", ["\Foo", "bar"]);',
                'error_message' => 'InvalidArgument'
            ],
            'noFatalErrorOnMissingClassWithoutSlash' => [
                '<?php
                    class Func {
                        public function __construct(string $name, callable $callable) {}
                    }

                    new Func("f", ["Foo", "bar"]);',
                'error_message' => 'InvalidArgument'
            ],
            'preventStringDocblockType' => [
                '<?php
                    /**
                     * @param string $mapper
                     */
                    function map2(callable $mapper): void {}

                    map2("foo");',
                'error_message' => 'MismatchingDocblockParamType',
            ],
            'moreSpecificCallable' => [
                '<?php
                    /** @param callable(string):void $c */
                    function takesSpecificCallable(callable $c) : void {
                        $c("foo");
                    }

                    function takesCallable(callable $c) : void {
                        takesSpecificCallable($c);
                    }',
                'error_message' => 'MixedArgumentTypeCoercion'
            ],
            'undefinedVarInBareCallable' => [
                '<?php
                    $fn = function(int $a): void{};
                    function a(callable $fn): void{
                      $fn(++$a);
                    }
                    a($fn);',
                'error_message' => 'UndefinedVariable',
            ],
            'dontQualifyStringCallables' => [
                '<?php
                    namespace NS;

                    function ff() : void {}

                    function run(callable $f) : void {
                        $f();
                    }

                    run("ff");',
                'error_message' => 'UndefinedFunction',
            ],
            'badCustomFunction' => [
                '<?php
                    /**
                     * @param callable(int):bool $func
                     */
                    function takesFunction(callable $func) : void {}

                    function myFunction( string $foo ) : bool {
                        return false;
                    }

                    takesFunction("myFunction");',
                'error_message' => 'InvalidScalarArgument',
            ],
            'emptyCallable' => [
                '<?php
                    $a = "";
                    $a();',
                'error_message' => 'InvalidFunctionCall',
            ],
            'ImpureFunctionCall' => [
                '<?php
                    /**
                     * @psalm-template T
                     *
                     * @psalm-param array<int, T> $values
                     * @psalm-param (callable(T): numeric) $num_func
                     *
                     * @psalm-return null|T
                     *
                     * @psalm-pure
                     */
                    function max_by(array $values, callable $num_func)
                    {
                        $max = null;
                        $max_num = null;
                        foreach ($values as $value) {
                            $value_num = $num_func($value);
                            if (null === $max_num || $value_num >= $max_num) {
                                $max = $value;
                                $max_num = $value_num;
                            }
                        }

                        return $max;
                    }

                    $c = max_by([1, 2, 3], static function(int $a): int {
                        return $a + mt_rand(0, $a);
                    });

                    echo $c;
                ',
                'error_message' => 'ImpureFunctionCall',
                'error_levels' => [],
            ],
            'constructCallableFromClassStringArray' => [
                '<?php
                    interface Foo {
                        public function bar() : int;
                    }

                    /**
                     * @param callable():string $c
                     */
                    function takesCallableReturningString(callable $c) : void {
                        $c();
                    }

                    /**
                     * @param class-string<Foo> $c
                     */
                    function foo(string $c) : void {
                        takesCallableReturningString([$c, "bar"]);
                    }',
                'error_message' => 'InvalidScalarArgument',
            ],
            'inexistantCallableinCallableString' => [
                '<?php
                    /**
                     * @param callable-string $c
                     */
                    function c(string $c): void {
                        $c();
                    }

                    c("hii");',
                'error_message' => 'InvalidArgument',
            ],
        ];
    }
}
