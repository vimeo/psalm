<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class DisjointArrayShapesTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'unionOfShapesIsKeptApart' => [
                'code' => '<?php
                    /** @return array{a: int}|array{b: string} */
                    function f(): array {
                        return ["a" => 1];
                    }

                    $x = f();',
                'assertions' => [
                    '$x===' => 'array{a: int}|array{b: string}',
                ],
            ],
            'sealedAssertedShapeOfNoCommonKeyKeepsTheEmptyArrayBothMayBe' => [
                'code' => '<?php
                    /** @psalm-assert-if-true array{b?: string} $v */
                    function isB(array $v): bool {
                        return $v === [] || isset($v["b"]);
                    }

                    /** @param array{a?: int}|array{b: string} $x */
                    function f(array $x): void {
                        if (isB($x)) {
                            /** @psalm-check-type-exact $x = array{b?: string} */;
                            if ($x === []) {}
                        }
                    }',
            ],
            'shapeOfListKeysIsNotHeldByTheListOfThem' => [
                'code' => '<?php
                    /** @return list{int, string}|array{0: int, 1: string}|array{a: bool} */
                    function f(): array {
                        return [1, "x"];
                    }

                    /** @return array{0: int, 1: string}|list{int, string}|array{a: bool} */
                    function g(): array {
                        return [1, "x"];
                    }

                    $x = f();
                    $y = g();',
                'assertions' => [
                    '$x===' => 'array{0: int, 1: string}|array{a: bool}',
                    '$y===' => 'array{0: int, 1: string}|array{a: bool}',
                ],
            ],
            'branchesAssigningDifferentShapesAreKeptApart' => [
                'code' => '<?php
                    $x = rand(0, 1) ? ["a" => 1] : ["b" => "x"];',
                'assertions' => [
                    '$x===' => "array{a: 1}|array{b: 'x'}",
                ],
            ],
            'offsetComparedToFalsePicksTheShape' => [
                'code' => '<?php
                    /** @param array{valid: false}|array{valid: true, moreStuff: int} $input */
                    function f(array $input): int {
                        if ($input["valid"] === false) {
                            return 0;
                        }

                        return $input["moreStuff"];
                    }',
            ],
            'tagPicksTheShapeInIfElse' => [
                'code' => '<?php
                    /** @param array{type: "a", a: int}|array{type: "b", b: string}|array{type: "c"} $x */
                    function f(array $x): string {
                        if ($x["type"] === "a") {
                            return (string) $x["a"];
                        } elseif ($x["type"] === "b") {
                            return $x["b"];
                        }

                        return $x["type"];
                    }',
            ],
            'tagPicksTheShapeInSwitch' => [
                'code' => '<?php
                    /** @param array{type: "a", a: int}|array{type: "b", b: string}|array{type: "c"} $x */
                    function f(array $x): string {
                        switch ($x["type"]) {
                            case "a":
                                return (string) $x["a"];
                            case "b":
                                return $x["b"];
                        }

                        return "";
                    }',
            ],
            'assertedSealedShapeRulesOutTheShapesOfNoCommonKey' => [
                'code' => '<?php
                    /** @psalm-assert-if-true array{world: string} $arg */
                    function isWorld(array $arg): bool {
                        return isset($arg["world"]);
                    }

                    /** @return array{hello: string}|array{world: string} */
                    function f(): array {
                        return ["hello" => "x"];
                    }

                    $x = f();
                    $a = null;
                    if (isWorld($x)) {
                        $a = $x;
                    }',
                'assertions' => [
                    '$a===' => 'array{world: string}|null',
                ],
            ],
            'assertedTemplateOfASealedShapeRulesOutTheShapesOfNoCommonKey' => [
                'code' => '<?php
                    /**
                     * @template T of array{world: string}
                     * @param array{hello: string}|T $arg
                     * @psalm-assert-if-true T $arg
                     */
                    function isWorld(array $arg): bool {
                        return isset($arg["world"]);
                    }

                    /** @return array{hello: string}|array{world: string} */
                    function f(): array {
                        return ["hello" => "x"];
                    }

                    $x = f();
                    $a = null;
                    if (isWorld($x)) {
                        $a = $x;
                    }',
                'assertions' => [
                    '$a===' => 'array{world: string}|null',
                ],
            ],
            'assertedSealedShapeOfAKeyNoShapeHasNarrowsTheShapesAsOne' => [
                'code' => '<?php
                    /** @psalm-assert-if-true array{other: string} $arg */
                    function isOther(array $arg): bool {
                        return isset($arg["other"]);
                    }

                    /** @return array{hello: string}|array{world: string} */
                    function f(): array {
                        return ["hello" => "x"];
                    }

                    $x = f();
                    $a = null;
                    if (isOther($x)) {
                        $a = $x;
                    }',
                'assertions' => [
                    '$a===' => 'array{hello?: string, other: string, world?: string}|null',
                ],
            ],
            'issetOfAnOffsetPicksTheShape' => [
                'code' => '<?php
                    /** @param array{string}|array{value: string} $a */
                    function f(array $a): string {
                        if (isset($a["value"])) {
                            return $a["value"];
                        }

                        return $a[0];
                    }',
            ],
            'arrayKeyExistsPicksTheShape' => [
                'code' => '<?php
                    /** @param array{a: int}|array{b: string} $x */
                    function f(array $x): string {
                        if (array_key_exists("a", $x)) {
                            return (string) $x["a"];
                        }

                        return $x["b"];
                    }',
            ],
            'shapesOfATypeAliasAreKeptApart' => [
                'code' => '<?php
                    /**
                     * @psalm-type Result = array{ok: true, value: int}|array{ok: false, error: string}
                     */
                    final class A {
                        /** @return Result */
                        public function get(): array {
                            return ["ok" => true, "value" => 1];
                        }

                        public function value(): int {
                            $result = $this->get();
                            if (!$result["ok"]) {
                                throw new Exception($result["error"]);
                            }

                            return $result["value"];
                        }
                    }',
            ],
            'nullableUnionOfShapesIsNarrowedPerShape' => [
                'code' => '<?php
                    /** @param array{a: int}|array{b: string}|null $x */
                    function f(?array $x): int {
                        if ($x !== null && isset($x["a"])) {
                            return $x["a"];
                        }

                        return 0;
                    }',
            ],
            'countPicksTheListWithThatManyItems' => [
                'code' => '<?php
                    /** @param list{int}|list{string, string} $x */
                    function f(array $x): string {
                        if (count($x) === 2) {
                            return $x[0];
                        }

                        return "";
                    }',
            ],
            'countedShapeWithOnlyOptionalKeysHasOneOfThem' => [
                'code' => '<?php
                    /** @return array{a?: int, b?: string} */
                    function f(): array {
                        return [];
                    }

                    $x = f();
                    $k = null;
                    $n = null;
                    if (count($x) > 0) {
                        $k = array_key_first($x);
                        $n = count($x);
                    }',
                'assertions' => [
                    '$k===' => "'a'|'b'|null",
                    '$n===' => 'int<1, 2>|null',
                ],
            ],
            'truthyShapeWithOnlyOptionalKeysHasOneOfThem' => [
                'code' => '<?php
                    /** @return array{a?: int, b?: string} */
                    function f(): array {
                        return [];
                    }

                    $x = f();
                    $k = "none";
                    if ($x) {
                        $k = array_key_first($x);
                    }',
                'assertions' => [
                    '$k===' => "'a'|'b'|'none'",
                ],
            ],
            'filteredListIsNonEmptyAfterNotEmpty' => [
                'code' => '<?php
                    function maybeSomething(): ?int {
                        return rand(0, 1) ? 234 : null;
                    }

                    $a = array_filter([maybeSomething(), maybeSomething()]);
                    $m = null;
                    if (!empty($a)) {
                        $m = min($a);
                    }',
                'assertions' => [
                    '$m===' => 'int|null',
                ],
            ],
            'shapeWithOnlyOptionalKeysIsNonEmptyWhenNotEmpty' => [
                'code' => '<?php
                    /** @return array{A?: "A", B?: "B"} */
                    function build(): array {
                        $out = [];
                        if (rand(0, 1)) {
                            $out["A"] = "A";
                        }
                        if (rand(0, 1)) {
                            $out["B"] = "B";
                        }
                        return $out;
                    }

                    /** @return non-empty-array<string, string>|true */
                    function runCount(): array|bool {
                        $out = build();
                        return count($out) === 0 ? true : $out;
                    }

                    /** @return non-empty-array<string, string>|true */
                    function runEmpty(): array|bool {
                        $out = build();
                        return empty($out) ? true : $out;
                    }

                    /** @return non-empty-array<string, string>|true */
                    function runEquality(): array|bool {
                        $out = build();
                        return $out == [] ? true : $out;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'issetOfAnOffsetOfAStaticConstantMakesItSet' => [
                'code' => '<?php
                    class Handler {
                        protected const MAP = ["view" => ["read"], "revert" => ["write", "update"]];

                        public function check(string $operation): string {
                            if (isset(static::MAP[$operation])) {
                                return static::MAP[$operation][0];
                            }

                            return "";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MissingClassConstType'],
            ],
            'issetOnACountedShapeWithOnlyOptionalKeysLeavesTheOtherKey' => [
                'code' => '<?php
                    /** @param array{a?: int, b?: string} $x */
                    function f(array $x): string {
                        if (!$x) {
                            return "";
                        }

                        if (isset($x["a"])) {
                            return (string) $x["a"];
                        }

                        return $x["b"];
                    }',
            ],
            'shapesDifferingInOneKeyAreOneShape' => [
                'code' => '<?php
                    $x = rand(0, 1) ? ["a" => 1] : ["a" => 1, "b" => 2];',
                'assertions' => [
                    '$x===' => 'array{a: 1, b?: 2}',
                ],
            ],
            'unsealedShapesAreOneShape' => [
                'code' => '<?php
                    /** @return array{a: int, ...<string, int>}|array{b: string, ...<string, int>} */
                    function f(): array {
                        return ["a" => 1];
                    }

                    $x = f();',
                'assertions' => [
                    '$x===' => 'array{b?: int|string, ...<string, int>}',
                ],
            ],
            'moreShapesThanTheLimitAreOneShape' => [
                'code' => '<?php
                    /**
                     * @return array{k: 1, a: 1}|array{k: 2, a: 2}|array{k: 3, a: 3}|array{k: 4, a: 4}|array{k: 5, a: 5}
                     *     |array{k: 6, a: 6}|array{k: 7, a: 7}|array{k: 8, a: 8}|array{k: 9, a: 9}
                     */
                    function f(): array {
                        return ["k" => 1, "a" => 1];
                    }

                    $x = f();',
                'assertions' => [
                    '$x===' => 'array{a: 1|2|3|4|5|6|7|8|9, k: 1|2|3|4|5|6|7|8|9}',
                ],
            ],
            'writeToAnOffsetMakesOneShapeOfTheUnion' => [
                'code' => '<?php
                    /** @return array{a: int}|array{b: string} */
                    function f(): array {
                        return ["a" => 1];
                    }

                    $x = f();
                    $x["c"] = 1;',
                'assertions' => [
                    '$x===' => 'array{a?: int, b?: string, c: 1}',
                ],
            ],
            'unsetMakesOneShapeOfTheUnion' => [
                'code' => '<?php
                    /** @return array{a: int, c: int}|array{b: string, c: string} */
                    function f(): array {
                        return ["a" => 1, "c" => 1];
                    }

                    $x = f();
                    unset($x["c"]);',
                'assertions' => [
                    '$x===' => 'array{a?: int, b?: string}',
                ],
            ],
            'unionOfShapesIsAcceptedWhereTheSameUnionIsExpected' => [
                'code' => '<?php
                    /**
                     * @param array{a: int}|array{b: string} $x
                     * @return array{a: int}|array{b: string}
                     */
                    function f(array $x): array {
                        return $x;
                    }

                    /** @param array{a?: int, b?: string} $x */
                    function g(array $x): void {}

                    g(f(["a" => 1]));',
            ],
            'shapeWithNoKeyLeftAfterNotIssetIsTheEmptyArray' => [
                'code' => '<?php
                    /** @return array{a?: int}|array{b: string} */
                    function f(): array {
                        return [];
                    }

                    $x = f();
                    if (isset($x["a"])) {
                        exit;
                    }',
                'assertions' => [
                    '$x===' => 'array{b?: string}',
                ],
            ],
            'shapeOfIntsIsNotHeldByAShapeOfFloats' => [
                'code' => '<?php
                    /** @return array{a: float, z?: int}|array{a: int}|array{b: string} */
                    function f(): array {
                        return ["a" => 1];
                    }

                    $x = f();
                    if (isset($x["z"])) {
                        exit;
                    }',
                'assertions' => [
                    '$x===' => 'array{a: float|int}|array{b: string}',
                ],
            ],
            'shapeOfNestedShapesIsNotHeldByTheirCombinedProperties' => [
                'code' => '<?php
                    /**
                     * @return array{a: array{foo: int}|array{bar: string}, z?: int}|array{a: array{foo?: int, bar?: string}}|array{b: string}
                     */
                    function f(): array {
                        return ["b" => ""];
                    }

                    $x = f();
                    if (isset($x["z"])) {
                        exit;
                    }',
                'assertions' => [
                    '$x===' => 'array{a: array{bar?: string, foo?: int}}|array{b: string}',
                ],
            ],
            'notIssetAsOneAlternativeKeepsTheShapeWithTheOffset' => [
                'code' => '<?php
                    /** @return array{a: int, tag: 1}|array{b: string, tag: 2} */
                    function f(): array {
                        return ["b" => "", "tag" => 2];
                    }

                    $x = f();
                    $y = null;
                    if (!isset($x["a"]) || $x["a"] === 1) {
                        $y = $x;
                    }',
                'assertions' => [
                    '$y===' => 'array{a: 1, tag: 1}|array{b: string, tag: 2}|null',
                ],
            ],
            'negatedIssetAndComparisonKeepsTheShapeWithTheOffset' => [
                'code' => '<?php
                    /** @return array{a: int, tag: 1}|array{b: string, tag: 2} */
                    function f(): array {
                        return ["b" => "", "tag" => 2];
                    }

                    $x = f();
                    if (isset($x["a"]) && $x["a"] !== 1) {
                        exit;
                    }',
                'assertions' => [
                    '$x===' => 'array{a: 1, tag: 1}|array{b: string, tag: 2}',
                ],
            ],
            'keyDoesNotExistAsOneAlternativeKeepsTheShapeWithTheOffset' => [
                'code' => '<?php
                    /** @return array{a: int, tag: 1}|array{b: string, tag: 2} */
                    function f(): array {
                        return ["b" => "", "tag" => 2];
                    }

                    $x = f();
                    $y = null;
                    if (!array_key_exists("a", $x) || $x["a"] === 1) {
                        $y = $x;
                    }',
                'assertions' => [
                    '$y===' => 'array{a: 1, tag: 1}|array{b: string, tag: 2}|null',
                ],
            ],
            'notIssetKeepsTheShapeWhoseMixedOffsetMayBeNull' => [
                'code' => '<?php
                    /** @return array{a: mixed, tag: 1}|array{b: string, tag: 2} */
                    function f(): array {
                        return ["b" => "", "tag" => 2];
                    }

                    $x = f();
                    if (isset($x["a"])) {
                        exit;
                    }',
                'assertions' => [
                    '$x===' => 'array{a: null, tag: 1}|array{b: string, tag: 2}',
                ],
            ],
            'truthyShapeWithMoreOptionalKeysThanShapesKeptApartStaysOneShape' => [
                'code' => '<?php
                    /** @return array{k0?: int, k1?: int, k2?: int, k3?: int, k4?: int, k5?: int, k6?: int, k7?: int, k8?: int} */
                    function f(): array {
                        return [];
                    }

                    $x = f();
                    $y = null;
                    if ($x) {
                        $y = $x;
                    }',
                'assertions' => [
                    '$y===' => 'array{k0?: int, k1?: int, k2?: int, k3?: int, k4?: int, k5?: int, k6?: int, k7?: int, k8?: int}|null',
                ],
            ],
            'unionOfShapesReturnedByAClosureIsInParentheses' => [
                'code' => '<?php
                    /** @return Closure():(array{a: int}|array{b: string}) */
                    function f(): Closure {
                        return static fn(): array => ["a" => 1];
                    }

                    $c = f();',
                'assertions' => [
                    '$c===' => 'Closure[impure]():(array{a: int}|array{b: string})',
                ],
            ],
            'itemsWrittenKeyByKeyInALoopAreOneShape' => [
                'code' => '<?php
                    /** @var list<array{k: string, c: string, t: string, u: bool}> $rows */
                    $rows = [];
                    $ary = [];
                    foreach ($rows as $row) {
                        $ary[$row["k"]]["a"] = $row["c"];
                        $ary[$row["k"]]["b"] = $row["u"];
                        $ary[$row["k"]]["c"] = $row["t"];
                    }

                    foreach ($ary as $data) {
                    }',
                'assertions' => [
                    '$ary===' => 'array<string, array{a: string, b: bool, c: string}>',
                ],
            ],
            'itemsWrittenKeyByKeyInTwoLevelsOfNonLiteralKeysAreOneShape' => [
                'code' => '<?php
                    /** @var string $k */
                    $k = "x";
                    /** @var string $m */
                    $m = "y";
                    $ary = [];
                    $ary[$k][$m]["a"] = 1;
                    $ary[$k][$m]["b"] = 2;
                    $ary[$k][$m]["c"] = 3;',
                'assertions' => [
                    '$ary===' => 'non-empty-array<string, non-empty-array<string, array{a: 1, b: 2, c?: 3}>>',
                ],
            ],
            'unionOfShapesOfAnArrayIsKeptWhenAnotherKeyIsWritten' => [
                'code' => '<?php
                    /** @var array<string, array{a: int}|array{b: string}> $ary */
                    $ary = [];
                    /** @var string $k */
                    $k = "x";
                    $ary[$k] = ["a" => 1];',
                'assertions' => [
                    '$ary===' => 'non-empty-array<string, array{a: int}|array{b: string}>',
                ],
            ],
            'countIsNotRedundantWhenOneShapeMayHaveFewerItems' => [
                'code' => '<?php
                    /** @param array{a: int, b: int}|array{c: int, d?: int, e?: int} $x */
                    function f(array $x): int {
                        if (count($x) >= 2) {
                            return 1;
                        }

                        return 0;
                    }',
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
            'offsetOfTheOtherShapeIsNotThere' => [
                'code' => '<?php
                    /** @param array{valid: false}|array{valid: true, moreStuff: int} $input */
                    function f(array $input): int {
                        if ($input["valid"] === false) {
                            return $input["moreStuff"];
                        }

                        return 0;
                    }',
                'error_message' => 'InvalidArrayOffset',
            ],
            'keysOfDifferentShapesAreNeverBothSet' => [
                'code' => '<?php
                    /** @param array{a: int}|array{b: string} $x */
                    function f(array $x): void {
                        if (isset($x["a"]) && isset($x["b"])) {
                            echo "both";
                        }
                    }',
                'error_message' => 'InvalidArrayOffset',
            ],
            'offsetOfOneShapeIsStillPossiblyUndefined' => [
                'code' => '<?php
                    /** @param array{a: int}|array{b: string} $x */
                    function f(array $x): int {
                        return $x["a"];
                    }',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'unionOfShapesWithAKeyEachIsAlwaysTruthy' => [
                'code' => '<?php
                    /** @param array{a: int}|array{b: string} $x */
                    function f(array $x): void {
                        if ($x) {
                            echo "filled";
                        }
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'optionalKeyOfACountedShapeIsStillPossiblyUndefined' => [
                'code' => '<?php
                    /** @param array{a?: int, b?: string} $x */
                    function f(array $x): int {
                        if (count($x) > 0) {
                            return $x["a"];
                        }

                        return 0;
                    }',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'emptyArrayIsNotAssignableToAUnionOfShapes' => [
                'code' => '<?php
                    class Foo {
                        /** @var array{foo: string}|array{bar: string} */
                        private array $fooOrBar = ["foo" => "a"];

                        public function set(): void {
                            $this->fooOrBar = ["foo" => "hello"];
                            $this->fooOrBar = ["bar" => "hello"];
                            $this->fooOrBar = [];
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue - src' . DIRECTORY_SEPARATOR . 'somefile.php:9:47',
            ],
        ];
    }
}
