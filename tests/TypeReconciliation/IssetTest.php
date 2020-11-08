<?php
namespace Psalm\Tests\TypeReconciliation;

class IssetTest extends \Psalm\Tests\TestCase
{
    use \Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;
    use \Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
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
                    '$a' => 'null|string',
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
                    '$foo[\'a\']' => 'mixed|string',
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
                    '$foo[\'a\']' => 'mixed|string',
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
            'issetTKeyedArray' => [
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
                    $b = rand(0, 2);
                    $c = rand(0, 2);
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
                    }',
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
            'regularArrayAccessInLoopAfterIsset' => [
                '<?php
                    $arr = [];
                    while (rand(0, 1)) {
                        if (!isset($arr["a"]["b"])) {
                            $arr["a"]["b"] = "foo";
                        }
                        echo $arr["a"]["b"];
                    }',
            ],
            'conditionalArrayAccessInLoopAfterIssetWithAltAssignment' => [
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
                    }',
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
                        $a = isset($arr["a"]) ? $arr["a"] : 4;
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
                    }',
            ],
            'notIssetOneOrOtherSimple' => [
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

                    $a = isset($foo["one"]) || isset($foo["two"]);

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
                    }',
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
                    $a = $_SESSION ?? [];',
            ],
            'sessionIssetNull' => [
                '<?php
                    $a = isset($_SESSION) ? $_SESSION : [];',
            ],
            'issetSeparateNegated' => [
                '<?php
                    function foo(?string $a, ?string $b): string {
                        if (!isset($a) || !isset($b)) {
                            return "";
                        }
                        return $a . $b;
                    }',
            ],
            'issetMultipleNegated' => [
                '<?php
                    function foo(?string $a, ?string $b): string {
                        if (!isset($a, $b)) {
                            return "";
                        }
                        return $a . $b;
                    }',
            ],
            'issetMultipleNegatedWithExtraClause' => [
                '<?php
                    function foo(?string $a, ?string $b): string {
                        if (!(isset($a, $b) && rand(0, 1))) {
                            return "";
                        }
                        return $a . $b;
                    }',
            ],
            'issetMultipleNotNegated' => [
                '<?php
                    function foo(?string $a, ?string $b): string {
                        if (isset($a, $b)) {
                            return $a . $b;
                        }

                        return "";
                    }',
            ],
            'issetNotIssetTest' => [
                '<?php
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
                '<?php
                    /**
                     * @param array{a:array} $array
                     * @return array{a:array{b:mixed}}
                     * @throw \LogicException
                     */
                    function level3($array) {
                        if (!isset($array["a"]["b"])) {
                            throw new \LogicException();
                        }
                        return $array;
                    }'
            ],
            'issetOnStringArrayShouldInformArrayness' => [
                '<?php
                    /**
                     * @param string[] $a
                     * @return array{b: string}
                     */
                    function foo(array $a) {
                        if (isset($a["b"])) {
                            return $a;
                        }

                        throw new \Exception("bad");
                    }'
            ],
            'arrayKeyExistsOnStringArrayShouldInformArrayness' => [
                '<?php
                    /**
                     * @param string[] $a
                     * @return array{b: string}
                     */
                    function foo(array $a) {
                        if (array_key_exists("b", $a)) {
                            return $a;
                        }

                        throw new \Exception("bad");
                    }'
            ],
            'issetOnArrayTwice' => [
                '<?php
                    function foo(array $options): void {
                        if (!isset($options["a"])) {
                            $options["a"] = "hello";
                        }

                        if (!isset($options["b"])) {
                            $options["b"] = 1;
                        }

                        if ($options["b"] === 2) {}
                    }'
            ],
            'listDestructuringErrorSuppress' => [
                '<?php
                    function foo(string $s) : void {
                        @list(, $port) = explode(":", $s);
                        echo isset($port) ? "cool" : "uncool";
                    }',
            ],
            'listDestructuringErrorSuppressWithFirstString' => [
                '<?php
                    function foo(string $s) : string {
                        @list($port, $starboard) = explode(":", $s);
                        return $port;
                    }',
            ],
            'accessAfterArrayExistsVariable' => [
                '<?php
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
                    class C extends P {}'
            ],
            'accessAfterArrayExistsStaticClass' => [
                '<?php
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
                    class C extends P {}'
            ],
            'issetCreateTKeyedArrayWithType' => [
                '<?php
                    function foo(array $options): void {
                        if (isset($options["a"])) {
                            $options["b"] = "hello";
                        }

                        if (\is_array($options["b"])) {}
                    }'
            ],
            'issetOnThing' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    class C {
                        public function foo() : ?string {
                            return null;
                        }
                    }

                    function foo(C $c) : void {
                        strlen($c->foo() ?? "");
                    }'
            ],
            'issetOnMethodCallInsideMethodCall' => [
                '<?php
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
                '<?php
                    class B {
                        public function bar() : void {}
                    }

                    /** @psalm-suppress MissingConstructor */
                    class A {
                        /** @var B */
                        public $foo;

                        public function init() : void {
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
                    }'
            ],
            'issetOnArrayOfArraysReturningStringInElse' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    function foo(int $i) : void {
                        /** @var array<int, array<string, string>> */
                        $tokens = [];

                        if (!isset($tokens[$i]["a"])) {
                            echo $tokens[$i]["b"];
                        }
                    }',
            ],
            'noMixedMethodCallAfterIsset' => [
                '<?php
                    $data = file_get_contents("php://input");
                    /** @psalm-suppress MixedAssignment */
                    $payload = json_decode($data, true);

                    if (!isset($payload["a"]) || rand(0, 1)) {
                        return;
                    }

                    /**
                     * @psalm-suppress MixedArrayAccess
                     * @psalm-suppress MixedArgument
                     */
                    echo $payload["b"];'
            ],
            'implicitIssetWithStringKeyOnArrayDoesntChangeArrayType' => [
                '<?php
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
                '<?php

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
                    }'
            ],
            'noCrashAfterTwoIsset' => [
                '<?php
                    /** @psalm-suppress MixedArrayOffset */
                    function foo(array $a, array $b) : void {
                        if (! isset($b["id"], $a[$b["id"]])) {
                            echo "z";
                        }
                    }'
            ],
            'assertOnPossiblyDefined' => [
                '<?php
                    function crashes(): void {
                        if (rand(0,1)) {
                            $dt = new \DateTime;
                        }
                        /**
                         * @psalm-suppress PossiblyUndefinedVariable
                         * @psalm-suppress MixedArgument
                         */
                        assert($dt);
                    }'
            ],
            'arrayKeyExistsThrice' => [
                '<?php
                    function three(array $a): void {
                        if (!array_key_exists("a", $a)
                            || !array_key_exists("b", $a)
                            || !array_key_exists("c", $a)
                            || (!is_string($a["a"]) && !is_int($a["a"]))
                            || (!is_string($a["b"]) && !is_int($a["b"]))
                            || (!is_string($a["c"]) && !is_int($a["c"]))
                        ) {
                            throw new \Exception();
                        }

                        echo $a["a"];
                        echo $a["b"];
                    }'
            ],
            'arrayKeyExistsTwice' => [
                '<?php
                    function two(array $a): void {
                        if (!array_key_exists("a", $a) || !(is_string($a["a"]) || is_int($a["a"])) ||
                            !array_key_exists("b", $a) || !(is_string($a["b"]) || is_int($a["b"]))
                        ) {
                            throw new \Exception();
                        }

                        echo $a["a"];
                        echo $a["b"];
                    }'
            ],
            'issetOnNullableMixed' => [
                '<?php
                    function processParam(mixed $param) : void {
                        if (rand(0, 1)) {
                            $param = null;
                        }

                        if (isset($param["name"])) {
                            /**
                             * @psalm-suppress MixedArgument
                             * @psalm-suppress MixedArrayAccess
                             */
                            echo $param["name"];
                        }
                    }',
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
            'issetAdditionalVarWithSealedTKeyedArray' => [
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
            'listDestructuringErrorSuppress' => [
                '<?php
                    function foo(string $s) : string {
                        @list($port) = explode(":", $s, -1);
                        return $port;
                    }',
                'error_message' => 'NullableReturnStatement',
            ],
            'undefinedVarInNullCoalesce' => [
                '<?php
                    function bar(): void {
                        $do_baz = $config["do_it"] ?? false;
                        if ($do_baz) {
                            baz();
                        }
                    }',
                'error_message' => 'UndefinedVariable'
            ],
            'issetNullVar' => [
                '<?php
                    function four(?string $s) : void {
                        if ($s === null) {
                            if (isset($s)) {}
                        }
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'stringIsAlwaysSet' => [
                '<?php
                    function foo(string $s) : string {
                        if (!isset($s)) {
                            return "foo";
                        }
                        return "bar";
                    }',
                'error_message' => 'TypeDoesNotContainType'
            ],
            'issetOnArrayOfArraysReturningString' => [
                '<?php
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
                '<?php
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
        ];
    }
}
