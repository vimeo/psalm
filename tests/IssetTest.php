<?php
namespace Psalm\Tests;

class IssetTest extends TestCase
{
    use Traits\ValidCodeAnalysisTestTrait;
    use Traits\InvalidCodeAnalysisTestTrait;

    /**
     * @return array
     */
    public function providerValidCodeParse()
    {
        return [
            'issetWithSimpleAssignment' => [
                '<?php
                    $array = [];

                    if (isset($array[$a = 5])) {
                        print "hello";
                    }

                    print $a;',
                'assertions' => [],
                'error_levels' => ['EmptyArrayAccess'],
            ],
            'issetWithMultipleAssignments' => [
                '<?php
                    if (rand(0, 4) > 2) {
                        $arr = [5 => [3 => "hello"]];
                    }

                    if (isset($arr[$a = 5][$b = 3])) {

                    }

                    echo $a;
                    echo $b;',
                'assertions' => [],
                'error_levels' => ['MixedArrayAccess'],
            ],
            'isset' => [
                '<?php
                    $a = isset($b) ? $b : null;',
                'assertions' => [
                    '$a' => 'mixed|null',
                ],
                'error_levels' => ['MixedAssignment'],
            ],
            'nullCoalesce' => [
                '<?php
                    $a = $b ?? null;',
                'assertions' => [
                    '$a' => 'mixed|null',
                ],
                'error_levels' => ['MixedAssignment'],
            ],
            'nullCoalesceWithGoodVariable' => [
                '<?php
                    $b = rand(0, 10) > 5 ? "hello" : null;
                    $a = $b ?? null;',
                'assertions' => [
                    '$a' => 'string|null',
                ],
            ],
            'issetKeyedOffset' => [
                '<?php
                    function getArray() : array {
                        return [];
                    }

                    $foo = getArray();

                    if (!isset($foo["a"])) {
                        $foo["a"] = "hello";
                    }',
                'assertions' => [
                    '$foo[\'a\']' => 'string|mixed',
                ],
                'error_levels' => [],
            ],
            'issetKeyedOffsetORFalse' => [
                '<?php
                    /** @return void */
                    function takesString(string $str) {}

                    $bar = rand(0, 1) ? ["foo" => "bar"] : false;

                    if (isset($bar["foo"])) {
                        takesString($bar["foo"]);
                    }',
                'assertions' => [],
                'error_levels' => ['PossiblyInvalidArrayAccess'],
            ],
            'nullCoalesceKeyedOffset' => [
                '<?php
                    function getArray() : array {
                        return [];
                    }

                    $foo = getArray();

                    $foo["a"] = $foo["a"] ?? "hello";',
                'assertions' => [
                    '$foo[\'a\']' => 'string|mixed',
                ],
                'error_levels' => ['MixedAssignment'],
            ],
            'noRedundantConditionOnMixed' => [
                '<?php
                    function testarray(array $data): void {
                        foreach ($data as $item) {
                            if (isset($item["a"]) && isset($item["b"]["c"])) {
                                echo "Found\n";
                            }
                        }
                    }',
                'assertions' => [],
                'error_levels' => ['MixedAssignment', 'MixedArrayAccess'],
            ],
            'testUnset' => [
                '<?php
                    $foo = ["a", "b", "c"];
                    foreach ($foo as $bar) {}
                    unset($foo, $bar);

                    function foo(): void {
                        $foo = ["a", "b", "c"];
                        foreach ($foo as $bar) {}
                        unset($foo, $bar);
                    }',
            ],
            'issetObjectLike' => [
                '<?php
                    $arr = [
                        "profile" => [
                            "foo" => "bar",
                        ],
                        "groups" => [
                            "foo" => "bar",
                            "hide"  => rand() % 2 > 0,
                        ],
                    ];

                    foreach ($arr as $item) {
                        if (!isset($item["hide"]) || !$item["hide"]) {}
                    }',
            ],
            'issetPropertyAffirmsObject' => [
                '<?php
                    class A {
                        /** @var ?int */
                        public $id;
                    }

                    function takesA(?A $a): A {
                        if (isset($a->id)) {
                            return $a;
                        }

                        return new A();
                    }',
            ],
            'issetVariableKeysWithoutChange' => [
                '<?php
                    $arr = [[1, 2, 3], null, [1, 2, 3], null];
                    $b = 2;
                    $c = 0;
                    if (isset($arr[$b][$c])) {
                        echo $arr[$b][$c];
                    }',
            ],
            'issetNonNullArrayKey' => [
                '<?php
                    /**
                     * @param  array<int, int> $arr
                     */
                    function foo(array $arr) : int {
                        $b = rand(0, 3);
                        if (!isset($arr[$b])) {
                            throw new \Exception("bad");
                        }
                        return $arr[$b];
                    }',
            ],
            'issetArrayOffsetConditionalCreationWithInt' => [
                '<?php
                    /** @param array<int, string> $arr */
                    function foo(array $arr) : string {
                        if (!isset($arr[0])) {
                            $arr[0] = "hello";
                        }

                        return $arr[0];
                    }',
            ],
            'issetArrayOffsetConditionalCreationWithVariable' => [
                '<?php
                    /** @param array<int, string> $arr */
                    function foo(array $arr) : string {
                        $b = 5;

                        if (!isset($arr[$b])) {
                            $arr[$b] = "hello";
                        }

                        return $arr[$b];
                    }',
            ],
            'noExceptionOnBracketString' => [
                '<?php
                    if (isset($foo["bar[]"])) {}',
            ],
            'issetArrayOffsetAndProperty' => [
                '<?php
                    class A {
                        /** @var ?B */
                        public $b;
                    }
                    class B {}

                    /**
                     * @param A[] $arr
                     */
                    function takesAList(array $arr) : B {
                        if (isset($arr[1]->b)) {
                            return $arr[1]->b;
                        }
                        throw new \Exception("bad");
                    }',
            ],
            'allowUnknownAdditionToInt' => [
                '<?php
                    $arr = [1, 1, 1, 1, 2, 5, 3, 2];
                    $cumulative = [];

                    foreach ($arr as $val) {
                        if (isset($cumulative[$val])) {
                            $cumulative[$val] = $cumulative[$val] + 1;
                        } else {
                            $cumulative[$val] = 1;
                        }
                    }',
            ],
            'allowUnknownArrayMergeToInt' => [
                '<?php
                    $arr = [1, 1, 1, 1, 2, 5, 3, 2];
                    $cumulative = [];

                    foreach ($arr as $val) {
                        if (isset($cumulative[$val])) {
                            $cumulative[$val] = array_merge($cumulative[$val], [$val]);
                        } else {
                            $cumulative[$val] = [$val];
                        }
                    }

                    foreach ($cumulative as $arr) {
                        foreach ($arr as $val) {
                            takesInt($val);
                        }
                    }

                    function takesInt(int $i) : void {}',
            ],
            'returnArrayWithDefinedKeys' => [
                '<?php
                    /**
                     * @param array{bar?: int, foo: int|string} $arr
                     * @return array{bar: int, foo: string}|null
                     */
                    function foo(array $arr) : ?array {
                        if (!isset($arr["bar"])) {
                            return null;
                        }

                        if (is_int($arr["foo"])) {
                            return null;
                        }

                        return $arr;
                    }',
            ],
            'arrayAccessAfterOneIsset' => [
                '<?php
                    $arr = [];

                    foreach ([1, 2, 3] as $foo) {
                        if (!isset($arr["bar"])) {
                            $arr["bar"] = 0;
                        }

                        echo $arr["bar"];
                    }',
            ],
            'arrayAccessAfterTwoIssets' => [
                '<?php
                    $arr = [];

                    foreach ([1, 2, 3] as $foo) {
                        if (!isset($arr["foo"])) {
                            $arr["foo"] = 0;
                        }

                        if (!isset($arr["bar"])) {
                            $arr["bar"] = 0;
                        }

                        echo $arr["bar"];
                    }',
            ],
            'issetAdditionalVar' => [
                '<?php
                    class Example {
                        const FOO = "foo";
                        /**
                         * @param array{bar:string} $params
                         */
                        public function test(array $params) : bool {
                            if (isset($params[self::FOO])) {
                                return true;
                            }

                            if (isset($params["bat"])) {
                                return true;
                            }

                            return false;
                        }
                    }'
            ],
            'noRedundantConditionAfterIsset' => [
                '<?php
                    /** @param array<string, array<int, string>> $arr */
                    function foo(array $arr, string $k) : void {
                        if (!isset($arr[$k])) {
                            return;
                        }

                        if ($arr[$k][0]) {}
                    }',
            ],
            'mixedArrayIsset' => [
                '<?php
                    $a = isset($_GET["a"]) ? $_GET["a"] : "";
                    if ($a) {}',
                'assertions' => [],
                'error_levels' => ['MixedAssignment', 'MixedArrayAccess'],
            ],
            'mixedArrayIssetGetStringVar' => [
                '<?php
                    if (isset($_GET["b"]) && is_string($_GET["b"])) {
                        echo $_GET["b"];
                    }',
            ],
            'nestedArrayAccessInLoopAfterIsset' => [
                '<?php
                    $arr = [];
                    while (rand(0, 1)) {
                        if (rand(0, 1)) {
                            if (!isset($arr["a"]["b"])) {
                                $arr["a"]["b"] = "foo";
                            }
                            echo $arr["a"]["b"];
                        } else {
                            $arr["c"] = "foo";
                        }
                    }'
            ],
            'issetVarInLoopBeforeAssignment' => [
                '<?php
                    function foo() : void {
                        while (rand(0, 1)) {
                            if (!isset($foo)) {
                                $foo = 1;
                            }
                        }
                    }',
            ],
            'issetOnArrayAccess' => [
                '<?php
                    function foo(ArrayAccess $arr) : void {
                        $a = isset($arr["a"]) ? $arr["a"] : "a";
                        takesInt($a);
                    }
                    function takesInt(int $i) : void {}',
                'assertions' => [],
                'error_levels' => ['MixedAssignment', 'MixedArgument'],
            ],
            'noParadoxOnMultipleNotIssets' => [
                '<?php
                    /** @var array */
                    $array = [];
                    function sameString(string $string): string {
                        return $string;
                    }

                    if (isset($array[sameString("key1")]) || isset($array[sameString("key2")])) {
                        throw new \InvalidArgumentException();
                    }

                    if (!isset($array[sameString("key3")]) || !isset($array[sameString("key4")])) {
                        throw new \InvalidArgumentException();
                    }'
            ],
            'notIssetOneOrOther' => [
                '<?php
                    $foo = [
                        "one" => rand(0,1) ? new DateTime : null,
                        "two" => rand(0,1) ? new DateTime : null,
                        "three" => new DateTime
                    ];

                    if (!(isset($foo["one"]) || isset($foo["two"]))) {
                        exit;
                    }

                    echo $foo["one"]->format("Y");',
                'assertions' => [],
                'error_levels' => ['PossiblyNullReference'],
            ],
            'notIssetOneOrOtherWithoutAssert' => [
                '<?php
                    $foo = [
                        "one" => rand(0,1) ? new DateTime : null,
                        "two" => rand(0,1) ? new DateTime : null,
                        "three" => new DateTime
                    ];

                    isset($foo["one"]) || isset($foo["two"]);

                    echo $foo["one"]->format("Y");',
                'assertions' => [],
                'error_levels' => ['PossiblyNullReference'],
            ],
            'notIssetOneOrOtherWithAssert' => [
                '<?php
                    $foo = [
                        "one" => rand(0,1) ? new DateTime : null,
                        "two" => rand(0,1) ? new DateTime : null,
                        "three" => new DateTime
                    ];

                    assert(isset($foo["one"]) || isset($foo["two"]));

                    echo $foo["one"]->format("Y");',
                'assertions' => [],
                'error_levels' => ['PossiblyNullReference'],
            ],
            'assertArrayAfterIssetStringOffset' => [
                '<?php
                    /**
                     * @param string|array $a
                     */
                    function _renderInput($a) : array {
                        if (isset($a["foo"], $a["bar"])) {
                            return $a;
                        }

                        return [];
                    }'
            ],
            'assertMoreComplicatedArrayAfterIssetStringOffset' => [
                '<?php
                    /**
                     * @param string|int $val
                     * @param string|array $text
                     * @param array $data
                     */
                    function _renderInput($val, $text, $data) : array {
                        if (is_int($val) && isset($text["foo"], $text["bar"])) {
                            $radio = $text;
                        } else {
                            $radio = ["value" => $val, "text" => $text];
                        }
                        return $radio;
                    }',
                'assertions' => [],
                'error_levels' => ['MixedAssignment'],
            ],
            'assertAfterIsset' => [
                '<?php
                    /**
                     * @param mixed $arr
                     */
                    function foo($arr) : void {
                        if (empty($arr)) {
                            return;
                        }

                        if (isset($arr["a"]) && isset($arr["b"])) {}
                    }',
                'assertions' => [],
                'error_levels' => ['MixedAssignment'],
            ],
            'noCrashAfterIsset' => [
                '<?php
                    /**
                     * @param string[] $columns
                     * @param mixed[]  $options
                     */
                    function foo(array $columns, array $options) : void {
                        $arr = $options["b"];

                        foreach ($arr as $a) {
                            if (isset($columns[$a]["c"])) {
                                return;
                            }
                        }
                    }',
                'assertions' => [],
                'error_levels' => ['MixedAssignment', 'MixedArrayOffset', 'InvalidArrayOffset'],
            ],
            'sessionNullCoalesce' => [
                '<?php
                    $a = $_SESSION ?? [];'
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerInvalidCodeParse()
    {
        return [
            'complainAboutBadCallInIsset' => [
                '<?php
                    class A {}
                    $a = isset(A::foo()[0]);',
                'error_message' => 'UndefinedMethod',
            ],
            'issetVariableKeysWithChange' => [
                '<?php
                    $arr = [[1, 2, 3], null, [1, 2, 3], null];
                    $b = 2;
                    $c = 0;
                    if (isset($arr[$b][$c])) {
                        $b = 1;
                        echo $arr[$b][$c];
                    }',
                'error_message' => 'NullArrayAccess',
            ],
            'issetAdditionalVarWithSealedObjectLike' => [
                '<?php
                    class Example {
                        const FOO = "foo";
                        public function test() : bool {
                            $params = ["bar" => "bat"];

                            if (isset($params[self::FOO])) {
                                return true;
                            }

                            return false;
                        }
                    }',
                'error_message' => 'InvalidArrayOffset',
            ],
        ];
    }
}
