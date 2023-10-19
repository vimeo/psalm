<?php

declare(strict_types=1);

namespace Psalm\Tests\TypeReconciliation;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class IssetTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'issetWithSimpleAssignment' => [
                'code' => '<?php
                    $array = [];

                    if (isset($array[$a = 5])) {
                        print "hello";
                    }

                    print $a;',
                'assertions' => [],
                'ignored_issues' => ['EmptyArrayAccess'],
            ],
            'issetWithMultipleAssignments' => [
                'code' => '<?php
                    if (rand(0, 4) > 2) {
                        $arr = [5 => [3 => "hello"]];
                    }

                    if (isset($arr[$a = 5][$b = 3])) {

                    }

                    echo $a;
                    echo $b;',
                'assertions' => [],
                'ignored_issues' => ['MixedArrayAccess'],
            ],
            'isset' => [
                'code' => '<?php
                    $a = isset($b) ? $b : null;',
                'assertions' => [
                    '$a' => 'mixed|null',
                ],
                'ignored_issues' => ['MixedAssignment'],
            ],
            'nullCoalesce' => [
                'code' => '<?php
                    $a = $b ?? null;',
                'assertions' => [
                    '$a' => 'mixed|null',
                ],
                'ignored_issues' => ['MixedAssignment'],
            ],
            'nullCoalesceWithGoodVariable' => [
                'code' => '<?php
                    $b = rand(0, 10) > 5 ? "hello" : null;
                    $a = $b ?? null;',
                'assertions' => [
                    '$a' => 'null|string',
                ],
            ],
            'issetKeyedOffset' => [
                'code' => '<?php
                    function getArray() : array {
                        return [];
                    }

                    $foo = getArray();

                    if (!isset($foo["a"])) {
                        $foo["a"] = "hello";
                    }',
                'assertions' => [
                    '$foo[\'a\']' => 'mixed|string',
                ],
                'ignored_issues' => [],
            ],
            'issetKeyedOffsetORFalse' => [
                'code' => '<?php
                    /** @return void */
                    function takesString(string $str) {}

                    $bar = rand(0, 1) ? ["foo" => "bar"] : false;

                    if (isset($bar["foo"])) {
                        takesString($bar["foo"]);
                    }',
                'assertions' => [],
                'ignored_issues' => ['PossiblyInvalidArrayAccess'],
            ],
            'nullCoalesceKeyedOffset' => [
                'code' => '<?php
                    function getArray() : array {
                        return [];
                    }

                    $foo = getArray();

                    $foo["a"] = $foo["a"] ?? "hello";',
                'assertions' => [
                    '$foo[\'a\']' => 'mixed|string',
                ],
                'ignored_issues' => ['MixedAssignment'],
            ],
            'noRedundantConditionOnMixed' => [
                'code' => '<?php
                    function testarray(array $data): void {
                        foreach ($data as $item) {
                            if (isset($item["a"]) && isset($item["b"]["c"])) {
                                echo "Found\n";
                            }
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment', 'MixedArrayAccess'],
            ],
            'testUnset' => [
                'code' => '<?php
                    $foo = ["a", "b", "c"];
                    foreach ($foo as $bar) {}
                    unset($foo, $bar);

                    function foo(): void {
                        $foo = ["a", "b", "c"];
                        foreach ($foo as $bar) {}
                        unset($foo, $bar);
                    }',
            ],
            'issetTKeyedArray' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    $arr = [[1, 2, 3], null, [1, 2, 3], null];
                    $b = rand(0, 2);
                    $c = rand(0, 2);
                    if (isset($arr[$b][$c])) {
                        echo $arr[$b][$c];
                    }',
            ],
            'issetNonNullArrayKey' => [
                'code' => '<?php
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
            'issetWithCalculatedKeyAndEqualComparison' => [
                'code' => '<?php
                    /** @var array<string, string> $array */
                    $array = [];

                    function sameString(string $string): string {
                        return $string;
                    }

                    if (isset($array[sameString("key")]) === false) {
                        throw new \LogicException("No such key");
                    }
                    $value = $array[sameString("key")];
                    ',
                'assertions' => [
                    '$value' => 'string',
                ],
            ],
            'issetArrayOffsetConditionalCreationWithInt' => [
                'code' => '<?php
                    /** @param array<int, string> $arr */
                    function foo(array $arr) : string {
                        if (!isset($arr[0])) {
                            $arr[0] = "hello";
                        }

                        return $arr[0];
                    }',
            ],
            'issetArrayOffsetConditionalCreationWithVariable' => [
                'code' => '<?php
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
                'code' => '<?php
                    if (isset($foo["bar[]"])) {}',
            ],
            'issetArrayOffsetAndProperty' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    $arr = [];

                    foreach ([1, 2, 3] as $foo) {
                        if (!isset($arr["bar"])) {
                            $arr["bar"] = 0;
                        }

                        echo $arr["bar"];
                    }',
            ],
            'arrayAccessAfterTwoIssets' => [
                'code' => '<?php
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
                'code' => '<?php
                    class Example {
                        const FOO = "foo";
                        /**
                         * @param array{bar:string, ...} $params
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
                    }',
            ],
            'noRedundantConditionAfterIsset' => [
                'code' => '<?php
                    /** @param array<string, array<int, string>> $arr */
                    function foo(array $arr, string $k) : void {
                        if (!isset($arr[$k])) {
                            return;
                        }

                        if ($arr[$k][0]) {}
                    }',
            ],
            'mixedArrayIsset' => [
                'code' => '<?php
                    $a = isset($_GET["a"]) ? $_GET["a"] : "";
                    if ($a) {}',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment', 'MixedArrayAccess'],
            ],
            'mixedArrayIssetGetStringVar' => [
                'code' => '<?php
                    if (isset($_GET["b"]) && is_string($_GET["b"])) {
                        echo $_GET["b"];
                    }',
            ],
            'regularArrayAccessInLoopAfterIsset' => [
                'code' => '<?php
                    $arr = [];
                    while (rand(0, 1)) {
                        if (!isset($arr["a"]["b"])) {
                            $arr["a"]["b"] = "foo";
                        }
                        echo $arr["a"]["b"];
                    }',
            ],
            'conditionalArrayAccessInLoopAfterIssetWithAltAssignment' => [
                'code' => '<?php
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
                    }',
            ],
            'issetVarInLoopBeforeAssignment' => [
                'code' => '<?php
                    function foo() : void {
                        while (rand(0, 1)) {
                            if (!isset($foo)) {
                                $foo = 1;
                            }
                        }
                    }',
            ],
            'issetOnArrayAccess' => [
                'code' => '<?php
                    function foo(ArrayAccess $arr) : void {
                        $a = isset($arr["a"]) ? $arr["a"] : 4;
                        takesInt($a);
                    }
                    function takesInt(int $i) : void {}',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment', 'MixedArgument'],
            ],
            'noParadoxOnMultipleNotIssets' => [
                'code' => '<?php
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
                    }',
            ],
            'notIssetOneOrOtherSimple' => [
                'code' => '<?php
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
                'ignored_issues' => ['PossiblyNullReference'],
            ],
            'notIssetOneOrOtherWithoutAssert' => [
                'code' => '<?php
                    $foo = [
                        "one" => rand(0,1) ? new DateTime : null,
                        "two" => rand(0,1) ? new DateTime : null,
                        "three" => new DateTime
                    ];

                    $a = isset($foo["one"]) || isset($foo["two"]);

                    echo $foo["one"]->format("Y");',
                'assertions' => [],
                'ignored_issues' => ['PossiblyNullReference'],
            ],
            'notIssetOneOrOtherWithAssert' => [
                'code' => '<?php
                    $foo = [
                        "one" => rand(0,1) ? new DateTime : null,
                        "two" => rand(0,1) ? new DateTime : null,
                        "three" => new DateTime
                    ];

                    assert(isset($foo["one"]) || isset($foo["two"]));

                    echo $foo["one"]->format("Y");',
                'assertions' => [],
                'ignored_issues' => ['PossiblyNullReference'],
            ],
            'assertArrayAfterIssetStringOffset' => [
                'code' => '<?php
                    /**
                     * @param string|array $a
                     */
                    function _renderInput($a) : array {
                        if (isset($a["foo"], $a["bar"])) {
                            return $a;
                        }

                        return [];
                    }',
            ],
            'assertMoreComplicatedArrayAfterIssetStringOffset' => [
                'code' => '<?php
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
                'ignored_issues' => ['MixedAssignment'],
            ],
            'assertAfterIsset' => [
                'code' => '<?php
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
                'ignored_issues' => ['MixedAssignment'],
            ],
            'noCrashAfterIsset' => [
                'code' => '<?php
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
                'ignored_issues' => ['MixedAssignment', 'MixedArrayOffset', 'InvalidArrayOffset'],
            ],
            'sessionNullCoalesce' => [
                'code' => '<?php
                    $a = $_SESSION ?? [];',
            ],
            'sessionIssetNull' => [
                'code' => '<?php
                    $a = isset($_SESSION) ? $_SESSION : [];',
            ],
            'issetSeparateNegated' => [
                'code' => '<?php
                    function foo(?string $a, ?string $b): string {
                        if (!isset($a) || !isset($b)) {
                            return "";
                        }
                        return $a . $b;
                    }',
            ],
            'issetMultipleNegated' => [
                'code' => '<?php
                    function foo(?string $a, ?string $b): string {
                        if (!isset($a, $b)) {
                            return "";
                        }
                        return $a . $b;
                    }',
            ],
            'issetMultipleNegatedWithExtraClause' => [
                'code' => '<?php
                    function foo(?string $a, ?string $b): string {
                        if (!(isset($a, $b) && rand(0, 1))) {
                            return "";
                        }
                        return $a . $b;
                    }',
            ],
            'issetMultipleNotNegated' => [
                'code' => '<?php
                    function foo(?string $a, ?string $b): string {
                        if (isset($a, $b)) {
                            return $a . $b;
                        }

                        return "";
                    }',
            ],
            'issetNotIssetTest' => [
                'code' => '<?php
                    class B {
                        /** @var string */
                        public $c = "hello";
                    }

                    function foo(array $a, B $b, string $s): void {
                        if ($s !== "bar" && !isset($a[$b->c])) {
                            return;
                        }

                        if ($s !== "bar" && isset($a[$b->c])) {
                            // do something
                        } else {
                            // something else
                        }
                    }',
            ],
            'issetOnNestedObjectlikeOneLevel' => [
                'code' => '<?php
                    /**
                     * @param array{a:array} $array
                     * @return array{a:array{b:mixed, ...}, ...}
                     * @throw \LogicException
                     */
                    function level3($array) {
                        if (!isset($array["a"]["b"])) {
                            throw new \LogicException();
                        }
                        return $array;
                    }',
            ],
            'issetOnStringArrayShouldInformArrayness' => [
                'code' => '<?php
                    /**
                     * @param string[] $a
                     * @return array{b: string, ...}
                     */
                    function foo(array $a) {
                        if (isset($a["b"])) {
                            return $a;
                        }

                        throw new \Exception("bad");
                    }',
            ],

            'issetOnArrayTwice' => [
                'code' => '<?php
                    function foo(array $options): void {
                        if (!isset($options["a"])) {
                            $options["a"] = "hello";
                        }

                        if (!isset($options["b"])) {
                            $options["b"] = 1;
                        }

                        if ($options["b"] === 2) {}
                    }',
            ],
            'listDestructuringErrorSuppress' => [
                'code' => '<?php
                    function foo(string $s) : void {
                        @list(, $port) = explode(":", $s);
                        echo isset($port) ? "cool" : "uncool";
                    }',
            ],
            'listDestructuringErrorSuppressWithFirstString' => [
                'code' => '<?php
                    function foo(string $s) : string {
                        @list($port, $starboard) = explode(":", $s);
                        return $port;
                    }',
            ],
            'accessAfterArrayExistsVariable' => [
                'code' => '<?php
                    abstract class P {
                        const MAP = [
                            A::class => 1,
                            B::class => 2,
                            C::class => 3,
                        ];

                        public function foo(string $s) : int {
                            $a = static::class;
                            if (!isset(self::MAP[$a])) {
                                throw new \Exception("bad");
                            }
                            return self::MAP[$a];
                        }
                    }

                    class A extends P {}
                    class B extends P {}
                    class C extends P {}',
            ],
            'accessAfterArrayExistsStaticClass' => [
                'code' => '<?php
                    abstract class P {
                        const MAP = [
                            A::class => 1,
                            B::class => 2,
                            C::class => 3,
                        ];

                        public function foo(string $s) : int {
                            if (!isset(self::MAP[static::class])) {
                                throw new \Exception("bad");
                            }
                            return self::MAP[static::class];
                        }
                    }

                    class A extends P {}
                    class B extends P {}
                    class C extends P {}',
            ],
            'issetCreateTKeyedArrayWithType' => [
                'code' => '<?php
                    function foo(array $options): void {
                        if (isset($options["a"])) {
                            $options["b"] = "hello";
                        }

                        if (\is_array($options["b"])) {}
                    }',
            ],
            'issetOnThing' => [
                'code' => '<?php
                    function foo() : void {
                        $p = [false, false];
                        $i = rand(0, 1);
                        if (rand(0, 1) && isset($p[$i])) {
                            $p[$i] = true;
                        }

                        foreach ($p as $q) {
                            if ($q) {}
                        }
                    }',
            ],
            'issetOnNullableObjectWithNullCoalesce' => [
                'code' => '<?php
                    class A {
                        public bool $s = true;
                    }
                    function foo(?A $a) : string {
                        if (rand(0, 1) && !($a->s ?? false)) {
                            return "foo";
                        }
                        return "bar";
                    }',
            ],
            'issetOnNullableObjectWithIsset' => [
                'code' => '<?php
                    class A {
                        public bool $s = true;
                    }
                    function foo(?A $a) : string {
                        if (rand(0, 1) && !(isset($a->s) ? $a->s : false)) {
                            return "foo";
                        }
                        return "bar";
                    }',
            ],
            'issetOnMethodCallInsideFunctionCall' => [
                'code' => '<?php
                    class C {
                        public function foo() : ?string {
                            return null;
                        }
                    }

                    function foo(C $c) : void {
                        strlen($c->foo() ?? "");
                    }',
            ],
            'issetOnMethodCallInsideMethodCall' => [
                'code' => '<?php
                    class C {
                        public function foo() : ?string {
                            return null;
                        }
                    }

                    function foo(C $c) : void {
                        new DateTime($c->foo() ?? "");
                    }',
            ],
            'methodCallAfterIsset' => [
                'code' => '<?php
                    class B {
                        public function bar() : void {}
                    }

                    /** @psalm-suppress MissingConstructor */
                    class A {
                        /** @var B */
                        public $foo;

                        public function init() : void {
                            /** @psalm-suppress RedundantPropertyInitializationCheck */
                            if (isset($this->foo)) {
                                return;
                            }

                            if (rand(0, 1)) {
                                $this->foo = new B;
                            } else {
                                $this->foo = new B;
                            }

                            $this->foo->bar();
                        }
                    }',
            ],
            'issetOnArrayOfArraysReturningStringInElse' => [
                'code' => '<?php
                    function foo(int $i) : string {
                        /** @var array<int, array<string, string>> */
                        $tokens = [];

                        if (isset($tokens[$i]["a"])) {
                            return "hello";
                        } else {
                            return $tokens[$i]["b"];
                        }
                    }',
            ],
            'issetOnArrayOfObjectsAssertingOnIssetValue' => [
                'code' => '<?php
                    class A {
                        public ?string $name = null;
                    }

                    function foo(int $i) : void {
                        /** @var array<int, A> */
                        $tokens = [];

                        if (isset($tokens[$i]->name) && $tokens[$i]->name === "hello") {}
                    }',
            ],
            'issetOnArrayOfObjectsAssertingOnNotIssetValue' => [
                'code' => '<?php
                    class A {
                        public ?string $name = null;
                    }

                    function foo(int $i) : void {
                        /** @var array<int, A> */
                        $tokens = [];

                        if (!isset($tokens[$i])) {
                            if (rand(0, 1)) {
                                if (rand(0, 1)) {
                                    $tokens[$i] = new A();
                                } else {
                                    return;
                                }
                            } else {
                                return;
                            }
                        }

                        echo $tokens[$i]->name;
                    }',
            ],
            'issetOnArrayOfMixed' => [
                'code' => '<?php
                    /**
                     * @psalm-suppress MixedArrayAccess
                     * @psalm-suppress MixedArgument
                     */
                    function foo(int $i) : void {
                        /** @var array */
                        $tokens = [];

                        if (!isset($tokens[$i]["a"])) {
                            echo $tokens[$i]["b"];
                        }
                    }',
            ],
            'issetOnArrayOfArrays' => [
                'code' => '<?php
                    /**
                     * @psalm-suppress MixedArgument
                     */
                    function foo(int $i) : void {
                        /** @var array<array> */
                        $tokens = [];

                        if (!isset($tokens[$i]["a"])) {
                            echo $tokens[$i]["b"];
                        }
                    }',
            ],
            'issetOnArrayOfArrayOfStrings' => [
                'code' => '<?php
                    function foo(int $i) : void {
                        /** @var array<int, array<string, string>> */
                        $tokens = [];

                        if (!isset($tokens[$i]["a"])) {
                            echo $tokens[$i]["b"];
                        }
                    }',
            ],
            'noMixedMethodCallAfterIsset' => [
                'code' => '<?php
                    $data = file_get_contents("php://input");
                    /** @psalm-suppress MixedAssignment */
                    $payload = json_decode($data, true);

                    if (!isset($payload["a"]) || rand(0, 1)) {
                        return;
                    }

                    /**
                     * @psalm-suppress MixedArgument
                     */
                    echo $payload["b"];',
            ],
            'implicitIssetWithStringKeyOnArrayDoesntChangeArrayType' => [
                'code' => '<?php
                    class A {}

                    function run1(array $arguments): void {
                        if ($arguments["a"] instanceof A) {}

                        if ($arguments["b"]) {
                            /** @psalm-suppress MixedArgument */
                            echo $arguments["b"];
                        }
                    }',
            ],
            'issetOnClassConstantOffset' => [
                'code' => '<?php

                    final class StudyJwtPayload {
                        public const STUDY_ID = "studid";

                        public static function fromClaims(array $claims): string
                        {
                            if (!isset($claims["usrid"])) {
                                throw new \InvalidArgumentException();
                            }

                            if (!\is_string($claims["usrid"])) {
                                throw new \InvalidArgumentException();
                            }

                            if (!isset($claims[self::STUDY_ID])) {
                                throw new \InvalidArgumentException();
                            }

                            if (!\is_string($claims[self::STUDY_ID])) {
                                throw new \InvalidArgumentException();
                            }

                            return $claims[self::STUDY_ID];
                        }
                    }',
            ],
            'noCrashAfterTwoIsset' => [
                'code' => '<?php
                    /** @psalm-suppress MixedArrayOffset */
                    function foo(array $a, array $b) : void {
                        if (! isset($b["id"], $a[$b["id"]])) {
                            echo "z";
                        }
                    }',
            ],
            'assertOnPossiblyDefined' => [
                'code' => '<?php
                    function crashes(): void {
                        if (rand(0,1)) {
                            $dt = new \DateTime;
                        }
                        /**
                         * @psalm-suppress PossiblyUndefinedVariable
                         * @psalm-suppress MixedArgument
                         */
                        assert($dt);
                    }',
            ],
            'issetOnNullableMixed' => [
                'code' => '<?php
                    function processParam(mixed $param) : void {
                        if (rand(0, 1)) {
                            $param = null;
                        }

                        if (isset($param["name"])) {
                            /**
                             * @psalm-suppress MixedArgument
                             */
                            echo $param["name"];
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'assertComplex' => [
                'code' => '<?php
                    function returnsInt(?int $a, ?int $b): int {
                        assert($a !== null || $b !== null);
                        return isset($a) ? $a : $b;
                    }',
            ],
            'assertComplexWithNullCoalesce' => [
                'code' => '<?php
                    function returnsInt(?int $a, ?int $b): int {
                        assert($a !== null || $b !== null);
                        return $a ?? $b;
                    }',
            ],
            'nullCoalesceSimpleArrayOffset' => [
                'code' => '<?php
                    function a(array $arr) : void {
                        /** @psalm-suppress MixedArgument */
                        echo isset($arr["a"]["b"]) ? $arr["a"]["b"] : 0;
                    }

                    function b(array $arr) : void {
                        /** @psalm-suppress MixedArgument */
                        echo $arr["a"]["b"] ?? 0;
                    }',
            ],
            'coalescePreserveContext' => [
                'code' => '<?php
                    function foo(array $test) : void {
                        /** @psalm-suppress MixedArgument */
                        echo $test[0] ?? ( $test[0] = 1 );
                        /** @psalm-suppress MixedArgument */
                        echo $test[0];
                    }',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'complainAboutBadCallInIsset' => [
                'code' => '<?php
                    class A {}
                    $a = isset(A::foo()[0]);',
                'error_message' => 'UndefinedMethod',
            ],
            'issetVariableKeysWithChange' => [
                'code' => '<?php
                    $arr = [[1, 2, 3], null, [1, 2, 3], null];
                    $b = 2;
                    $c = 0;
                    if (isset($arr[$b][$c])) {
                        $b = 1;
                        echo $arr[$b][$c];
                    }',
                'error_message' => 'NullArrayAccess',
            ],
            'issetAdditionalVarWithSealedTKeyedArray' => [
                'code' => '<?php
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
            'undefinedVarInNullCoalesce' => [
                'code' => '<?php
                    function bar(): void {
                        $do_baz = $config["do_it"] ?? false;
                        if ($do_baz) {
                            baz();
                        }
                    }',
                'error_message' => 'UndefinedVariable',
            ],
            'issetNullVar' => [
                'code' => '<?php
                    function four(?string $s) : void {
                        if ($s === null) {
                            if (isset($s)) {}
                        }
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'stringIsAlwaysSet' => [
                'code' => '<?php
                    function foo(string $s) : string {
                        if (!isset($s)) {
                            return "foo";
                        }
                        return "bar";
                    }',
                'error_message' => 'TypeDoesNotContainNull',
            ],
            'issetOnArrayOfArraysReturningString' => [
                'code' => '<?php
                    function foo(int $i) : ?string {
                        /** @var array<array> */
                        $tokens = [];

                        if (!isset($tokens[$i]["a"])) {
                            return $tokens[$i]["a"];
                        }

                        return "hello";
                    }',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'accessAfterIssetCheckOnFalsableArray' => [
                'code' => '<?php
                    /**
                     * @return array{b?: string}|false
                     */
                    function returnPossiblyFalseArray() {
                        return rand(0, 1) ? false : (rand(0, 1) ? ["b" => "hello"] : []);
                    }

                    function foo() : void {
                        $arr = returnPossiblyFalseArray();
                        /** @psalm-suppress PossiblyInvalidArrayAccess */
                        if (!isset($arr["b"])) {}
                        echo $arr["b"];
                    }',
                'error_message' => 'PossiblyInvalidArrayAccess',
            ],
            'issetOnStaticProperty' => [
                'code' => '<?php
                    class Singleton {
                        private static self $instance;
                        public function getInstance(): self {
                            if (isset(self::$instance)) {
                                return self::$instance;
                            }
                            return self::$instance = new self();
                        }
                        private function __construct() {}
                    }',
                'error_message' => 'RedundantPropertyInitializationCheck',
            ],
            'negatedIssetOnStaticProperty' => [
                'code' => '<?php
                    class Singleton {
                        private static self $instance;
                        public function getInstance(): self {
                            if (!isset(self::$instance)) {
                                self::$instance = new self();
                            }
                            return self::$instance;
                        }
                        private function __construct() {}
                    }',
                'error_message' => 'RedundantPropertyInitializationCheck',
            ],
            'setArbitraryListElementAfterIsset' => [
                'code' => '<?php
                    /** @param list<string> $list */
                    function foo(array &$list, int $offset): void {
                        if (isset($list[$offset])) {}
                        $list[$offset] = "";
                    }',
                'error_message' => 'ReferenceConstraintViolation',
            ],
            'setArbitraryListWithinNotIsset' => [
                'code' => '<?php
                    /** @param list<string> $list */
                    function foo(array &$list, int $offset): void {
                        if (!isset($list[$offset])) {
                            $list[$offset] = "";
                        }
                    }',
                'error_message' => 'ReferenceConstraintViolation',
            ],
        ];
    }
}
