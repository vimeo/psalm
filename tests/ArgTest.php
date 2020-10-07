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
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
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
        ];
    }
}
