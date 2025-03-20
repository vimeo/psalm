<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class ArgTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    #[Override]
    public function providerValidCodeParse(): iterable
    {
        return [
            'argumentUnpackingLiteral' => [
                'code' => '<?php
                    function add(int $a, int $b, int $c) : int {
                        return $a + $b + $c;
                    }

                    echo add(1, ...[2, 3]);',
            ],
            'arrayPushArgumentUnpackingWithGoodArg' => [
                'code' => '<?php
                    $a = ["foo"];
                    $b = ["foo", "bar"];

                    array_push($a, ...$b);',
                'assertions' => [
                    '$a' => 'non-empty-list<string>',
                ],
            ],
            'arrayMergeArgumentUnpacking' => [
                'code' => '<?php
                    $a = [[1, 2]];
                    $b = array_merge([], ...$a);',
                'assertions' => [
                    '$b===' => 'list{1, 2}',
                ],
            ],
            'preserveTypesWhenUnpacking' => [
                'code' => '<?php
                    /**
                     * @return array<int,array<int,string>>
                     */
                    function getData(): array
                    {
                        return [
                            ["a", "b"],
                            ["c", "d"]
                        ];
                    }

                    /**
                     * @return array<int,string>
                     */
                    function f1(): array
                    {
                        $data = getData();
                        return array_merge($data[0], $data[1]);
                    }

                    /**
                     * @return array<int,string>
                     */
                    function f2(): array
                    {
                        $data = getData();
                        return array_merge(...$data);
                    }

                    /**
                     * @return array<int,string>
                     */
                    function f3(): array
                    {
                        $data = getData();
                        return array_merge([], ...$data);
                    }',
            ],
            'unpackArg' => [
                'code' => '<?php
                    function Foo(string $a, string ...$b) : void {}

                    /** @return array<array-key, string> */
                    function Baz(string ...$c) {
                        Foo(...$c);
                        return $c;
                    }',
            ],
            'unpackByRefArg' => [
                'code' => '<?php
                    function example (int &...$x): void {}
                    $y = 0;
                    example($y);
                    $z = [0];
                    example(...$z);',
                'assertions' => [
                    '$y' => 'int',
                    '$z' => 'array<int, int>',
                ],
            ],
            'callMapClassOptionalArg' => [
                'code' => '<?php
                    class Hello {}
                    $m = new ReflectionMethod(Hello::class, "goodbye");
                    $m->invoke(null, "cool");',
            ],
            'sortFunctions' => [
                'code' => '<?php
                    $a = ["b" => 5, "a" => 8];
                    ksort($a);
                    $b = ["b" => 5, "a" => 8];
                    sort($b);
                    $c = [];
                    sort($c);
                ',
                'assertions' => [
                    '$a' => 'array{a: int, b: int}',
                    '$b' => 'non-empty-list<int>',
                    '$c' => 'array<never, never>',
                ],
            ],
            'arrayModificationFunctions' => [
                'code' => '<?php
                    $a = ["b" => 5, "a" => 8];
                    array_unshift($a, (bool)rand(0, 1));
                    $b = ["b" => 5, "a" => 8];
                    array_push($b, (bool)rand(0, 1));
                ',
                'assertions' => [
                    '$a' => 'non-empty-array<int|string, bool|int>',
                    '$b' => 'non-empty-array<int|string, bool|int>',
                ],
            ],
            'byRefArgAssignment' => [
                'code' => '<?php
                    $a = ["hello", "goodbye"];
                    shuffle($a);
                    $a = [0, 1];',
            ],
            'correctOrderValidation' => [
                'code' => '<?php
                    function getString(int $i) : string {
                        return rand(0, 1) ? "hello" : "";
                    }

                    function takesInt(int $i) : void {}

                    $i = rand(0, 10);

                    if (!($i = getString($i))) {}',
            ],
            'allowNullInObjectUnion' => [
                'code' => '<?php
                    /**
                     * @param string|null|object $b
                     */
                    function foo($b) : void {}
                    foo(null);',
            ],
            'allowArrayIntScalarForArrayStringWithArgumentTypeCoercionIgnored' => [
                'code' => '<?php
                    /** @param array<array-key> $arr */
                    function foo(array $arr) : void {
                    }

                    /** @return array<int, scalar> */
                    function bar() : array {
                      return [];
                    }

                    /** @psalm-suppress ArgumentTypeCoercion */
                    foo(bar());',
            ],
            'allowArrayScalarForArrayStringWithArgumentTypeCoercionIgnored' => [
                'code' => '<?php declare(strict_types=1);
                    /** @param array<string> $arr */
                    function foo(array $arr) : void {}

                    /** @return array<int, scalar> */
                    function bar() : array {
                        return [];
                    }

                    /** @psalm-suppress ArgumentTypeCoercion */
                    foo(bar());',
            ],
            'unpackObjectlikeListArgs' => [
                'code' => '<?php
                    $a = [new DateTime(), 1];
                    function f(DateTime $d, int $a): void {}
                    f(...$a);',
            ],
            'unpackWithoutAlteringArray' => [
                'code' => '<?php
                    function takeVariadicInts(int ...$inputs): void {}

                    $a = [3, 5, 7];
                    takeVariadicInts(...$a);',
                'assertions' => [
                    '$a' => 'non-empty-list<int>',
                ],
            ],
            'iterableSplat' => [
                'code' => '<?php
                    /** @param iterable<int, mixed> $args */
                    function foo(iterable $args): int {
                        return intval(...$args);
                    }

                    /** @param ArrayIterator<int, mixed> $args */
                    function bar(ArrayIterator $args): int {
                        return intval(...$args);
                    }',
            ],
            'unpackListWithOptional' => [
                'code' => '<?php
                    function foo(string ...$rest):void {}

                    $rest = ["zzz"];

                    if (rand(0,1)) {
                        $rest[] = "xxx";
                    }

                    foo("first", ...$rest);',
            ],
            'useNamedArguments' => [
                'code' => '<?php
                    class CustomerData {
                        public function __construct(
                            public string $name,
                            public string $email,
                            public int $age,
                        ) {}
                    }

                    /**
                     * @param array{age: int, name: string, email: string} $input
                     */
                    function foo(array $input) : CustomerData {
                        return new CustomerData(
                            age: $input["age"],
                            name: $input["name"],
                            email: $input["email"],
                        );
                    }',
            ],
            'useNamedArgumentsSimple' => [
                'code' => '<?php
                    function takesArguments(string $name, int $age) : void {}

                    takesArguments(name: "hello", age: 5);
                    takesArguments(age: 5, name: "hello");',
            ],
            'useNamedArgumentsSpread' => [
                'code' => '<?php
                    function takesArguments(string $name, int $age) : void {}

                    $args = ["name" => "hello", "age" => 5];
                    takesArguments(...$args);',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'useNamedVariadicArguments' => [
                'code' => '<?php
                    function takesArguments(int ...$args) : void {}

                    takesArguments(age: 5);',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'useUnpackedNamedVariadicArguments' => [
                'code' => '<?php
                    function takesArguments(int ...$args) : void {}

                    takesArguments(...["age" => 5]);',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'variadicArgsOptional' => [
                'code' => '<?php
                    bar(...["aaaaa"]);
                    function bar(string $p1, int $p3 = 10) : void {}',
            ],
            'mkdirNamedParameters' => [
                'code' => '<?php declare(strict_types=1);
                    mkdir("/var/test/123", recursive: true);',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'variadicArgumentWithNoNamedArgumentsIsList' => [
                'code' => '<?php
                    class A {
                        /**
                         * @no-named-arguments
                         * @psalm-return list<int>
                         */
                        public function foo(int ...$values): array
                        {
                            return $values;
                        }
                    }
                ',
            ],
            'SealedAcceptSealed' => [
                'code' => '<?php
                    /** @param array{test: string} $a */
                    function a(array $a): string {
                        return $a["test"];
                    }

                    $sealed = ["test" => "str"];
                    a($sealed);
                ',
            ],
            'variadicCallbackArgsCountMatch' => [
                'code' => '<?php
                /**
                 * @param callable(string, string):void $callback
                 * @return void
                 */
                function caller($callback) {}

                /**
                 * @param string ...$bar
                 * @return void
                 */
                function foo(...$bar) {}

                caller("foo");',
            ],
            'variadicCallableArgsCountMatch' => [
                'code' => '<?php
                    /**
                     * @param callable(string, ...int):void $callback
                     * @return void
                     */
                    function var_caller($callback) {}

                    /**
                     * @param string $a
                     * @param int ...$b
                     * @return void
                     */
                    function foo($a, ...$b) {}

                    var_caller("foo");',
            ],
            'mixedNullable' => [
                'code' => '<?php
                    class A {
                        public function __construct(public mixed $default = null) {
                        }
                    }
                    $a = new A;
                    $_v = $a->default;',
                'assertions' => [
                    '$_v===' => 'mixed',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
        ];
    }

    #[Override]
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'arrayPushArgumentUnpackingWithBadArg' => [
                'code' => '<?php
                    $a = [];
                    $b = "hello";

                    $a[] = "foo";

                    array_push($a, ...$b);',
                'error_message' => 'InvalidArgument',
            ],
            'possiblyInvalidArgument' => [
                'code' => '<?php
                    $foo = [
                        "a",
                        ["b"],
                    ];

                    $a = array_map(
                        function (string $uuid): string {
                            return $uuid;
                        },
                        $foo[rand(0, 1)]
                    );',
                'error_message' => 'PossiblyInvalidArgument',
            ],
            'possiblyInvalidArgumentWithOverlap' => [
                'code' => '<?php
                    class A {}
                    class B {}
                    class C {}

                    $foo = rand(0, 1) ? new A : new B;

                    /** @param B|C $b */
                    function bar($b) : void {}

                    bar($foo);',
                'error_message' => 'PossiblyInvalidArgument',
            ],
            'possiblyInvalidArgumentWithMixed' => [
                'code' => '<?php declare(strict_types=1);
                    /**
                     * @psalm-suppress MissingParamType
                     * @psalm-suppress MixedArgument
                     */
                    function foo($a) : void {
                        if (rand(0, 1)) {
                            $a = 0;
                        }

                        echo strlen($a);
                    }',
                'error_message' => 'PossiblyInvalidArgument',
            ],
            'expectsNonNullAndPassedPossiblyNull' => [
                'code' => '<?php
                    /**
                     * @param mixed|null $mixed_or_null
                     */
                    function foo($mixed, $mixed_or_null): void {
                        /**
                         * @psalm-suppress MixedArgument
                         */
                        new Exception($mixed_or_null);
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'useInvalidNamedArgument' => [
                'code' => '<?php
                    class CustomerData {
                        public function __construct(
                            public string $name,
                            public string $email,
                            public int $age,
                        ) {}
                    }

                    /**
                     * @param array{age: int, name: string, email: string} $input
                     */
                    function foo(array $input) : CustomerData {
                        return new CustomerData(
                            aage: $input["age"],
                            name: $input["name"],
                            email: $input["email"],
                        );
                    }',
                'error_message' => 'InvalidNamedArgument',
            ],
            'usePositionalArgAfterNamed' => [
                'code' => '<?php
                    final class Person
                    {
                        public function __construct(
                            public string $name,
                            public int $age,
                        ) { }
                    }

                    new Person(name: "", 0);',
                'error_message' => 'InvalidNamedArgument',
            ],
            'useUnpackedInvalidNamedArgument' => [
                'code' => '<?php
                    class CustomerData {
                        public function __construct(
                            public string $name,
                            public string $email,
                            public int $age,
                        ) {}
                    }

                    /**
                     * @param array{aage: int, name: string, email: string} $input
                     */
                    function foo(array $input) : CustomerData {
                        return new CustomerData(...$input);
                    }',
                'error_message' => 'InvalidNamedArgument',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'noNamedArgsMethod' => [
                'code' => '<?php
                    class CustomerData
                    {
                        /** @no-named-arguments */
                        public function __construct(
                            public string $name,
                            public string $email,
                            public int $age,
                        ) {}
                    }

                    /**
                     * @param array{age: int, name: string, email: string} $input
                     */
                    function foo(array $input) : CustomerData {
                        return new CustomerData(
                            age: $input["age"],
                            name: $input["name"],
                            email: $input["email"],
                        );
                    }',
                'error_message' => 'NamedArgumentNotAllowed',
            ],
            'noNamedArgsFunction' => [
                'code' => '<?php
                    /** @no-named-arguments */
                    function takesArguments(string $name, int $age) : void {}

                    takesArguments(age: 5, name: "hello");',
                'error_message' => 'NamedArgumentNotAllowed',
            ],
            'arrayWithoutAllNamedParameters' => [
                'code' => '<?php
                    class User {
                        public function __construct(
                            public int $id,
                            public string $name,
                            public int $age
                        ) {}
                    }

                    /**
                     * @param array{id: int, name: string} $data
                     */
                    function processUserDataInvalid(array $data) : User {
                        return new User(...$data);
                    }',
                'error_message' => 'MixedArgument',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'arrayWithoutAllNamedParametersSuppressMixed' => [
                'code' => '<?php
                    class User {
                        public function __construct(
                            public int $id,
                            public string $name,
                            public int $age
                        ) {}
                    }

                    /**
                     * @param array{id: int, name: string} $data
                     */
                    function processUserDataInvalid(array $data) : User {
                        /** @psalm-suppress MixedArgument */
                        return new User(...$data);
                    }',
                'error_message' => 'TooFewArguments',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'wrongTypeVariadicArguments' => [
                'code' => '<?php
                    function takesArguments(int ...$args) : void {}

                    takesArguments(age: "abc");',
                'error_message' => 'InvalidScalarArgument',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'byrefVarSetsPossible' => [
                'code' => '<?php
                    /**
                     * @param mixed $a
                     * @psalm-param-out int $a
                     */
                    function takesByRef(&$a) : void {
                        $a = 5;
                    }

                    if (rand(0, 1)) {
                        takesByRef($b);
                    }

                    echo $b;',
                'error_message' => 'PossiblyUndefinedGlobalVariable',
            ],
            'overwriteNamedParam' => [
                'code' => '<?php
                    function test(int $param, int $param2): void {
                        echo $param + $param2;
                    }

                    test(param: 1, param: 2);',
                'error_message' => 'InvalidNamedArgument',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'overwriteOrderedNamedParam' => [
                'code' => '<?php
                    function test(int $param, int $param2): void {
                        echo $param + $param2;
                    }

                    test(1, param: 2);',
                'error_message' => 'InvalidNamedArgument',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'overwriteOrderedWithUnpackedNamedParam' => [
                'code' => '<?php
                    function test(int $param, int $param2): void {
                        echo $param + $param2;
                    }

                    test(1, ...["param" => 2]);',
                'error_message' => 'InvalidNamedArgument',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'variadicArgumentIsNotList' => [
                'code' => '<?php
                    /** @psalm-return list<int> */
                    function foo(int ...$values): array
                    {
                        return $values;
                    }
                ',
                'error_message' => 'LessSpecificReturnStatement',
            ],
            'preventUnpackingPossiblyIterable' => [
                'code' => '<?php
                    function foo(int $arg1, int $arg2): void {}

                    /** @var iterable<int, int>|object */
                    $test = [1, 2];
                    foo(...$test);
                ',
                'error_message' => 'PossiblyInvalidArgument',
            ],
            'SKIPPED-preventUnpackingPossiblyArray' => [
                'code' => '<?php
                    function foo(int $arg1, int $arg2): void {}

                    /** @var array<int, int>|object */
                    $test = [1, 2];
                    foo(...$test);
                ',
                'error_message' => 'PossiblyInvalidArgument',
            ],
            'noNamedArguments' => [
                'code' => '<?php
                    /**
                     * @psalm-suppress UnusedParam
                     * @no-named-arguments
                     */
                    function foo(int $arg1, int $arg2): void {}

                    foo(arg2: 0, arg1: 1);
                ',
                'error_message' => 'NamedArgumentNotAllowed',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'noNamedArgumentsUnpackIterable' => [
                'code' => '<?php
                    /**
                     * @psalm-suppress UnusedParam
                     * @no-named-arguments
                     */
                    function foo(int $arg1, int $arg2): void {}

                    /** @var iterable<string, int> */
                    $test = ["arg1" => 1, "arg2" => 2];
                    foo(...$test);
                ',
                'error_message' => 'NamedArgumentNotAllowed',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'variadicArgumentWithNoNamedArgumentsPreventsPassingArrayWithStringKey' => [
                'code' => '<?php
                    /**
                     * @no-named-arguments
                     * @psalm-return list<int>
                     */
                    function foo(int ...$values): array
                    {
                        return $values;
                    }

                    foo(...["a" => 0]);
                ',
                'error_message' => 'NamedArgumentNotAllowed',
            ],
            'unpackNonArrayKeyIterable' => [
                'code' => '<?php
                    /** @psalm-suppress UnusedParam */
                    function foo(string ...$args): void {}

                    /** @var Iterator<float, string> */
                    $test = null;
                    foo(...$test);
                ',
                'error_message' => 'InvalidArgument',
            ],
            'numericStringIsNotNonFalsy' => [
                'code' => '<?php
                    /** @param non-falsy-string $arg */
                    function foo(string $arg): string
                    {
                        return $arg;
                    }

                    /** @return numeric-string */
                    function bar(): string
                    {
                        return "0";
                    }

                    foo(bar());
                ',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'objectIsNotObjectWithProperties' => [
                'code' => '<?php

                    function makeObj(): object {
                        return (object)["a" => 42];
                    }

                    /** @param object{hmm:float} $_o */
                    function takesObject($_o): void {}

                    takesObject(makeObj()); // expected: ArgumentTypeCoercion
                ',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'objectRedundantCast' => [
                'code' => '<?php

                    function makeObj(): object {
                        return (object)["a" => 42];
                    }

                    function takesObject(object $_o): void {}

                    takesObject((object)makeObj()); // expected: RedundantCast
                ',
                'error_message' => 'RedundantCast',
            ],
            'MissingMandatoryParamWithNamedParams' => [
                'code' => '<?php
                class User
                {
                    public function __construct(
                        protected string $name,
                        protected string $problematicOne,
                        protected string $id = "",
                    ){}
                }

                new User(
                name: "John",
                id: "asd",
                );
                ',
                'error_message' => 'TooFewArguments',
            ],
            'SealedRefuseUnsealed' => [
                'code' => '<?php
                    /** @param array{test: string} $a */
                    function a(array $a): string {
                        return $a["test"];
                    }

                    /** @var array{test: string, ...} */
                    $unsealed = [];
                    a($unsealed);
                ',
                'error_message' => 'InvalidArgument',
            ],
            'SealedRefuseSealedExtra' => [
                'code' => '<?php
                    /** @param array{test: string} $a */
                    function a(array $a): string {
                        return $a["test"];
                    }

                    $sealedExtraKeys = ["test" => "str", "somethingElse" => "test"];
                    a($sealedExtraKeys);
                ',
                'error_message' => 'InvalidArgument - src'  . DIRECTORY_SEPARATOR . 'somefile.php:8:23 - Argument 1 of a expects array{test: string}, but array{somethingElse: \'test\', test: \'str\'} with additional array shape fields (somethingElse) was provided',
            ],
            'callbackArgsCountMismatch' => [
                'code' => '<?php
                    /**
                     * @param callable(string, string):void $callback
                     * @return void
                     */
                    function caller($callback) {}

                    /**
                     * @param string $a
                     * @return void
                     */
                    function foo($a) {}

                    caller("foo");',
                'error_message' => 'PossiblyInvalidArgument',
            ],
            'callableArgsCountMismatch' => [
                'code' => '<?php
                    /**
                     * @param callable(string):void $callback
                     * @return void
                     */
                    function caller($callback) {}

                    /**
                     * @param string $a
                     * @param string $b
                     * @return void
                     */
                    function foo($a, $b) {}

                    caller("foo");',
                'error_message' => 'InvalidArgument',
            ],
        ];
    }
}
