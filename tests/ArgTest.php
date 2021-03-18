<?php
namespace Psalm\Tests;

class ArgTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'argumentUnpackingLiteral' => [
                '<?php
                    function add(int $a, int $b, int $c) : int {
                        return $a + $b + $c;
                    }

                    echo add(1, ...[2, 3]);',
            ],
            'arrayPushArgumentUnpackingWithGoodArg' => [
                '<?php
                    $a = ["foo"];
                    $b = ["foo", "bar"];

                    array_push($a, ...$b);',
                'assertions' => [
                    '$a' => 'non-empty-list<string>',
                ],
            ],
            'arrayMergeArgumentUnpacking' => [
                '<?php
                    $a = [[1, 2]];
                    $b = array_merge([], ...$a);',
                'assertions' => [
                    '$b' => 'array{0: int, 1: int}',
                ],
            ],
            'preserveTypesWhenUnpacking' => [
                '<?php
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
                '<?php
                    function Foo(string $a, string ...$b) : void {}

                    /** @return array<int, string> */
                    function Baz(string ...$c) {
                        Foo(...$c);
                        return $c;
                    }',
            ],
            'unpackByRefArg' => [
                '<?php
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
                '<?php
                    class Hello {}
                    $m = new ReflectionMethod(Hello::class, "goodbye");
                    $m->invoke(null, "cool");',
            ],
            'sortFunctions' => [
                '<?php
                    $a = ["b" => 5, "a" => 8];
                    ksort($a);
                    $b = ["b" => 5, "a" => 8];
                    sort($b);
                ',
                'assertions' => [
                    '$a' => 'array{a: int, b: int}',
                    '$b' => 'list<int>',
                ],
            ],
            'arrayModificationFunctions' => [
                '<?php
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
                '<?php
                    $a = ["hello", "goodbye"];
                    shuffle($a);
                    $a = [0, 1];',
            ],
            'correctOrderValidation' => [
                '<?php
                    function getString(int $i) : string {
                        return rand(0, 1) ? "hello" : "";
                    }

                    function takesInt(int $i) : void {}

                    $i = rand(0, 10);

                    if (!($i = getString($i))) {}',
            ],
            'allowNullInObjectUnion' => [
                '<?php
                    /**
                     * @param string|null|object $b
                     */
                    function foo($b) : void {}
                    foo(null);',
            ],
            'allowArrayIntScalarForArrayStringWithScalarIgnored' => [
                '<?php
                    /** @param array<int|string> $arr */
                    function foo(array $arr) : void {
                    }

                    /** @return array<int, scalar> */
                    function bar() : array {
                      return [];
                    }

                    /** @psalm-suppress InvalidScalarArgument */
                    foo(bar());',
            ],
            'allowArrayScalarForArrayStringWithScalarIgnored' => [
                '<?php declare(strict_types=1);
                    /** @param array<string> $arr */
                    function foo(array $arr) : void {}

                    /** @return array<int, scalar> */
                    function bar() : array {
                        return [];
                    }

                    /** @psalm-suppress InvalidScalarArgument */
                    foo(bar());',
            ],
            'unpackObjectlikeListArgs' => [
                '<?php
                    $a = [new DateTime(), 1];
                    function f(DateTime $d, int $a): void {}
                    f(...$a);',
            ],
            'unpackWithoutAlteringArray' => [
                '<?php
                    function takeVariadicInts(int ...$inputs): void {}

                    $a = [3, 5, 7];
                    takeVariadicInts(...$a);',
                [
                    '$a' => 'non-empty-list<int>'
                ]
            ],
            'iterableSplat' => [
                '<?php
                    function foo(iterable $args): int {
                        return intval(...$args);
                    }

                    function bar(ArrayIterator $args): int {
                        return intval(...$args);
                    }',
            ],
            'unpackListWithOptional' => [
                '<?php
                    function foo(string ...$rest):void {}

                    $rest = ["zzz"];

                    if (rand(0,1)) {
                        $rest[] = "xxx";
                    }

                    foo("first", ...$rest);'
            ],
            'useNamedArguments' => [
                '<?php
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
                    }'
            ],
            'useNamedArgumentsSimple' => [
                '<?php
                    function takesArguments(string $name, int $age) : void {}

                    takesArguments(name: "hello", age: 5);
                    takesArguments(age: 5, name: "hello");'
            ],
            'useNamedArgumentsSpread' => [
                '<?php
                    function takesArguments(string $name, int $age) : void {}

                    $args = ["name" => "hello", "age" => 5];
                    takesArguments(...$args);',
                [],
                [],
                '8.0'
            ],
            'useNamedVariadicArguments' => [
                '<?php
                    function takesArguments(int ...$args) : void {}

                    takesArguments(age: 5);',
                [],
                [],
                '8.0'
            ],
            'variadicArgsOptional' => [
                '<?php
                    bar(...["aaaaa"]);
                    function bar(string $p1, int $p3 = 10) : void {}'
            ],
            'mkdirNamedParameters' => [
                '<?php declare(strict_types=1);
                    mkdir("/var/test/123", recursive: true);',
                [],
                [],
                '8.0'
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'arrayPushArgumentUnpackingWithBadArg' => [
                '<?php
                    $a = [];
                    $b = "hello";

                    $a[] = "foo";

                    array_push($a, ...$b);',
                'error_message' => 'InvalidArgument',
            ],
            'possiblyInvalidArgument' => [
                '<?php
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
                '<?php
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
                '<?php declare(strict_types=1);
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
                '<?php
                    /**
                     * @param mixed|null $mixed_or_null
                     */
                    function foo($mixed, $mixed_or_null): void {
                        /**
                         * @psalm-suppress MixedArgument
                         */
                        new Exception($mixed_or_null);
                    }',
                'error_message' => 'PossiblyNullArgument'
            ],
            'useInvalidNamedArgument' => [
                '<?php
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
                'error_message' => 'InvalidNamedArgument'
            ],
            'noNamedArgsMethod' => [
                '<?php
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
                'error_message' => 'InvalidScalarArgument'
            ],
            'noNamedArgsFunction' => [
                '<?php
                    /** @no-named-arguments */
                    function takesArguments(string $name, int $age) : void {}

                    takesArguments(age: 5, name: "hello");',
                'error_message' => 'InvalidScalarArgument'
            ],
            'arrayWithoutAllNamedParameters' => [
                '<?php
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
                [],
                false,
                '8.0'
            ],
            'arrayWithoutAllNamedParametersSuppressMixed' => [
                '<?php
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
                [],
                false,
                '8.0'
            ],
            'wrongTypeVariadicArguments' => [
                '<?php
                    function takesArguments(int ...$args) : void {}

                    takesArguments(age: "abc");',
                'error_message' => 'InvalidScalarArgument',
                [],
                false,
                '8.0'
            ],
            'byrefVarSetsPossible' => [
                '<?php
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
                '<?php
                    function test(int $param, int $param2): void {
                        echo $param + $param2;
                    }

                    test(param: 1, param: 2);',
                'error_message' => 'InvalidNamedArgument',
                [],
                false,
                '8.0'
            ],
            'overwriteOrderedNamedParam' => [
                '<?php
                    function test(int $param, int $param2): void {
                        echo $param + $param2;
                    }

                    test(1, param: 2);',
                'error_message' => 'InvalidNamedArgument',
                [],
                false,
                '8.0'
            ],
        ];
    }
}
