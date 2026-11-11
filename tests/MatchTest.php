<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function array_map;
use function implode;
use function range;

use const DIRECTORY_SEPARATOR;

final class MatchTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'exhaustiveMatchOverAnEnumWithMoreCasesThanTheMaximumShapedArraySize' => [
                'code' => '<?php
                    enum Id: string {
                        ' . implode("\n", array_map(static fn(int $i): string => "case ID_$i = \"$i\";", range(1, 105))) . '
                    }

                    function label(Id $id): string {
                        return match ($id) {
                            ' . implode("\n", array_map(static fn(int $i): string => "Id::ID_$i => \"label $i\",", range(1, 105))) . '
                        };
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'matchAssignsVarInEveryArm' => [
                'code' => '<?php
                    $case = rand(0, 2);

                    match ($case) {
                        0 => $o = 1,
                        1 => $o = 2,
                        default => $o = 3,
                    };

                    $r = $o;',
                'assertions' => [
                    '$r===' => '1|2|3',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'matchAssignsVarWithThrowingDefault' => [
                'code' => '<?php
                    $case = rand(0, 2);

                    match ($case) {
                        0 => $o = 1,
                        1 => $o = 2,
                        default => throw new RuntimeException("x"),
                    };

                    $r = $o;',
                'assertions' => [
                    '$r===' => '1|2',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'nestedTernaryWithAThrowingBranchAssignsVarInTheParent' => [
                'code' => '<?php
                    function pick(bool $a, bool $b): int {
                        $a ? ($value = 1) : ($b ? ($value = 2) : throw new RuntimeException("none"));

                        return $value;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'nestedTernaryAssignsVarInEveryBranch' => [
                'code' => '<?php
                    $c = (bool) rand(0, 1);
                    $d = (bool) rand(0, 1);

                    $c ? $o = 1 : ($d ? $o = 2 : $o = 3);

                    $r = $o;',
                'assertions' => [
                    '$r===' => '1|2|3',
                ],
            ],
            'ternaryAssignsVarWithThrowingBranch' => [
                'code' => '<?php
                    $c = (bool) rand(0, 1);

                    $c ? $o = 1 : throw new RuntimeException("x");

                    $r = $o;',
                'assertions' => [
                    '$r===' => '1',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'subjectIsOneOfTheArmValuesAfterAMatchWithoutDefault' => [
                'code' => '<?php
                    function f(string $type): int {
                        $id = match ($type) {
                            "organization" => 1,
                            "prof", "masterprice" => 2,
                        };

                        return $id;
                    }

                    function g(string $type): string {
                        $id = match ($type) {
                            "organization" => 1,
                            "prof" => 2,
                        };

                        return $type;
                    }

                    function getOwnerType(): string {
                        return "";
                    }

                    $type = getOwnerType();
                    match ($type) {
                        "organization" => 1,
                        "prof", "masterprice" => 2,
                    };
                    $kept = $type;

                    $other = getOwnerType();
                    match ($other) {
                        "organization" => 1,
                        default => 2,
                    };
                    $unchanged = $other;',
                'assertions' => [
                    '$kept===' => "'masterprice'|'organization'|'prof'",
                    '$unchanged===' => 'string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
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
            'nullCoalesce' => [
                'code' => <<<'PHP'
                    <?php
                    function foo(): bool { return false; }
                    $match = match (foo()) {
                        false => null,
                        true => 1,
                    } ?? 2;
                    PHP,
                'assertions' => [
                    '$match' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'matchTrueArmWithSeveralMethodCalls' => [
                'code' => '<?php
                    interface Environment {
                        public function isProduction(): bool;
                        public function isBackup(): bool;
                        public function isAcceptance(): bool;
                    }

                    function baseUrl(Environment $environment): string {
                        return match (true) {
                            $environment->isProduction(),
                            $environment->isBackup(),
                            $environment->isAcceptance() => "https://x.example",
                            default => "https://y.example",
                        };
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'firstClassCallableInMatchSubject' => [
                'code' => <<<'PHP'
                    <?php
                    echo match (count(...)) { default => 0 };
                    echo match (gettype(...)) { default => 0 };
                    echo match (get_class(...)) { default => 0 };
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return [
            'matchOverAnEnumWithMoreCasesThanTheMaximumShapedArraySizeMissingOne' => [
                'code' => '<?php
                    enum Id: string {
                        ' . implode("\n", array_map(static fn(int $i): string => "case ID_$i = \"$i\";", range(1, 105))) . '
                    }

                    function label(Id $id): string {
                        return match ($id) {
                            ' . implode("\n", array_map(static fn(int $i): string => "Id::ID_$i => \"label $i\",", range(1, 104))) . '
                        };
                    }',
                'error_message' => 'values enum(Id::ID_105',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'nestedTernaryAssignsVarInOnlySomeBranches' => [
                'code' => '<?php
                    $c = (bool) rand(0, 1);
                    $d = (bool) rand(0, 1);

                    $c ? $o = 1 : ($d ? $o = 2 : 3);

                    echo $o;',
                'error_message' => 'PossiblyUndefinedGlobalVariable',
            ],
            'matchAssignsVarInOnlySomeArms' => [
                'code' => '<?php
                    $case = rand(0, 2);

                    match ($case) {
                        0 => $o = 1,
                        1 => 2,
                        default => $o = 3,
                    };

                    echo $o;',
                'error_message' => 'PossiblyUndefinedGlobalVariable',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'getClassArgWrongClass' => [
                'code' => '<?php
                    class A {}

                    class B {}

                    $a = rand(0, 10) ? new A() : new B();

                    $a = match (get_class($a)) {
                        A::class => $a->barBar(),
                    };',
                'error_message' => 'UndefinedMethod',
                'error_levels' => [],
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
                'error_levels' => [],
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
                'error_levels' => [],
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
                'error_levels' => [],
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
                'error_levels' => [],
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
                'error_levels' => [],
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
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'matchArmConditionIssueIsLocatedAtTheCondition' => [
                'code' => '<?php
                    function f(int $i): int {
                        return match ($i) {
                            1 => 1,
                            2, "b" => 2,
                            default => 3,
                        };
                    }',
                'error_message' => 'TypeDoesNotContainType - src' . DIRECTORY_SEPARATOR . 'somefile.php:5:32',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'noCrashWithEmptyMatch' => [
                'code' => '<?php
                    function foo(int $i) {
                        match ($i) {

                        };
                    }',
                'error_message' => 'UnhandledMatchCondition',
                'error_levels' => [],
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
                'error_levels' => [],
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
                'error_levels' => [],
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
                'error_levels' => [],
                'php_version' => '8.0',
            ],
        ];
    }
}
