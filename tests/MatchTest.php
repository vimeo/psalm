<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class MatchTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'switchTruthy' => [
                'code' => '<?php
                    class A {
                       public ?string $a = null;
                       public ?string $b = null;
                    }

                    function f(A $obj): string {
                        return match (true) {
                            $obj->a !== null => $obj->a,
                            $obj->b !== null => $obj->b,
                            default => throw new \InvalidArgumentException("$obj->a or $obj->b must be set"),
                        };
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'defaultAboveCase' => [
                'code' => '<?php
                    function foo(string $a) : string {
                        return match ($a) {
                            "a" => "hello",
                            default => "yellow",
                            "b" => "goodbye",
                        };
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'allMatchedNoRedundantCondition' => [
                'code' => '<?php
                    function foo() : string {
                        $a = rand(0, 1) ? "a" : "b";
                        return match ($a) {
                            "a" => "hello",
                            "b" => "goodbye",
                        };
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'getClassWithMethod' => [
                'code' => '<?php
                    interface Foo {}

                    class Bar implements Foo
                    {
                        public function hello(): string
                        {
                            return "a";
                        }
                    }

                    function foo(Foo $value): string {
                        return match (get_class($value)) {
                            Bar::class => $value->hello(),
                            default => "b",
                        };
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'MatchWithCount' => [
                'code' => '<?php
                    /**
                     * @return non-empty-array
                     */
                    function test(array $array): array
                    {
                        return match (\count($array)) {
                            0 => throw new \InvalidArgumentException,
                            default => $array,
                        };
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'multipleIdenticalChecksInOneArm' => [
                'code' => '<?php
                    function foo(?string $t1, string $t2): string
                    {
                        return match ($t1 ?? $t2) {
                            "type1", "type2", "type3" => "1 or 2 or 3",
                            "type4", "type5", "type6" => "4 or 5 or 6",
                            default => "rest",
                        };
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'multipleInstanceOfConditionsInOneArm' => [
                'code' => '<?php
                    interface Foo {}
                    class A implements Foo {}
                    class B implements Foo {}
                    class C {}

                    function baz(A|B $_): int {
                        return 1;
                    }

                    function bar(Foo $foo): int {
                        return match (true) {
                            $foo instanceof A, $foo instanceof B => baz($foo),
                            $foo instanceof C => 3,
                            default => 0,
                        };
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'multipleTypeCheckConditionsInOneArm' => [
                'code' => '<?php
                    function baz(int|string $_): int {
                        return 1;
                    }

                    function bar(mixed $foo): int {
                        return match (true) {
                            is_string($foo), is_int($foo) => baz($foo),
                            default => 0,
                        };
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'matchOnConstClassFetch' => [
                'code' => '<?php
                    final class Obj1 {
                        public string $propFromObj1 = "str";
                    }
                    final class Obj2 {
                        public int $propFromObj2 = 42;
                    }

                    function process(Obj1|Obj2 $obj): int|string
                    {
                        return match ($obj::class) {
                            Obj1::class => $obj->propFromObj1,
                            Obj2::class => $obj->propFromObj2,
                        };
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'getClassArgWrongClass' => [
                'code' => '<?php
                    class A {}

                    class B {}

                    $a = rand(0, 10) ? new A() : new B();

                    $a = match (get_class($a)) {
                        A::class => $a->barBar(),
                    };',
                'error_message' => 'UndefinedMethod',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'getClassMissingClass' => [
                'code' => '<?php
                    class A {}
                    class B {}

                    $a = rand(0, 10) ? new A() : new B();

                    $a = match (get_class($a)) {
                        C::class => 5,
                    };',
                'error_message' => 'UndefinedClass',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'allMatchedDefaultImpossible' => [
                'code' => '<?php
                    function foo() : string {
                        $a = rand(0, 1) ? "a" : "b";
                        return match ($a) {
                            "a" => "hello",
                            "b" => "goodbye",
                            default => "impossible",
                        };
                    }',
                'error_message' => 'TypeDoesNotContainType',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'allMatchedAnotherImpossible' => [
                'code' => '<?php
                    function foo() : string {
                        $a = rand(0, 1) ? "a" : "b";
                        return match ($a) {
                            "a" => "hello",
                            "b" => "goodbye",
                            "c" => "impossible",
                        };
                    }',
                'error_message' => 'TypeDoesNotContainType',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'notAllEnumsMet' => [
                'code' => '<?php
                    /**
                     * @param "foo"|"bar" $foo
                     */
                    function foo(string $foo): string {
                        return match ($foo) {
                            "foo" => "foo",
                        };
                    }',
                'error_message' => 'UnhandledMatchCondition',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'notAllConstEnumsMet' => [
                'code' => '<?php
                    class Airport {
                        const JFK = "jfk";
                        const LHR = "lhr";
                        const LGA = "lga";

                        /**
                         * @param self::* $airport
                         */
                        public static function getName(string $airport): string {
                            return match ($airport) {
                                self::JFK => "John F Kennedy Airport",
                                self::LHR => "London Heathrow",
                            };
                        }
                    }',
                'error_message' => 'UnhandledMatchCondition',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'paradoxWithDuplicateValue' => [
                'code' => '<?php
                    function foo(int $i) : void {
                        echo match ($i) {
                            1 => 0,
                            1 => 1,
                        };
                    };',
                'error_message' => 'ParadoxicalCondition',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'noCrashWithEmptyMatch' => [
                'code' => '<?php
                    function foo(int $i) {
                        match ($i) {

                        };
                    }',
                'error_message' => 'UnhandledMatchCondition',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'exitIsLikeThrow' => [
                'code' => '<?php
                    /**
                     * @param 1|2|3 $i
                     */
                    function foo(int $i): void {
                        $a = match ($i) {
                            1 => exit(),
                            2, 3 => $i,
                        };
                        $a === "aaa";
                    }',
                'error_message' => 'DocblockTypeContradiction',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'matchTrueImpossible' => [
                'code' => '<?php
                    $foo = new \stdClass();
                    $a = match (true) {
                        $foo instanceof \stdClass => 1,
                        $foo instanceof \Exception => 1,
                    };',
                'error_message' => 'TypeDoesNotContainType',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'multipleInstanceOfConditionsNotMetInOneArm' => [
                'code' => '<?php
                    interface Foo {}
                    class A implements Foo {}
                    class B implements Foo {}
                    class C {}

                    function baz(C $_): int {
                        return 1;
                    }

                    function bar(Foo $foo): int {
                        return match (true) {
                            $foo instanceof A, $foo instanceof B => baz($foo),
                            $foo instanceof C => 3,
                            default => 0,
                        };
                    }',
                'error_message' => 'InvalidArgument',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
        ];
    }
}
