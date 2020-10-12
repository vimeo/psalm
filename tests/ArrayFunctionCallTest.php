<?php
namespace Psalm\Tests;

use const DIRECTORY_SEPARATOR;

class ArrayFunctionCallTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'arrayFilter' => [
                '<?php
                    $d = array_filter(["a" => rand(0, 10), "b" => rand(0, 10), "c" => null]);
                    $e = array_filter(
                        ["a" => rand(0, 10), "b" => rand(0, 10), "c" => null],
                        function(?int $i): bool {
                            return true;
                        }
                    );',
                'assertions' => [
                    '$d' => 'array{a?: int, b?: int}',
                    '$e' => 'array<string, int|null>',
                ],
            ],
            'arrayFilterAdvanced' => [
                '<?php
                    $f = array_filter(["a" => 5, "b" => 12, "c" => null], function(?int $val, string $key): bool {
                        return true;
                    }, ARRAY_FILTER_USE_BOTH);
                    $g = array_filter(["a" => 5, "b" => 12, "c" => null], function(string $val): bool {
                        return true;
                    }, ARRAY_FILTER_USE_KEY);

                    $bar = "bar";

                    $foo = [
                        $bar => function (): string {
                            return "baz";
                        },
                    ];

                    $foo = array_filter(
                        $foo,
                        function (string $key): bool {
                            return $key === "bar";
                        },
                        ARRAY_FILTER_USE_KEY
                    );',
                'assertions' => [
                    '$f' => 'array<string, int|null>',
                    '$g' => 'array<string, int|null>',
                ],
            ],
            'arrayFilterIgnoreNullable' => [
                '<?php
                    class A {
                        /**
                         * @return array<int, self|null>
                         */
                        public function getRows() : array {
                            return [new self, null];
                        }

                        public function filter() : void {
                            $arr = array_filter(
                                static::getRows(),
                                function (self $row) : bool {
                                    return is_a($row, static::class);
                                }
                            );
                        }
                    }',
                'assertions' => [],
                'error_levels' => ['PossiblyInvalidArgument'],
            ],
            'arrayFilterAllowTrim' => [
                '<?php
                    $foo = array_filter(["hello ", " "], "trim");',
            ],
            'arrayFilterAllowNull' => [
                '<?php
                    function foo() : array {
                        return array_filter(
                            array_map(
                                /** @return null */
                                function (int $arg) {
                                    return null;
                                },
                                [1, 2, 3]
                            )
                        );
                    }',
            ],
            'arrayFilterNamedFunction' => [
                '<?php
                    /**
                     * @param array<int, DateTimeImmutable|null> $a
                     * @return array<int, DateTimeImmutable>
                     */
                    function foo(array $a) : array {
                        return array_filter($a, "is_object");
                    }',
            ],
            'arrayFilterFleshOutType' => [
                '<?php
                    class Baz {
                        public const STATUS_FOO = "foo";
                        public const STATUS_BAR = "bar";
                        public const STATUS_QUX = "qux";

                        /**
                         * @psalm-param self::STATUS_* $role
                         */
                        public static function isStatus(string $role): bool
                        {
                            return !\in_array($role, [self::STATUS_BAR, self::STATUS_QUX], true);
                        }
                    }

                    /** @psalm-var array<Baz::STATUS_*> $statusList */
                    $statusList = [Baz::STATUS_FOO, Baz::STATUS_QUX];
                    $statusList = array_filter($statusList, [Baz::class, "isStatus"]);'
            ],
            'arrayKeysNonEmpty' => [
                '<?php
                    $a = array_keys(["a" => 1, "b" => 2]);',
                'assertions' => [
                    '$a' => 'non-empty-list<string>',
                ],
            ],
            'arrayKeysMixed' => [
                '<?php
                    /** @var array */
                    $b = ["a" => 5];
                    $a = array_keys($b);',
                'assertions' => [
                    '$a' => 'list<array-key>',
                ],
                'error_levels' => ['MixedArgument'],
            ],
            'arrayValues' => [
                '<?php
                    $b = array_values(["a" => 1, "b" => 2]);
                    $c = array_values(["a" => "hello", "b" => "jello"]);',
                'assertions' => [
                    '$b' => 'non-empty-list<int>',
                    '$c' => 'non-empty-list<string>',
                ],
            ],
            'arrayCombine' => [
                '<?php
                    $c = array_combine(["a", "b", "c"], [1, 2, 3]);',
                'assertions' => [
                    '$c' => 'array<string, int>|false',
                ],
            ],
            'arrayCombineFalse' => [
                '<?php
                    $c = array_combine(["a", "b"], [1, 2, 3]);',
                'assertions' => [
                    '$c' => 'array<string, int>|false',
                ],
            ],
            'arrayMergeIntArrays' => [
                '<?php
                    $d = array_merge(["a", "b", "c"], [1, 2, 3]);',
                'assertions' => [
                    '$d' => 'array{0: string, 1: string, 2: string, 3: int, 4: int, 5: int}',
                ],
            ],
            'arrayMergePossiblyUndefined' => [
                '<?php
                    /**
                     * @param array{host?:string} $opts
                     * @return array{host:string|int}
                     */
                    function b(array $opts): array {
                        return array_merge(["host" => 5], $opts);
                    }',
            ],
            'arrayMergeListResultWithArray' => [
                '<?php
                    /**
                     * @param array<int, string> $list
                     * @return list<string>
                     */
                    function bar(array $list) : array {
                        return array_merge($list, ["test"]);
                    }',
            ],
            'arrayMergeListResultWithList' => [
                '<?php
                    /**
                     * @param list<string> $list
                     * @return list<string>
                     */
                    function foo(array $list) : array {
                        return array_merge($list, ["test"]);
                    }',
            ],
            'arrayReverseDontPreserveKey' => [
                '<?php
                    $d = array_reverse(["a", "b", 1, "d" => 4]);',
                'assertions' => [
                    '$d' => 'non-empty-array<int|string, int|string>',
                ],
            ],
            'arrayReverseDontPreserveKeyExplicitArg' => [
                '<?php
                    $d = array_reverse(["a", "b", 1, "d" => 4], false);',
                'assertions' => [
                    '$d' => 'non-empty-array<int|string, int|string>',
                ],
            ],
            'arrayReversePreserveKey' => [
                '<?php
                    $d = array_reverse(["a", "b", 1], true);',
                'assertions' => [
                    '$d' => 'non-empty-array<int, int|string>',
                ],
            ],
            'arrayDiff' => [
                '<?php
                    $d = array_diff(["a" => 5, "b" => 12], [5]);',
                'assertions' => [
                    '$d' => 'array<string, int>',
                ],
            ],
            'arrayDiffIsVariadic' => [
                '<?php
                    array_diff([], [], [], [], []);',
                'assertions' => [],
            ],
            'arrayDiffKeyIsVariadic' => [
                '<?php
                    array_diff_key([], [], [], [], []);',
                'assertions' => [],
            ],
            'arrayDiffAssoc' => [
                '<?php
                    /**
                     * @var array<string, int> $a
                     * @var array $b
                     * @var array $c
                     */
                    $r = array_diff_assoc($a, $b, $c);',
                'assertions' => [
                    '$r' => 'array<string, int>',
                ],
            ],
            'arrayPopMixed' => [
                '<?php
                    /** @var mixed */
                    $b = ["a" => 5, "c" => 6];
                    $a = array_pop($b);',
                'assertions' => [
                    '$a' => 'mixed',
                    '$b' => 'mixed',
                ],
                'error_levels' => ['MixedAssignment', 'MixedArgument'],
            ],
            'arrayPopNonEmpty' => [
                '<?php
                    /** @var array<string, int> */
                    $a = ["a" => 5, "b" => 6, "c" => 7];
                    $b = 5;
                    if ($a) {
                        $b = array_pop($a);
                    }
                    $c = array_pop($a);',
                'assertions' => [
                    '$b' => 'int',
                    '$c' => 'int|null',
                ],
            ],
            'arrayPopNonEmptyAfterIsset' => [
                '<?php
                    /** @var array<string, int> */
                    $a = ["a" => 5, "b" => 6, "c" => 7];
                    $b = 5;
                    if (isset($a["a"])) {
                        $b = array_pop($a);
                    }',
                'assertions' => [
                    '$b' => 'int',
                ],
            ],
            'arrayPopNonEmptyAfterCount' => [
                '<?php
                    /** @var array<string, int> */
                    $a = ["a" => 5, "b" => 6, "c" => 7];
                    $b = 5;
                    if (count($a)) {
                        $b = array_pop($a);
                    }',
                'assertions' => [
                    '$b' => 'int',
                ],
            ],
            'arrayShiftNonEmptyList' => [
                '<?php
                    /** @param non-empty-list $arr */
                    function type_of_array_shift(array $arr) : int {
                        if (\is_int($arr[0])) {
                            return \array_shift($arr);
                        }

                        return 0;
                    }',
            ],
            'arrayShiftFunkyObjectLikeList' => [
                '<?php
                    /**
                     * @param non-empty-list<string>|array{null} $arr
                     * @return array<int, string>
                     */
                    function foo(array $arr) {
                        array_shift($arr);
                        return $arr;
                    }'
            ],
            'arrayPopNonEmptyAfterCountEqualsOne' => [
                '<?php
                    /** @var array<string, int> */
                    $a = ["a" => 5, "b" => 6, "c" => 7];
                    $b = 5;
                    if (count($a) === 1) {
                        $b = array_pop($a);
                    }',
                'assertions' => [
                    '$b' => 'int',
                ],
            ],
            'arrayPopNonEmptyAfterCountSoftEqualsOne' => [
                '<?php
                    /** @var array<string, int> */
                    $a = ["a" => 5, "b" => 6, "c" => 7];
                    $b = 5;
                    if (count($a) == 1) {
                        $b = array_pop($a);
                    }',
                'assertions' => [
                    '$b' => 'int',
                ],
            ],
            'arrayPopNonEmptyAfterCountGreaterThanOne' => [
                '<?php
                    /** @var array<string, int> */
                    $a = ["a" => 5, "b" => 6, "c" => 7];
                    $b = 5;
                    if (count($a) > 0) {
                        $b = array_pop($a);
                    }',
                'assertions' => [
                    '$b' => 'int',
                ],
            ],
            'arrayPopNonEmptyAfterCountGreaterOrEqualsOne' => [
                '<?php
                    /** @var array<string, int> */
                    $a = ["a" => 5, "b" => 6, "c" => 7];
                    $b = 5;
                    if (count($a) >= 1) {
                        $b = array_pop($a);
                    }',
                'assertions' => [
                    '$b' => 'int',
                ],
            ],
            'arrayPopNonEmptyAfterCountEqualsOneReversed' => [
                '<?php
                    /** @var array<string, int> */
                    $a = ["a" => 5, "b" => 6, "c" => 7];
                    $b = 5;
                    if (1 === count($a)) {
                        $b = array_pop($a);
                    }',
                'assertions' => [
                    '$b' => 'int',
                ],
            ],
            'arrayPopNonEmptyAfterCountSoftEqualsOneReversed' => [
                '<?php
                    /** @var array<string, int> */
                    $a = ["a" => 5, "b" => 6, "c" => 7];
                    $b = 5;
                    if (1 == count($a)) {
                        $b = array_pop($a);
                    }',
                'assertions' => [
                    '$b' => 'int',
                ],
            ],
            'arrayPopNonEmptyAfterCountGreaterThanOneReversed' => [
                '<?php
                    /** @var array<string, int> */
                    $a = ["a" => 5, "b" => 6, "c" => 7];
                    $b = 5;
                    if (0 < count($a)) {
                        $b = array_pop($a);
                    }',
                'assertions' => [
                    '$b' => 'int',
                ],
            ],
            'arrayPopNonEmptyAfterCountGreatorOrEqualToOneReversed' => [
                '<?php
                    /** @var array<string, int> */
                    $a = ["a" => 5, "b" => 6, "c" => 7];
                    $b = 5;
                    if (1 <= count($a)) {
                        $b = array_pop($a);
                    }',
                'assertions' => [
                    '$b' => 'int',
                ],
            ],
            'arrayNotEmptyArrayAfterCountLessThanEqualToOne' => [
                '<?php
                    /** @var list<int> */
                    $leftCount = [1, 2, 3];
                    if (count($leftCount) <= 1) {
                        echo $leftCount[0];
                    }
                    /** @var list<int> */
                    $rightCount = [1, 2, 3];
                    if (1 >= count($rightCount)) {
                        echo $rightCount[0];
                    }',
            ],
            'arrayNotEmptyArrayAfterCountLessThanTwo' => [
                '<?php
                    /** @var list<int> */
                    $leftCount = [1, 2, 3];
                    if (count($leftCount) < 2) {
                        echo $leftCount[0];
                    }
                    /** @var list<int> */
                    $rightCount = [1, 2, 3];
                    if (2 > count($rightCount)) {
                        echo $rightCount[0];
                    }',
            ],
            'arrayEmptyArrayAfterCountLessThanOne' => [
                '<?php
                    /** @var list<int> */
                    $leftCount = [1, 2, 3];
                    assert (count($leftCount) < 1);
                    /** @var list<int> */
                    $rightCount = [1, 2, 3];
                    assert (1 > count($rightCount));',
                'assertions' => [
                    '$leftCount' => 'array<empty, empty>',
                    '$rightCount' => 'array<empty, empty>',
                ],
            ],
            'arrayEmptyArrayAfterCountLessThanEqualToZero' => [
                '<?php
                    /** @var list<int> */
                    $leftCount = [1, 2, 3];
                    assert (count($leftCount) <= 0);
                    /** @var list<int> */
                    $rightCount = [1, 2, 3];
                    assert (0 >= count($rightCount));',
                'assertions' => [
                    '$leftCount' => 'array<empty, empty>',
                    '$rightCount' => 'array<empty, empty>',
                ],
            ],
            'arrayNotNonEmptyArrayAfterCountGreaterThanEqualToZero' => [
                '<?php
                    /** @var list<int> */
                    $leftCount = [1, 2, 3];
                    assert(count($leftCount) >= 0);
                    /** @var list<int> */
                    $rightCount = [1, 2, 3];
                    assert(0 <= count($rightCount));',
                'assertions' => [
                    '$leftCount' => 'list<int>',
                    '$rightCount' => 'list<int>',
                ],
            ],
            'arrayNotNonEmptyArrayAfterCountGreaterThanMinusOne' => [
                '<?php
                    /** @var list<int> */
                    $leftCount = [1, 2, 3];
                    assert (count($leftCount) > -1);
                    /** @var list<int> */
                    $rightCount = [1, 2, 3];
                    assert (-1 < count($rightCount));',
                'assertions' => [
                    '$leftCount' => 'list<int>',
                    '$rightCount' => 'list<int>',
                ],
            ],
            'arrayNonEmptyArrayAfterCountGreaterThanEqualToOne' => [
                '<?php
                    /** @var list<int> */
                    $leftCount = [1, 2, 3];
                    assert(count($leftCount) >= 1);
                    /** @var list<int> */
                    $rightCount = [1, 2, 3];
                    assert(1 <= count($rightCount));',
                'assertions' => [
                    '$leftCount' => 'non-empty-list<int>',
                    '$rightCount' => 'non-empty-list<int>',
                ],
            ],
            'arrayNonEmptyArrayAfterCountGreaterThanZero' => [
                '<?php
                    /** @var list<int> */
                    $leftCount = [1, 2, 3];
                    assert (count($leftCount) > 0);
                    /** @var list<int> */
                    $rightCount = [1, 2, 3];
                    assert (0 < count($rightCount));',
                'assertions' => [
                    '$leftCount' => 'non-empty-list<int>',
                    '$rightCount' => 'non-empty-list<int>',
                ],
            ],
            'arrayPopNonEmptyAfterArrayAddition' => [
                '<?php
                    /** @var array<string, int> */
                    $a = ["a" => 5, "b" => 6, "c" => 7];
                    $a["foo"] = 10;
                    $b = array_pop($a);',
                'assertions' => [
                    '$b' => 'int',
                ],
            ],
            'arrayPopNonEmptyAfterMixedArrayAddition' => [
                '<?php
                    /** @var array */
                    $a = ["a" => 5, "b" => 6, "c" => 7];
                    $a[] = "hello";
                    $b = array_pop($a);',
                'assertions' => [
                    '$b' => 'mixed|string',
                ],
                'error_levels' => [
                    'MixedAssignment',
                ],
            ],
            'uasort' => [
                '<?php
                    $manifest = ["a" => 1, "b" => 2];
                    uasort(
                        $manifest,
                        function (int $a, int $b) {
                            return $a > $b ? 1 : -1;
                        }
                    );',
                'assertions' => [
                    '$manifest' => 'array<string, int>'
                ],
            ],
            'uksort' => [
                '<?php
                    $array = ["b" => 1, "a" => 2];
                    uksort(
                        $array,
                        function (string $a, string $b) {
                            return $a <=> $b;
                        }
                    );',
                'assertions' => [
                    '$array' => 'array<string, int>',
                ],
            ],
            'arrayMergeObjectLike' => [
                '<?php
                  /**
                   * @param array<string, int> $a
                   * @return array<string, int>
                   */
                  function foo($a)
                  {
                    return $a;
                  }

                  $a1 = ["hi" => 3];
                  $a2 = ["bye" => 5];
                  $a3 = array_merge($a1, $a2);

                  foo($a3);',
                'assertions' => [
                    '$a3' => 'array{hi: int, bye: int}',
                ],
            ],
            'arrayRand' => [
                '<?php
                    $vars = ["x" => "a", "y" => "b"];
                    $c = array_rand($vars);
                    $d = $vars[$c];
                    $more_vars = ["a", "b"];
                    $e = array_rand($more_vars);',

                'assertions' => [
                    '$vars' => 'array{x: string, y: string}',
                    '$c' => 'string',
                    '$d' => 'string',
                    '$more_vars' => 'array{string, string}',
                    '$e' => 'int',
                ],
            ],
            'arrayRandMultiple' => [
                '<?php
                    $vars = ["x" => "a", "y" => "b"];
                    $b = 3;
                    $c = array_rand($vars, 1);
                    $d = array_rand($vars, 2);
                    $e = array_rand($vars, 3);
                    $f = array_rand($vars, $b);',

                'assertions' => [
                    '$vars' => 'array{x: string, y: string}',
                    '$c' => 'string',
                    '$e' => 'list<string>',
                    '$f' => 'list<string>|string',
                ],
            ],
            'arrayKeysNoEmpty' => [
                '<?php
                    function expect_string(string $x): void {
                        echo $x;
                    }

                    function test(): void {
                        foreach (array_keys([]) as $key) {
                            expect_string($key);
                        }
                    }',
                'assertions' => [],
                'error_levels' => ['MixedAssignment', 'MixedArgument', 'MixedArgumentTypeCoercion'],
            ],
            'arrayPopNotNullable' => [
                '<?php
                    function expectsInt(int $a) : void {}

                    /**
                     * @param array<array-key, array{item:int}> $list
                     */
                    function test(array $list) : void
                    {
                        while (!empty($list)) {
                            $tmp = array_pop($list);
                            expectsInt($tmp["item"]);
                        }
                    }',
            ],
            'arrayFilterWithAssert' => [
                '<?php
                    $a = array_filter(
                        [1, "hello", 6, "goodbye"],
                        function ($s): bool {
                            return is_string($s);
                        }
                    );',
                'assertions' => [
                    '$a' => 'array<int, string>',
                ],
                'error_levels' => [
                    'MissingClosureParamType',
                ],
            ],
            'arrayFilterUseKey' => [
                '<?php
                    $bar = "bar";

                    $foo = [
                        $bar => function (): string {
                            return "baz";
                        },
                    ];

                    $foo = array_filter(
                        $foo,
                        function (string $key): bool {
                            return $key === "bar";
                        },
                        ARRAY_FILTER_USE_KEY
                    );',
                'assertions' => [
                    '$foo' => 'array<string, pure-Closure():string(baz)>',
                ],
            ],
            'ignoreFalsableCurrent' => [
                '<?php
                    /** @param string[] $arr */
                    function foo(array $arr): string {
                        return current($arr);
                    }
                    /** @param string[] $arr */
                    function bar(array $arr): string {
                        $a = current($arr);
                        if ($a === false) {
                            return "hello";
                        }
                        return $a;
                    }
                    /**
                     * @param string[] $arr
                     * @return false|string
                     */
                    function bat(array $arr) {
                        return current($arr);
                    }',
            ],
            'arraySumEmpty' => [
                '<?php
                    $foo = array_sum([]) + 1;',
                'assertions' => [
                    '$foo' => 'int',
                ],
            ],
            'arraySumOnlyInt' => [
                '<?php
                    $foo = array_sum([5,18]);',
                'assertions' => [
                    '$foo' => 'int',
                ],
            ],
            'arraySumOnlyFloat' => [
                '<?php
                    $foo = array_sum([5.1,18.2]);',
                'assertions' => [
                    '$foo' => 'float',
                ],
            ],
            'arraySumNumeric' => [
                '<?php
                    $foo = array_sum(["5","18"]);',
                'assertions' => [
                    '$foo' => 'float|int',
                ],
            ],
            'arraySumMix' => [
                '<?php
                    $foo = array_sum([5,18.5]);',
                'assertions' => [
                    '$foo' => 'float',
                ],
            ],
            'arrayMapWithArrayAndCallable' => [
                '<?php
                    /**
                     * @psalm-return array<array-key, int>
                     */
                    function foo(array $v): array {
                        $r = array_map("intval", $v);
                        return $r;
                    }',
            ],
            'arrayMapObjectLikeAndCallable' => [
                '<?php
                    /**
                     * @psalm-return array{key1:int,key2:int}
                     */
                    function foo(): array {
                        $v = ["key1"=> 1, "key2"=> "2"];
                        $r = array_map("intval", $v);
                        return $r;
                    }',
            ],
            'arrayMapObjectLikeListAndCallable' => [
                '<?php
                    /** @param list<int> $list */
                    function takesList(array $list): void {}

                    takesList(
                        array_map(
                            "intval",
                            ["1", "2", "3"]
                        )
                    );',
            ],
            'arrayMapObjectLikeAndClosure' => [
                '<?php
                    /**
                     * @psalm-return array{key1:int,key2:int}
                     */
                    function foo(): array {
                      $v = ["key1"=> 1, "key2"=> "2"];
                      $r = array_map(function($i) : int { return intval($i);}, $v);
                      return $r;
                    }',
                'assertions' => [],
                'error_levels' => [
                    'MissingClosureParamType',
                    'MixedTypeCoercion',
                ],
            ],
            'arrayMapObjectLikeListAndClosure' => [
                '<?php
                    /** @param list<string> $list */
                    function takesList(array $list): void {}

                    takesList(
                        array_map(
                            function (string $str): string { return $str . "x"; },
                            ["foo", "bar", "baz"]
                        )
                    );',
            ],
            'arrayMapUntypedCallable' => [
                '<?php
                    /**
                     * @var callable $callable
                     * @var array<string, int> $array
                     */
                    $a = array_map($callable, $array);

                    /**
                     * @var callable $callable
                     * @var array<string, int> $array
                     */
                    $b = array_map($callable, $array, $array);

                    /**
                     * @var callable $callable
                     * @var list<string> $list
                     */
                    $c = array_map($callable, $list);

                    /**
                     * @var callable $callable
                     * @var list<string> $list
                     */
                    $d = array_map($callable, $list, $list);',
                'assertions' => [
                    '$a' => 'array<string, mixed>',
                    '$b' => 'list<mixed>',
                    '$c' => 'list<mixed>',
                    '$d' => 'list<mixed>',
                ],
            ],
            'arrayFilterGoodArgs' => [
                '<?php
                    function fooFoo(int $i) : bool {
                      return true;
                    }

                    class A {
                        public static function barBar(int $i) : bool {
                            return true;
                        }
                    }

                    array_filter([1, 2, 3], "fooFoo");
                    array_filter([1, 2, 3], "foofoo");
                    array_filter([1, 2, 3], "FOOFOO");
                    array_filter([1, 2, 3], "A::barBar");
                    array_filter([1, 2, 3], "A::BARBAR");
                    array_filter([1, 2, 3], "A::barbar");',
            ],
            'arrayFilterIgnoreMissingClass' => [
                '<?php
                    array_filter([1, 2, 3], "A::bar");',
                'assertions' => [],
                'error_levels' => ['UndefinedClass'],
            ],
            'arrayFilterIgnoreMissingMethod' => [
                '<?php
                    class A {
                        public static function bar(int $i) : bool {
                            return true;
                        }
                    }

                    array_filter([1, 2, 3], "A::foo");',
                'assertions' => [],
                'error_levels' => ['UndefinedMethod'],
            ],
            'arrayMapParamDefault' => [
                '<?php
                    $arr = ["a", "b"];
                    array_map("mapdef", $arr, array_fill(0, count($arr), 1));
                    function mapdef(string $_a, int $_b = 0): string {
                        return "a";
                    }',
            ],
            'implodeMultiDimensionalArray' => [
                '<?php
                    $urls = array_map("implode", [["a", "b"]]);',
            ],
            'implodeNonEmptyArrayAndString' => [
                '<?php
                    /** @var non-empty-array<non-empty-string> */
                    $l = ["a", "b"];
                    $a = implode(":", $l);',
                [
                    '$a===' => 'non-empty-string',
                ]
            ],
            'key' => [
                '<?php
                    $a = ["one" => 1, "two" => 3];
                    $b = key($a);',
                'assertions' => [
                    '$b' => 'null|string',
                ],
            ],
            'keyEmptyArray' => [
                '<?php
                    $a = [];
                    $b = key($a);',
                'assertions' => [
                    '$b' => 'null',
                ],
            ],
            'keyNonEmptyArray' => [
                '<?php
                    /**
                     * @param non-empty-array $arr
                     * @return null|array-key
                     */
                    function foo(array $arr) {
                        return key($arr);
                    }',
            ],
            'arrayKeyFirst' => [
                '<?php
                    /** @return array<string, int> */
                    function makeArray(): array { return ["one" => 1, "two" => 3]; }
                    $a = makeArray();
                    $b = array_key_first($a);
                    $c = null;
                    if ($b !== null) {
                        $c = $a[$b];
                    }',
                'assertions' => [
                    '$b' => 'null|string',
                    '$c' => 'int|null',
                ],
            ],
            'arrayKeyFirstNonEmpty' => [
                '<?php
                    $a = ["one" => 1, "two" => 3];
                    $b = array_key_first($a);
                    $c = $a[$b];',
                'assertions' => [
                    '$b' => 'string',
                    '$c' => 'int',
                ],
            ],
            'arrayKeyFirstEmpty' => [
                '<?php
                    $a = [];
                    $b = array_key_first($a);',
                'assertions' => [
                    '$b' => 'null'
                ],
            ],
            'arrayKeyLast' => [
                '<?php
                    /** @return array<string, int> */
                    function makeArray(): array { return ["one" => 1, "two" => 3]; }
                    $a = makeArray();
                    $b = array_key_last($a);
                    $c = null;
                    if ($b !== null) {
                        $c = $a[$b];
                    }',
                'assertions' => [
                    '$b' => 'null|string',
                    '$c' => 'int|null',
                ],
            ],
            'arrayKeyLastNonEmpty' => [
                '<?php
                    $a = ["one" => 1, "two" => 3];
                    $b = array_key_last($a);
                    $c = $a[$b];',
                'assertions' => [
                    '$b' => 'string',
                    '$c' => 'int',
                ],
            ],
            'arrayKeyLastEmpty' => [
                '<?php
                    $a = [];
                    $b = array_key_last($a);',
                'assertions' => [
                    '$b' => 'null'
                ],
            ],
            'arrayResetNonEmptyArray' => [
                '<?php
                    /** @return non-empty-array<string, int> */
                    function makeArray(): array { return ["one" => 1, "two" => 3]; }
                    $a = makeArray();
                    $b = reset($a);',
                'assertions' => [
                    '$b' => 'int'
                ],
            ],
            'arrayResetNonEmptyList' => [
                '<?php
                    /** @return non-empty-list<int> */
                    function makeArray(): array { return [1, 3]; }
                    $a = makeArray();
                    $b = reset($a);',
                'assertions' => [
                    '$b' => 'int'
                ],
            ],
            'arrayResetNonEmptyObjectLike' => [
                '<?php
                    $a = ["one" => 1, "two" => 3];
                    $b = reset($a);',
                'assertions' => [
                    '$b' => 'int'
                ],
            ],
            'arrayResetEmptyArray' => [
                '<?php
                    $a = [];
                    $b = reset($a);',
                'assertions' => [
                    '$b' => 'false'
                ],
            ],
            'arrayResetEmptyList' => [
                '<?php
                    /** @return list<empty> */
                    function makeArray(): array { return []; }
                    $a = makeArray();
                    $b = reset($a);',
                'assertions' => [
                    '$b' => 'false'
                ],
            ],
            'arrayResetMaybeEmptyArray' => [
                '<?php
                    /** @return array<string, int> */
                    function makeArray(): array { return ["one" => 1, "two" => 3]; }
                    $a = makeArray();
                    $b = reset($a);',
                'assertions' => [
                    '$b' => 'false|int'
                ],
            ],
            'arrayResetMaybeEmptyList' => [
                '<?php
                    /** @return list<int> */
                    function makeArray(): array { return []; }
                    $a = makeArray();
                    $b = reset($a);',
                'assertions' => [
                    '$b' => 'false|int'
                ],
            ],
            'arrayResetMaybeEmptyObjectLike' => [
                '<?php
                    /** @return array{foo?: int} */
                    function makeArray(): array { return []; }
                    $a = makeArray();
                    $b = reset($a);',
                'assertions' => [
                    '$b' => 'false|int'
                ],
            ],
            'arrayEndNonEmptyArray' => [
                '<?php
                    /** @return non-empty-array<string, int> */
                    function makeArray(): array { return ["one" => 1, "two" => 3]; }
                    $a = makeArray();
                    $b = end($a);',
                'assertions' => [
                    '$b' => 'int'
                ],
            ],
            'arrayEndNonEmptyList' => [
                '<?php
                    /** @return non-empty-list<int> */
                    function makeArray(): array { return [1, 3]; }
                    $a = makeArray();
                    $b = end($a);',
                'assertions' => [
                    '$b' => 'int'
                ],
            ],
            'arrayEndNonEmptyObjectLike' => [
                '<?php
                    $a = ["one" => 1, "two" => 3];
                    $b = end($a);',
                'assertions' => [
                    '$b' => 'int'
                ],
            ],
            'arrayEndEmptyArray' => [
                '<?php
                    $a = [];
                    $b = end($a);',
                'assertions' => [
                    '$b' => 'false'
                ],
            ],
            'arrayEndEmptyList' => [
                '<?php
                    /** @return list<empty> */
                    function makeArray(): array { return []; }
                    $a = makeArray();
                    $b = end($a);',
                'assertions' => [
                    '$b' => 'false'
                ],
            ],
            'arrayEndMaybeEmptyArray' => [
                '<?php
                    /** @return array<string, int> */
                    function makeArray(): array { return ["one" => 1, "two" => 3]; }
                    $a = makeArray();
                    $b = end($a);',
                'assertions' => [
                    '$b' => 'false|int'
                ],
            ],
            'arrayEndMaybeEmptyList' => [
                '<?php
                    /** @return list<int> */
                    function makeArray(): array { return []; }
                    $a = makeArray();
                    $b = end($a);',
                'assertions' => [
                    '$b' => 'false|int'
                ],
            ],
            'arrayEndMaybeEmptyObjectLike' => [
                '<?php
                    /** @return array{foo?: int} */
                    function makeArray(): array { return []; }
                    $a = makeArray();
                    $b = end($a);',
                'assertions' => [
                    '$b' => 'false|int'
                ],
            ],
            'arrayColumnInference' => [
                '<?php
                    function makeMixedArray(): array { return []; }
                    /** @return array<array<int,bool>> */
                    function makeGenericArray(): array { return []; }
                    /** @return array<array{0:string}> */
                    function makeShapeArray(): array { return []; }
                    /** @return array<array{0:string}|int> */
                    function makeUnionArray(): array { return []; }
                    $a = array_column([[1], [2], [3]], 0);
                    $b = array_column([["a" => 1], ["a" => 2], ["a" => 3]], "a");
                    $c = array_column([["k" => "a", "v" => 1], ["k" => "b", "v" => 2]], "v", "k");
                    $d = array_column([], 0);
                    $e = array_column(makeMixedArray(), 0);
                    $f = array_column(makeMixedArray(), 0, "k");
                    $g = array_column(makeMixedArray(), 0, null);
                    $h = array_column(makeGenericArray(), 0);
                    $i = array_column(makeShapeArray(), 0);
                    $j = array_column(makeUnionArray(), 0);
                    $k = array_column([[0 => "test"]], 0);
                ',
                'assertions' => [
                    '$a' => 'non-empty-list<int>',
                    '$b' => 'non-empty-list<int>',
                    '$c' => 'non-empty-array<string, int>',
                    '$d' => 'list<mixed>',
                    '$e' => 'list<mixed>',
                    '$f' => 'array<array-key, mixed>',
                    '$g' => 'list<mixed>',
                    '$h' => 'list<mixed>',
                    '$i' => 'list<string>',
                    '$j' => 'list<mixed>',
                    '$k' => 'non-empty-list<string>',
                ],
            ],
            'splatArrayIntersect' => [
                '<?php
                    $foo = [
                        [1, 2, 3],
                        [1, 2],
                    ];

                    $bar = array_intersect(... $foo);',
                'assertions' => [
                    '$bar' => 'array<int, int>',
                ],
            ],
            'arrayIntersectIsVariadic' => [
                '<?php
                    array_intersect([], [], [], [], []);',
                'assertions' => [],
            ],
            'arrayIntersectKeyIsVariadic' => [
                '<?php
                    array_intersect_key([], [], [], [], []);',
                'assertions' => [],
            ],
            'arrayIntersectKeyNoReturnType' => [
                '<?php
                    /**
                     * @psalm-suppress MissingReturnType
                     */
                    function unknown() {
                        return ["x" => "hello"];
                    }

                    class C {
                        /**
                         * @psalm-suppress MissingReturnType
                         */
                        public static function unknownStatic() {
                            return ["x" => "hello"];
                        }

                        /**
                         * @psalm-suppress MissingReturnType
                         */
                        public static function unknownInstance() {
                            return ["x" => "hello"];
                        }
                    }

                    /**
                     * @psalm-suppress MixedArgument
                     */
                    function sdn(array $s) : void {
                        $r = array_intersect_key(unknown(), array_filter($s));
                        if (empty($r)) {}

                        $r = array_intersect_key(C::unknownStatic(), array_filter($s));
                        if (empty($r)) {}

                        $r = array_intersect_key((new C)->unknownInstance(), array_filter($s));
                        if (empty($r)) {}
                    }',
            ],
            'arrayIntersectAssoc' => [
                '<?php
                    /**
                     * @var array<string, int> $a
                     * @var array $b
                     * @var array $c
                     */
                    $r = array_intersect_assoc($a, $b, $c);',
                'assertions' => [
                    '$r' => 'array<string, int>',
                ],
            ],
            'arrayReduce' => [
                '<?php
                    $arr = [2, 3, 4, 5];

                    function multiply (int $carry, int $item) : int {
                        return $carry * $item;
                    }

                    $f2 = function (int $carry, int $item) : int {
                        return $carry * $item;
                    };

                    $direct_closure_result = array_reduce(
                        $arr,
                        function (int $carry, int $item) : int {
                            return $carry * $item;
                        },
                        1
                    );

                    $passed_closure_result = array_reduce(
                        $arr,
                        $f2,
                        1
                    );

                    $function_call_result = array_reduce(
                        $arr,
                        "multiply",
                        1
                    );',
                'assertions' => [
                    '$direct_closure_result' => 'int',
                    '$passed_closure_result' => 'int',
                    '$function_call_result' => 'int',
                ],
            ],
            'arrayReduceMixedReturn' => [
                '<?php
                    $arr = [2, 3, 4, 5];

                    $direct_closure_result = array_reduce(
                        $arr,
                        function (int $carry, int $item) {
                            return $_GET["boo"];
                        },
                        1
                    );',
                'assertions' => [],
                'error_levels' => ['MissingClosureReturnType', 'MixedAssignment'],
            ],
            'arraySpliceArray' => [
                '<?php
                    $a = [1, 2, 3];
                    $c = $a;
                    $b = ["a", "b", "c"];
                    array_splice($a, rand(-10, 0), rand(0, 10), $b);',
                'assertions' => [
                    '$a' => 'non-empty-list<int|string>',
                    '$b' => 'array{string, string, string}',
                    '$c' => 'array{int, int, int}',
                ],
            ],
            'arraySpliceReturn' => [
                '<?php
                    $d = [1, 2, 3];
                    $e = array_splice($d, -1, 1);',
                'assertions' => [
                    '$e' => 'array<array-key, mixed>'
                ],
            ],
            'arraySpliceOtherType' => [
                '<?php
                    $d = [["red"], ["green"], ["blue"]];
                    array_splice($d, -1, 1, "foo");',
                'assertions' => [
                    '$d' => 'array<int, array{string}|string>',
                ],
            ],
            'ksortPreserveShape' => [
                '<?php
                    $a = ["a" => 3, "b" => 4];
                    ksort($a);
                    acceptsAShape($a);

                    /**
                     * @param array{a:int,b:int} $a
                     */
                    function acceptsAShape(array $a): void {}',
            ],
            'arraySlicePreserveKeys' => [
                '<?php
                    $a = ["a" => 1, "b" => 2, "c" => 3];
                    $b = array_slice($a, 1, 2, true);
                    $c = array_slice($a, 1, 2, false);
                    $d = array_slice($a, 1, 2);',
                'assertions' => [
                    '$b' => 'array<string, int>',
                    '$c' => 'array<string, int>',
                    '$d' => 'array<string, int>',
                ],
            ],
            'arraySliceDontPreserveIntKeys' => [
                '<?php
                    $a = [1 => "a", 4 => "b", 3 => "c"];
                    $b = array_slice($a, 1, 2, true);
                    $c = array_slice($a, 1, 2, false);
                    $d = array_slice($a, 1, 2);',
                'assertions' => [
                    '$b' => 'array<int, string>',
                    '$c' => 'list<string>',
                    '$d' => 'list<string>',
                ],
            ],
            'arrayReversePreserveNonEmptiness' => [
                '<?php
                    /** @param string[] $arr */
                    function getOrderings(array $arr): int {
                        if ($arr) {
                            $next = null;
                            foreach (array_reverse($arr) as $v) {
                                $next = 1;
                            }
                            return $next;
                        }

                        return 2;
                    }',
            ],
            'inferArrayMapReturnType' => [
                '<?php
                    /** @return array<string> */
                    function Foo(DateTime ...$dateTimes) : array {
                        return array_map(
                            function ($dateTime) {
                                return (string) ($dateTime->format("c"));
                            },
                            $dateTimes
                        );
                    }',
            ],
            'inferArrayMapArrowFunctionReturnType' => [
                '<?php
                    /** @return array<string> */
                    function Foo(DateTime ...$dateTimes) : array {
                        return array_map(
                            fn ($dateTime) => (string) ($dateTime->format("c")),
                            $dateTimes
                        );
                    }',
                'assertions' => [],
                'error_levels' => [],
                '7.4',
            ],
            'arrayPad' => [
                '<?php
                    $a = array_pad(["foo" => 1, "bar" => 2], 10, 123);
                    $b = array_pad(["a", "b", "c"], 10, "x");
                    /** @var list<int> $list */
                    $c = array_pad($list, 10, 0);
                    /** @var array<string, string> $array */
                    $d = array_pad($array, 10, "");',
                'assertions' => [
                    '$a' => 'non-empty-array<int|string, int>',
                    '$b' => 'non-empty-list<string>',
                    '$c' => 'non-empty-list<int>',
                    '$d' => 'non-empty-array<int|string, string>',
                ],
            ],
            'arrayPadDynamicSize' => [
                '<?php
                    function getSize(): int { return random_int(1, 10); }

                    $a = array_pad(["foo" => 1, "bar" => 2], getSize(), 123);
                    $b = array_pad(["a", "b", "c"], getSize(), "x");
                    /** @var list<int> $list */
                    $c = array_pad($list, getSize(), 0);
                    /** @var array<string, string> $array */
                    $d = array_pad($array, getSize(), "");',
                'assertions' => [
                    '$a' => 'array<int|string, int>',
                    '$b' => 'list<string>',
                    '$c' => 'list<int>',
                    '$d' => 'array<int|string, string>',
                ],
            ],
            'arrayPadZeroSize' => [
                '<?php
                    /** @var array $arr */
                    $result = array_pad($arr, 0, null);',
                'assertions' => [
                    '$result' => 'array<array-key, mixed|null>',
                ],
            ],
            'arrayPadTypeCombination' => [
                '<?php
                    $a = array_pad(["foo" => 1, "bar" => "two"], 5, false);
                    $b = array_pad(["a", 2, 3.14], 5, null);
                    /** @var list<string|bool> $list */
                    $c = array_pad($list, 5, 0);
                    /** @var array<string, string> $array */
                    $d = array_pad($array, 5, null);',
                'assertions' => [
                    '$a' => 'non-empty-array<int|string, false|int|string>',
                    '$b' => 'non-empty-list<float|int|null|string>',
                    '$c' => 'non-empty-list<bool|int|string>',
                    '$d' => 'non-empty-array<int|string, null|string>',
                ],
            ],
            'arrayPadMixed' => [
                '<?php
                    /** @var array{foo: mixed, bar: mixed} $arr */
                    $a = array_pad($arr, 5, null);
                    /** @var mixed $mixed */
                    $b = array_pad([$mixed, $mixed], 5, null);
                    /** @var list $list */
                    $c = array_pad($list, 5, null);
                    /** @var mixed[] $array */
                    $d = array_pad($array, 5, null);',
                'assertions' => [
                    '$a' => 'non-empty-array<int|string, mixed|null>',
                    '$b' => 'non-empty-list<mixed|null>',
                    '$c' => 'non-empty-list<mixed|null>',
                    '$d' => 'non-empty-array<array-key, mixed|null>',
                ],
            ],
            'arrayPadFallback' => [
                '<?php
                    /**
                     * @var mixed $mixed
                     * @psalm-suppress MixedArgument
                     */
                    $result = array_pad($mixed, $mixed, $mixed);',
                'assertions' => [
                    '$result' => 'array<array-key, mixed>',
                ],
            ],
            'arrayChunk' => [
                '<?php
                    /** @var array{a: int, b: int, c: int, d: int} $arr */
                    $a = array_chunk($arr, 2);
                    /** @var list<string> $list */
                    $b = array_chunk($list, 2);
                    /** @var array<string, float> $arr */
                    $c = array_chunk($arr, 2);
                    ',
                'assertions' => [
                    '$a' => 'list<non-empty-list<int>>',
                    '$b' => 'list<non-empty-list<string>>',
                    '$c' => 'list<non-empty-list<float>>',
                ],
            ],
            'arrayChunkPreservedKeys' => [
                '<?php
                    /** @var array{a: int, b: int, c: int, d: int} $arr */
                    $a = array_chunk($arr, 2, true);
                    /** @var list<string> $list */
                    $b = array_chunk($list, 2, true);
                    /** @var array<string, float> $arr */
                    $c = array_chunk($arr, 2, true);',
                'assertions' => [
                    '$a' => 'list<non-empty-array<string, int>>',
                    '$b' => 'list<non-empty-array<int, string>>',
                    '$c' => 'list<non-empty-array<string, float>>',
                ],
            ],
            'arrayChunkPreservedKeysExplicitFalse' => [
                '<?php
                    /** @var array<string, string> $arr */
                    $result = array_chunk($arr, 2, false);',
                'assertions' => [
                    '$result' => 'list<non-empty-list<string>>',
                ],
            ],
            'arrayChunkMixed' => [
                '<?php
                    /** @var array{a: mixed, b: mixed, c: mixed} $arr */
                    $a = array_chunk($arr, 2);
                    /** @var list<mixed> $list */
                    $b = array_chunk($list, 2);
                    /** @var mixed[] $arr */
                    $c = array_chunk($arr, 2);',
                'assertions' => [
                    '$a' => 'list<non-empty-list<mixed>>',
                    '$b' => 'list<non-empty-list<mixed>>',
                    '$c' => 'list<non-empty-list<mixed>>',
                ],
            ],
            'arrayChunkFallback' => [
                '<?php
                    /**
                     * @var mixed $mixed
                     * @psalm-suppress MixedArgument
                     */
                    $result = array_chunk($mixed, $mixed, $mixed);',
                'assertions' => [
                    '$result' => 'list<array<array-key, mixed>>',
                ],
            ],
            'arrayMapPreserveNonEmptiness' => [
                '<?php
                    /**
                     * @psalm-param non-empty-list<string> $strings
                     * @psalm-return non-empty-list<int>
                     */
                    function foo(array $strings): array {
                        return array_map("intval", $strings);
                    }'
            ],
            'arrayMapZip' => [
                '<?php
                    /**
                     * @return array{array{string,string|null}}
                     */
                    function getCharPairs(string $line) : array {
                        $chars = str_split($line);
                        return array_map(
                            null,
                            $chars,
                            array_slice($chars, 1)
                        );
                    }'
            ],
            'arrayFillKeys' => [
                '<?php
                    $keys = [1, 2, 3];
                    $result = array_fill_keys($keys, true);',
                'assertions' => [
                    '$result' => 'array<int, true>',
                ],
            ],
            'shuffle' => [
                '<?php
                    $array = ["foo" => 123, "bar" => 456];
                    shuffle($array);',
                'assertions' => [
                    '$array' => 'list<int>',
                ],
            ],
            'sort' => [
                '<?php
                    $array = ["foo" => 123, "bar" => 456];
                    sort($array);',
                'assertions' => [
                    '$array' => 'list<int>',
                ],
            ],
            'rsort' => [
                '<?php
                    $array = ["foo" => 123, "bar" => 456];
                    sort($array);',
                'assertions' => [
                    '$array' => 'list<int>',
                ],
            ],
            'usort' => [
                '<?php
                    $array = ["foo" => 123, "bar" => 456];
                    usort($array, function (int $a, int $b) { return $a <=> $b; });',
                'assertions' => [
                    '$array' => 'list<int>',
                ],
            ],
            'closureParamConstraintsMet' => [
                '<?php
                    class A {}
                    class B {}

                    $test = [new A(), new B()];

                    usort(
                        $test,
                        /**
                         * @param A|B $a
                         * @param A|B $b
                         */
                        function($a, $b): int
                        {
                            return $a === $b ? 1 : -1;
                        }
                    );'
            ],
            'specialCaseArrayFilterOnSingleEntry' => [
                '<?php
                    /** @psalm-return list<int> */
                    function makeAList(int $ofThisInteger): array {
                        return array_filter([$ofThisInteger]);
                    }'
            ],
            'arrayMapWithEmptyArrayReturn' => [
                '<?php
                    /**
                     * @param array<array<string>> $elements
                     * @return list<string>
                     */
                    function resolvePossibleFilePaths($elements) : array
                    {
                        return array_values(
                            array_filter(
                                array_merge(
                                    ...array_map(
                                        function (array $element) : array {
                                            if (rand(0,1) == 1) {
                                                return [];
                                            }
                                            return $element;
                                        },
                                        $elements
                                    )
                                )
                            )
                        );
                    }'
            ],
            'arrayFilterArrowFunction' => [
                '<?php
                    class A {}
                    class B {}

                    $a = \array_filter(
                        [new A(), new B()],
                        function($x) {
                            return $x instanceof B;
                        }
                    );

                    $b = \array_filter(
                        [new A(), new B()],
                        fn($x) => $x instanceof B
                    );',
                'assertions' => [
                    '$a' => 'array<int, B>',
                    '$b' => 'array<int, B>',
                ],
                'error_levels' => [],
                '7.4',
            ],
            'arrayMergeTwoExplicitLists' => [
                '<?php
                    /**
                     * @param list<int> $foo
                     */
                    function foo(array $foo) : void {}

                    $foo1 = [1, 2, 3];
                    $foo2 = [1, 4, 5];
                    foo(array_merge($foo1, $foo2));'
            ],
            'arrayMergeTwoPossiblyFalse' => [
                '<?php
                    $a = array_merge(
                        glob(__DIR__ . \'/stubs/*.php\'),
                        glob(__DIR__ . \'/stubs/DBAL/*.php\'),
                    );',
                [
                    '$a' => 'list<string>'
                ],
            ],
            'arrayMapPossiblyFalseIgnored' => [
                '<?php
                    function takesString(string $string): void {}

                    $date = new DateTime();

                    $a = [$date->format("Y-m-d")];

                    takesString($a[0]);
                    array_map("takesString", $a);',
            ],
            'arrayMapExplicitZip' => [
                '<?php
                    $as = ["key"];
                    $bs = ["value"];

                    return array_map(fn ($a, $b) => [$a => $b], $as, $bs);',
                'assertions' => [],
                'error_levels' => [],
                '7.4',
            ],
            'allowUnpackWithArrayKey' => [
                '<?php
                    class Foo {
                        protected function one(): array {
                            return [];
                        }

                        protected function two(): array {
                            return [];
                        }

                        public function three(): array {
                            return [...$this->one(), ...$this->two()];
                        }
                    }'
            ],
            'spliceTurnsintKeyedInputToList' => [
                '<?php
                    /**
                     * @psalm-param list<string> $elements
                     * @return list<string>
                     */
                    function bar(array $elements, int $index, string $element) : array {
                        array_splice($elements, $index, 0, [$element]);
                        return $elements;
                    }'
            ],
            'arrayKeyExistsShoudldNotModifyIntType' => [
                '<?php
                    class HttpError {
                        const ERRS = [
                            403 => "a",
                            404 => "b",
                            500 => "c"
                        ];
                    }

                    function init(string $code) : string {
                        if (array_key_exists($code, HttpError::ERRS)) {
                            return $code;
                        }

                        return "";
                    }'
            ],
            'arrayChangeKeyCaseWithNonStringKeys' => [
                '<?php

                $a = [42, "A" => 42];
                echo array_change_key_case($a, CASE_LOWER)[0];'
            ],
            'mapInterfaceMethod' => [
                '<?php
                    interface MapperInterface {
                        public function map(string $s): int;
                    }

                    /**
                     * @param list<string> $strings
                     * @return list<int>
                     */
                    function mapList(MapperInterface $m, array $strings): array {
                        return array_map([$m, "map"], $strings);
                    }'
            ],
            'arrayShiftComplexArray' => [
                '<?php
                    /**
                     * @param list<string> $slugParts
                     */
                    function foo(array $slugParts) : void {
                        if (!$slugParts) {
                            $slugParts = [""];
                        }
                        array_shift($slugParts);
                        if (!empty($slugParts)) {}
                    }'
            ],
            'arrayMergeKeepLastKeysAndType' => [
                '<?php
                    /**
                     * @param array{A: int} $a
                     * @param array<string, string> $b
                     *
                     * @return array{A: int}
                     */
                    function merger(array $a, array $b) : array {
                        return array_merge($b, $a);
                    }'
            ],
            'arrayMergeKeepFirstKeysSameType' => [
                '<?php
                    /**
                     * @param array{A: int} $a
                     * @param array<string, int> $b
                     *
                     * @return array{A: int}
                     */
                    function merger(array $a, array $b) : array {
                        return array_merge($a, $b);
                    }'
            ],
            'filteredArrayCanBeEmpty' => [
                '<?php
                    /**
                      * @return string|null
                      */
                    function thing() {
                        if(rand(0,1) === 1) {
                            return "data";
                        } else {
                            return null;
                        }
                    }
                    $list = [thing(),thing(),thing()];
                    $list = array_filter($list);
                    if (!empty($list)) {}'
            ],
            'arrayShiftOnMixedOrEmptyArray' => [
                '<?php
                    /**
                     * @param mixed|array<empty, empty> $lengths
                     */
                    function doStuff($lengths): void {
                        /** @psalm-suppress MixedArgument, MixedAssignment */
                        $length = array_shift($lengths);
                        if ($length !== null) {}
                    }'
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'arrayFilterWithoutTypes' => [
                '<?php
                    $e = array_filter(
                        ["a" => 5, "b" => 12, "c" => null],
                        function(?int $i) {
                            return $_GET["a"];
                        }
                    );',
                'error_message' => 'MixedArgumentTypeCoercion',
                'error_levels' => ['MissingClosureParamType', 'MissingClosureReturnType'],
            ],
            'arrayFilterUseMethodOnInferrableInt' => [
                '<?php
                    $a = array_filter([1, 2, 3, 4], function ($i) { return $i->foo(); });',
                'error_message' => 'InvalidMethodCall',
            ],
            'arrayMapUseMethodOnInferrableInt' => [
                '<?php
                    $a = array_map(function ($i) { return $i->foo(); }, [1, 2, 3, 4]);',
                'error_message' => 'InvalidMethodCall',
            ],
            'arrayMapWithNonCallableStringArray' => [
                '<?php
                    $foo = ["one", "two"];
                    array_map($foo, ["hello"]);',
                'error_message' => 'InvalidArgument',
            ],
            'arrayMapWithNonCallableIntArray' => [
                '<?php
                    $foo = [1, 2];
                    array_map($foo, ["hello"]);',
                'error_message' => 'InvalidArgument',
            ],
            'arrayFilterBadArgs' => [
                '<?php
                    function foo(int $i) : bool {
                      return true;
                    }

                    array_filter(["hello"], "foo");',
                'error_message' => 'InvalidScalarArgument',
            ],
            'arrayFilterTooFewArgs' => [
                '<?php
                    function foo(int $i, string $s) : bool {
                      return true;
                    }

                    array_filter([1, 2, 3], "foo");',
                'error_message' => 'TooFewArguments',
            ],
            'arrayMapBadArgs' => [
                '<?php
                    function foo(int $i) : bool {
                      return true;
                    }

                    array_map("foo", ["hello"]);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'arrayMapTooFewArgs' => [
                '<?php
                    function foo(int $i, string $s) : bool {
                      return true;
                    }

                    array_map("foo", [1, 2, 3]);',
                'error_message' => 'TooFewArguments',
            ],
            'arrayMapTooManyArgs' => [
                '<?php
                    function foo() : bool {
                      return true;
                    }

                    array_map("foo", [1, 2, 3]);',
                'error_message' => 'TooManyArguments',
            ],
            'arrayReduceInvalidClosureTooFewArgs' => [
                '<?php
                    $arr = [2, 3, 4, 5];

                    $direct_closure_result = array_reduce(
                        $arr,
                        function (int $carry) : int {
                            return 5;
                        },
                        1
                    );',
                'error_message' => 'InvalidArgument',
                'error_levels' => ['MixedTypeCoercion'],
            ],
            'arrayReduceInvalidItemType' => [
                '<?php
                    $arr = [2, 3, 4, 5];

                    $direct_closure_result = array_reduce(
                        $arr,
                        function (int $carry, stdClass $item) {
                            return $_GET["boo"];
                        },
                        1
                    );',
                'error_message' => 'InvalidArgument',
                'error_levels' => ['MissingClosureReturnType'],
            ],
            'arrayReduceInvalidCarryType' => [
                '<?php
                    $arr = [2, 3, 4, 5];

                    $direct_closure_result = array_reduce(
                        $arr,
                        function (stdClass $carry, int $item) {
                            return $_GET["boo"];
                        },
                        1
                    );',
                'error_message' => 'InvalidArgument',
                'error_levels' => ['MissingClosureReturnType'],
            ],
            'arrayReduceInvalidCarryOutputType' => [
                '<?php
                    $arr = [2, 3, 4, 5];

                    $direct_closure_result = array_reduce(
                        $arr,
                        function (int $carry, int $item) : stdClass {
                            return new stdClass;
                        },
                        1
                    );',
                'error_message' => 'InvalidArgument',
            ],
            'arrayPopNotNull' => [
                '<?php
                    function expectsInt(int $a) : void {}

                    /**
                     * @param array<array-key, array{item:int}> $list
                     */
                    function test(array $list) : void
                    {
                        while (!empty($list)) {
                            $tmp = array_pop($list);
                            if ($tmp === null) {}
                        }
                    }',
                'error_message' => 'DocblockTypeContradiction',
            ],
            'usortInvalidCallableString' => [
                '<?php
                    $a = [[1], [2], [3]];
                    usort($a, "strcmp");',
                'error_message' => 'InvalidArgument',
            ],
            'arrayShiftUndefinedVariable' => [
                '<?php
                    /** @psalm-suppress MissingParamType */
                    function foo($data): void {
                        /** @psalm-suppress MixedArgument */
                        array_unshift($data, $a);
                    }',
                'error_message' => 'UndefinedVariable',
            ],
            'arrayFilterObjectLike' => [
                '<?php
                    /** @param list<int> $ints */
                    function ints(array $ints) : void {}
                    $brr = array_filter([2,3,0,4,5]);
                    ints($brr);',
                'error_message' => 'InvalidArgument',
            ],
            'usortOneParamInvalid' => [
                '<?php
                    $list = [3, 2, 5, 9];
                    usort($list, fn(int $a, string $b): int => (int) ($a > $b));',
                'error_message' => 'InvalidScalarArgument',
                2 => [],
                3 => false,
                4 => '7.4',
            ],
            'usortInvalidComparison' => [
                '<?php
                    $arr = [["one"], ["two"], ["three"]];

                    usort(
                        $arr,
                        function (string $a, string $b): int {
                            return strcmp($a, $b);
                        }
                    );',
                'error_message' => 'InvalidArgument',
            ],
            'arrayMergeKeepFirstKeysButNotType' => [
                '<?php
                    /**
                     * @param array{A: int} $a
                     * @param array<string, string> $b
                     *
                     * @return array{A: int}
                     */
                    function merger(array $a, array $b) : array {
                        return array_merge($a, $b);
                    }',
                'error_message' => 'LessSpecificReturnStatement - src' . DIRECTORY_SEPARATOR . 'somefile.php:9:32 - The type \'array{A: int|string}<string, string>\' is more general',
            ],
        ];
    }
}
