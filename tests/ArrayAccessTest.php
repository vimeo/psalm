<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class ArrayAccessTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function testEnsureArrayOffsetsExist(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('PossiblyUndefinedStringArrayOffset');

        Config::getInstance()->ensure_array_string_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                function takesString(string $s): void {}

                /** @param array<string, string> $arr */
                function takesArrayIteratorOfString(array $arr): void {
                    echo $arr["hello"];
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testEnsureArrayOffsetsExistWithIssetCheck(): void
    {
        Config::getInstance()->ensure_array_string_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                function takesString(string $s): void {}

                /** @param array<string, string> $arr */
                function takesArrayIteratorOfString(array $arr): void {
                    if (isset($arr["hello"])) {
                        echo $arr["hello"];
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testDontEnsureArrayOffsetsExist(): void
    {
        Config::getInstance()->ensure_array_string_offsets_exist = false;

        $this->addFile(
            'somefile.php',
            '<?php
                function takesString(string $s): void {}

                /** @param array<string, string> $arr */
                function takesArrayIteratorOfString(array $arr): void {
                    echo $arr["hello"];
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testEnsureArrayOffsetsExistWithIssetCheckFollowedByIsArray(): void
    {
        Config::getInstance()->ensure_array_string_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @param array<string, mixed> $s */
                function foo(array $s) : void {
                    if (isset($s["a"]) && \is_array($s["a"])) {}
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testComplainAfterFirstIsset(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('PossiblyUndefinedStringArrayOffset');

        Config::getInstance()->ensure_array_string_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                    function foo(array $arr) : void {
                        if (isset($arr["a"]) && $arr["b"]) {}
                    }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testEnsureArrayIntOffsetsExist(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('PossiblyUndefinedIntArrayOffset');

        Config::getInstance()->ensure_array_int_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                function takesString(string $s): void {}

                /** @param array<int, string> $arr */
                function takesArrayIteratorOfString(array $arr): void {
                    echo $arr[4];
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testNoIssueWhenUsingArrayValuesOnNonEmptyArray(): void
    {
        Config::getInstance()->ensure_array_int_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @param string[][] $arr */
                function foo(array $arr) : void {
                    if (count($arr) === 1 && count(array_values($arr)[0]) === 1) {}
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testNoIssueWhenUsingArrayValuesOnNonEmptyArrayCheckedWithSizeof(): void
    {
        Config::getInstance()->ensure_array_int_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @param string[][] $arr */
                function foo(array $arr) : void {
                    if (sizeof($arr) === 1 && sizeof(array_values($arr)[0]) === 1) {}
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testNoIssueAfterManyIssets(): void
    {
        Config::getInstance()->ensure_array_int_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @return mixed
                 */
                function f(array $a) {
                    if (isset($a[1])
                        && is_array($a[1])
                        && isset($a[1][2])
                    ) {
                        return $a[1][2];
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testEnsureListOffsetExistsNotEmpty(): void
    {
        Config::getInstance()->ensure_array_int_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @param list<string> $arr */
                function takesList(array $arr) : void {
                    if ($arr) {
                        echo $arr[0];
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testEnsureListOffsetExistsAfterArrayPop(): void
    {
        Config::getInstance()->ensure_array_int_offsets_exist = true;

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('PossiblyUndefinedIntArrayOffset');

        $this->addFile(
            'somefile.php',
            '<?php
                /** @param list<string> $arr */
                function takesList(array $arr) : void {
                    if ($arr) {
                        echo $arr[0];
                        array_pop($arr);
                        echo $arr[0];
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testEnsureOffsetExistsAfterArrayPush(): void
    {
        Config::getInstance()->ensure_array_int_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                $a = [1, 2, 3];
                array_push($a, 4);
                echo $a[3];',
        );
        $this->analyzeFile('somefile.php', new Context());
    }

    public function testEnsureOffsetExistsAfterNestedIsset(): void
    {
        Config::getInstance()->ensure_array_string_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                class A {
                    public int $foo = 0;
                }

                /**
                 * @param array<string, A> $value
                 */
                function test(array $value): int
                {
                    return isset($value["a"]->foo) ? $value["a"]->foo : 0;
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testEnsureListOffsetExistsAfterCountValueInRange(): void
    {
        Config::getInstance()->ensure_array_int_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @param list<string> $arr */
                function takesList(array $arr) : void {
                    if (count($arr) >= 3) {
                        echo $arr[0];
                        echo $arr[1];
                        echo $arr[2];
                    }

                    if (count($arr) > 2) {
                        echo $arr[0];
                        echo $arr[1];
                        echo $arr[2];
                    }

                    if (count($arr) === 3) {
                        echo $arr[0];
                        echo $arr[1];
                        echo $arr[2];
                    }

                    if (3 === count($arr)) {
                        echo $arr[0];
                        echo $arr[1];
                        echo $arr[2];
                    }

                    if (3 <= count($arr)) {
                        echo $arr[0];
                        echo $arr[1];
                        echo $arr[2];
                    }

                    if (2 < count($arr)) {
                        echo $arr[0];
                        echo $arr[1];
                        echo $arr[2];
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testCountOnKeyedArrayInRange(): void
    {
        Config::getInstance()->ensure_array_int_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @param non-empty-list<string> $list */
                function bar(array $list) : void {
                    if (rand(0, 1)) {
                        $list = ["a"];
                    }
                    if (count($list) > 1) {
                        echo $list[1];
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testCountOnKeyedArrayInRangeWithUpdate(): void
    {
        Config::getInstance()->ensure_array_int_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @param non-empty-list<string> $list */
                function bar(array $list) : void {
                    if (rand(0, 1)) {
                        $list = ["a"];
                    }
                    if (count($list) > 1) {
                        if ($list[1][0] === "a") {
                            $list[1] = "foo";
                        }
                        echo $list[1];
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testCountOnKeyedArrayOutOfRange(): void
    {
        Config::getInstance()->ensure_array_int_offsets_exist = true;

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('PossiblyUndefinedIntArrayOffset');

        $this->addFile(
            'somefile.php',
            '<?php
                /** @param non-empty-list<string> $list */
                function bar(array $list) : void {
                    if (rand(0, 1)) {
                        $list = ["a"];
                    }
                    if (count($list) > 1) {
                        echo $list[2];
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testEnsureListOffsetExistsAfterCountValueOutOfRange(): void
    {
        Config::getInstance()->ensure_array_int_offsets_exist = true;

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('PossiblyUndefinedIntArrayOffset');

        $this->addFile(
            'somefile.php',
            '<?php
                /** @param list<string> $arr */
                function takesList(array $arr) : void {
                    if (count($arr) >= 2) {
                        echo $arr[0];
                        echo $arr[1];
                        echo $arr[2];
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testEnsureListOffsetExistsAfterCountValueOutOfRangeSmallerThan(): void
    {
        Config::getInstance()->ensure_array_int_offsets_exist = true;

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('PossiblyUndefinedIntArrayOffset');

        $this->addFile(
            'somefile.php',
            '<?php
                /** @param list<string> $arr */
                function takesList(array $arr) : void {
                    if (2 <= count($arr)) {
                        echo $arr[0];
                        echo $arr[1];
                        echo $arr[2];
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testDontWorryWhenUnionedWithPositiveInt(): void
    {
        Config::getInstance()->ensure_array_int_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @param list<string> $a
                 * @param 0|positive-int $b
                 */
                function foo(array $a, int $b): void {
                    echo $a[$b];
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    #[Override]
    public function providerValidCodeParse(): iterable
    {
        return [
            'allowEmptyList' => [
                'code' => '<?php
                    function test(): void {
                        $a = [];
                        /** @psalm-suppress RedundantFunctionCall */
                        $a = array_values($a);

                        /** @psalm-suppress RedundantConditionGivenDocblockType, NoValue, NullArrayOffset */
                        if (empty($a)
                            || count($a) > 1
                            || empty($a[array_key_first($a)])
                        ) {
                        }
                    }',
            ],
            'testBuildList' => [
                'code' => '<?php
                    $a = [];

                    if (random_int(0, 1)) {
                        $a []= 0;
                    }

                    if (random_int(0, 1)) {
                        $a []= 1;
                    }

                    $pre = $a;

                    $a []= 2;

                ',
                'assertions' => [
                    '$pre===' => 'list{0?: 0|1, 1?: 1}',
                    '$a===' => 'list{0: 0|1|2, 1?: 1|2, 2?: 2}',
                ],
            ],
            'testBuildListOther' => [
                'code' => '<?php
                    $list = [];
                    $entropy = random_int(0, 2);
                    if ($entropy === 0) {
                        $list[] = "A";
                    } elseif ($entropy === 1) {
                        $list[] = "B";
                    }

                    $list[] = "C";
                ',
                'assertions' => [
                    '$list===' => "list{0: 'A'|'B'|'C', 1?: 'C'}",
                ],
            ],
            'testBuildList3' => [
                'code' => '<?php
                    $a = [0];
                    if (random_int(0, 1)) {
                        $a []= 1;
                    }
                    if (random_int(0, 1)) {
                        $a []= 2;
                    }
                    $a []= 3;
                ',
                'assertions' => [
                    '$a===' => "list{0: 0, 1: 1|2|3, 2?: 2|3, 3?: 3}",
                ],
            ],
            'instanceOfStringOffset' => [
                'code' => '<?php
                    class A {
                        public function fooFoo(): void { }
                    }
                    function bar (array $a): void {
                        if ($a["a"] instanceof A) {
                            $a["a"]->fooFoo();
                        }
                    }',
            ],
            'instanceOfIntOffset' => [
                'code' => '<?php
                    class A {
                        public function fooFoo(): void { }
                    }
                    function bar (array $a): void {
                        if ($a[0] instanceof A) {
                            $a[0]->fooFoo();
                        }
                    }',
            ],
            'nonEmptyStringAccess' => [
                'code' => '<?php
                    /** @var non-empty-string $a */
                    $a = "blah";
                    $b = $a[0];',
                'assertions' => [
                    '$b===' => 'non-empty-string',
                ],
            ],
            'notEmptyStringOffset' => [
                'code' => '<?php
                    /**
                     * @param  array<string>  $a
                     */
                    function bar (array $a): string {
                        if ($a["bat"]) {
                            return $a["bat"];
                        }

                        return "blah";
                    }',
            ],
            'issetPropertyStringOffset' => [
                'code' => '<?php
                    class A {
                        /** @var array<string, string> */
                        public $arr = [];
                    }
                    $a = new A();
                    if (!isset($a->arr["bat"]) || strlen($a->arr["bat"])) { }',
            ],
            'issetPropertyStringOffsetUndefinedClass' => [
                'code' => '<?php
                    /** @psalm-suppress UndefinedClass */
                    $a = new A();
                    /**
                     * @psalm-suppress UndefinedClass
                     */
                    if (!isset($a->arr["bat"]) || strlen($a->arr["bat"])) { }',
                'assertions' => [],
                'ignored_issues' => ['MixedArgument', 'MixedArrayAccess'],
            ],
            'notEmptyIntOffset' => [
                'code' => '<?php
                    /**
                     * @param  array<string>  $a
                     */
                    function bar (array $a): string {
                        if ($a[0]) {
                            return $a[0];
                        }

                        return "blah";
                    }',
            ],
            'ignorePossiblyNullArrayAccess' => [
                'code' => '<?php
                    $a = rand(0, 1) ? [1, 2] : null;
                    echo $a[0];',
                'assertions' => [],
                'ignored_issues' => ['PossiblyNullArrayAccess'],
            ],
            'ignoreEmptyArrayAccess' => [
                'code' => '<?php
                    $arr = [];
                    $x = $arr[0];
                    if (isset($arr[0]) && $arr[0]) { }',
                'assertions' => [
                    '$x' => 'mixed',
                ],
                'ignored_issues' => ['EmptyArrayAccess', 'MixedAssignment'],
            ],
            'objectLikeWithoutKeys' => [
                'code' => '<?php
                    function takesInt(int $i): void {}
                    function takesString(string $s): void {}
                    function takesBool(bool $b): void {}

                    /**
                     * @param array{int, string, bool} $b
                     */
                    function a(array $b): void {
                        takesInt($b[0]);
                        takesString($b[1]);
                        takesBool($b[2]);
                    }',
            ],
            'stringKeysWithInts' => [
                'code' => '<?php
                    $array = ["01" => "01", "02" => "02"];

                    foreach ($array as $key => $value) {
                        $len = strlen($key);
                    }',
            ],
            'listAssignmentKeyOffset' => [
                'code' => '<?php
                    $a = [];
                    list($a["foo"]) = explode("+", "a+b");
                    echo $a["foo"];',
            ],
            'objectlikeOptionalNamespacedParam' => [
                'code' => '<?php
                    namespace N;

                    /**
                     * @psalm-param array{key?:string} $p
                     */
                    function f(array $p): void
                    {
                        echo isset($p["key"]) ? $p["key"] : "";
                    }',
            ],
            'unsetTKeyedArrayOffset' => [
                'code' => '<?php
                    $x1 = ["a" => "value"];
                    unset($x1["a"]);

                    $x2 = ["a" => "value", "b" => "value"];
                    unset($x2["a"]);

                    $x3 = ["a" => "value", "b" => "value"];
                    $k = "a";
                    unset($x3[$k]);',
                'assertions' => [
                    '$x1===' => 'array<never, never>',
                    '$x2===' => "array{b: 'value'}",
                    '$x3===' => "array{b: 'value'}",
                ],
            ],
            'possiblyUndefinedArrayOffsetKeyedArray' => [
                'code' => '<?php
                    $d = [];
                    if (!rand(0,1)) {
                        $d[0] = "a";
                    }

                    $x = $d[0];',
                'assertions' => [
                    '$x===' => '\'a\'',
                ],
                'ignored_issues' => ['PossiblyUndefinedArrayOffset'],
            ],
            'domNodeListAccessible' => [
                'code' => '<?php
                    $doc = new DOMDocument();
                    $doc->loadXML("<node key=\"value\"/>");
                    $e = $doc->getElementsByTagName("node")[0];',
                'assertions' => [
                    '$e' => 'DOMElement|null',
                ],
            ],
            'getOnArrayAccess' => [
                'code' => '<?php
                    /** @param ArrayAccess<int, string> $a */
                    function foo(ArrayAccess $a) : string {
                        return $a[0];
                    }',
            ],
            'mixedKeyMixedOffset' => [
                'code' => '<?php
                    function example(array $x, $y) : void {
                        echo $x[$y];
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedArgument', 'MixedArrayOffset', 'MissingParamType'],
            ],
            'suppressPossiblyUndefinedStringArrayOffset' => [
                'code' => '<?php
                    /** @var array{a?:string} */
                    $entry = ["a"];

                    ["a" => $elt] = $entry;
                    strlen($elt);
                    strlen($entry["a"]);',
                'assertions' => [],
                'ignored_issues' => ['PossiblyUndefinedArrayOffset'],
            ],
            'noRedundantConditionOnMixedArrayAccess' => [
                'code' => '<?php
                    /** @var array<int, int> */
                    $b = [];

                    /** @var array<int, int> */
                    $c = [];

                    /** @var array<int, mixed> */
                    $d = [];

                    if (!empty($d[0]) && !isset($c[$d[0]])) {
                        if (isset($b[$d[0]])) {}
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedArrayOffset'],
            ],
            'noEmptyArrayAccessInLoop' => [
                'code' => '<?php
                    /**
                     * @psalm-suppress MixedAssignment
                     * @psalm-suppress MixedOperand
                     * @psalm-suppress MixedArrayAssignment
                     * @param mixed[] $line
                     */
                    function _renderCells(array $line): void {
                      foreach ($line as $cell) {
                        $cellOptions = [];
                        if (is_array($cell)) {
                          $cellOptions = $cell[1];
                        }
                        if (isset($cellOptions[0])) {
                          $cellOptions[0] = $cellOptions[0] . "b";
                        } else {
                          $cellOptions[0] = "b";
                        }
                      }
                    }',
            ],
            'arrayAccessPropertyAssertion' => [
                'code' => '<?php
                    class A {}
                    class B extends A {
                        /** @var array<int, string> */
                        public $arr = [];
                    }

                    /** @var array<A> */
                    $as = [];

                    if (!$as
                        || !$as[0] instanceof B
                        || !$as[0]->arr
                    ) {
                        return null;
                    }

                    $b = $as[0]->arr;',
            ],
            'arrayAccessAfterPassByref' => [
                'code' => '<?php
                    class Arr {
                        /**
                         * @param mixed $c
                         * @return mixed
                         */
                        public static function pull(array &$a, string $b, $c = null) {
                            return $a[$b] ?? $c;
                        }
                    }

                    function _renderButton(array $settings): void {
                        Arr::pull($settings, "a", true);

                        if (isset($settings["b"])) {
                            Arr::pull($settings, "b");
                        }

                        if (isset($settings["c"])) {}
                    }',
            ],
            'arrayKeyChecks' => [
                'code' => '<?php
                    /**
                     * @param  string[] $arr
                     */
                    function foo(array $arr) : void {
                        if (!$arr) {
                            return;
                        }

                        foreach ($arr as $i => $_) {}

                        if ($i === "hello") {}
                        if ($i !== "hello") {}
                        if ($i === 5) {}
                        if ($i !== 5) {}
                        if (is_string($i)) {}
                        if (is_int($i)) {}

                        foreach ($arr as $i => $_) {}

                        if ($i === "hell") {
                            $i = "hellp";
                        }

                        if ($i === "hel") {}
                    }',
            ],
            'arrayKeyChecksAfterDefinition' => [
                'code' => '<?php
                    /**
                     * @param  string[] $arr
                     */
                    function foo(array $arr) : void {
                        if (!$arr) {
                            return;
                        }

                        foreach ($arr as $i => $_) {}

                        if ($i === "hell") {
                            $i = "hellp";
                        }

                        if ($i === "hel") {}
                    }',
            ],
            'allowMixedTypeCoercionArrayKeyAccess' => [
                'code' => '<?php
                    /**
                     * @param array<array-key, int> $i
                     * @param array<int, string> $arr
                     */
                    function foo(array $i, array $arr) : void {
                        foreach ($i as $j => $k) {
                            echo $arr[$j];
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedArrayTypeCoercion'],
            ],
            'allowNegativeStringOffset' => [
                'code' => '<?php
                    $a = "hello";
                    echo $a[-5];
                    echo $a[-4];
                    echo $a[-3];
                    echo $a[-2];
                    echo $a[-1];
                    echo $a[0];
                    echo $a[1];
                    echo $a[2];
                    echo $a[3];
                    echo $a[4];',
            ],
            'arrayAccessAfterPossibleGeneralisation' => [
                'code' => '<?php
                    function getArray() : array { return []; }
                    $params = array(
                        "a" => 1,
                        "b" => [
                            "c" => "a",
                        ]
                    );

                    if (rand(0, 1)) {
                        $params = getArray();
                    }

                    echo $params["b"]["c"];',
                'assertions' => [],
                'ignored_issues' => ['MixedArrayAccess', 'MixedArgument'],
            ],
            'arrayAccessOnObjectWithNullGet' => [
                'code' => '<?php
                    $array = new C([]);
                    $array["key"] = [];
                    /** @psalm-suppress PossiblyInvalidArrayAssignment */
                    $array["key"][] = "testing";

                    $c = isset($array["foo"]) ? $array["foo"] : null;

                    /**
                     * @psalm-suppress MissingTemplateParam
                     */
                    class C implements ArrayAccess
                    {
                        /**
                         * @var array<C|scalar>
                         */
                        protected $data = [];

                        /**
                         * @param array<scalar|array> $array
                         * @psalm-suppress MixedArgumentTypeCoercion
                         */
                        final public function __construct(array $array)
                        {
                            foreach ($array as $key => $value) {
                                if (is_array($value)) {
                                    $this->data[$key] = new static($value);
                                } else {
                                    $this->data[$key] = $value;
                                }
                            }
                        }

                        /**
                         * @param string $name
                         * @return C|scalar
                         */
                        public function offsetGet($name)
                        {
                            return $this->data[$name];
                        }

                        /**
                         * @param ?string $offset
                         * @param scalar|array $value
                         * @psalm-suppress MixedArgumentTypeCoercion
                         */
                        public function offsetSet($offset, $value) : void
                        {
                            if (is_array($value)) {
                                $value = new static($value);
                            }

                            if (null === $offset) {
                                $this->data[] = $value;
                            } else {
                                $this->data[$offset] = $value;
                            }
                        }

                        public function __isset(string $name) : bool
                        {
                            return isset($this->data[$name]);
                        }

                        public function __unset(string $name) : void
                        {
                            unset($this->data[$name]);
                        }

                        /**
                         * @psalm-suppress MixedArgument
                         */
                        public function offsetExists($offset) : bool
                        {
                            return $this->__isset($offset);
                        }

                        /**
                         * @psalm-suppress MixedArgument
                         */
                        public function offsetUnset($offset) : void
                        {
                            $this->__unset($offset);
                        }
                    }',
                'assertions' => [
                    '$c' => 'C|null|scalar',
                ],
            ],
            'singleLetterOffset' => [
                'code' => '<?php
                    ["s" => "str"]["str"[0]];',
            ],
            'arrayAccessAfterByRefArrayOffsetAssignment' => [
                'code' => '<?php
                    /**
                     * @param array{param1: array} $params
                     */
                    function dispatch(array $params) : void {
                        $params["param1"]["foo"] = "bar";
                    }

                    $ar = [];
                    dispatch(["param1" => &$ar]);
                    $value = "foo";
                    if (isset($ar[$value])) {
                        echo (string) $ar[$value];
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedArrayAccess'],
            ],
            'byRefArrayAccessWithoutKnownVarNoNotice' => [
                'code' => '<?php
                    $a = new stdClass();
                    /** @psalm-suppress MixedPropertyFetch */
                    print_r([&$a->foo->bar]);',
            ],
            'accessOffsetOnList' => [
                'code' => '<?php
                    /** @param list<int> $arr */
                    function foo(array $arr) : void {
                        echo $arr[3] ?? null;
                    }',
            ],
            'destructureMixed' => [
                'code' => '<?php
                    class S {
                        protected array $a = [];
                        protected array $b = [];
                        protected array $c = [];

                        /**
                         * @psalm-suppress MixedAssignment
                         */
                        public function pop(): void {
                            if (!$this->a) {
                                return;
                            }

                            $popped = array_pop($this->a);

                            /** @psalm-suppress MixedArrayAccess */
                            [$this->b, $this->c] = $popped;
                        }
                    }',
            ],
            'simpleXmlArrayFetch' => [
                'code' => '<?php
                    function foo(SimpleXMLElement $s) : ?SimpleXMLElement {
                        return $s["a"];
                    }',
            ],
            'simpleXmlArrayFetchChildren' => [
                'code' => '<?php
                    function iterator(SimpleXMLElement $xml): iterable {
                        $children = $xml->children();
                        assert($children !== null);
                        foreach ($children as $img) {
                            yield $img["src"] ?? "";
                        }
                    }',
            ],
            'assertOnArrayAccess' => [
                'code' => '<?php
                    class A {
                        public function foo() : void {}
                    }

                    /**
                     * @psalm-suppress MissingTemplateParam
                     */
                    class C implements ArrayAccess
                    {
                        /**
                         * @var array
                         */
                        protected $data = [];

                        /**
                         * @param string $name
                         * @return mixed
                         */
                        public function offsetGet($name)
                        {
                            return $this->data[$name];
                        }

                        /**
                         * @param string $offset
                         * @param mixed $value
                         */
                        public function offsetSet($offset, $value) : void
                        {
                            $this->data[$offset] = $value;
                        }

                        public function __isset(string $name) : bool
                        {
                            return isset($this->data[$name]);
                        }

                        public function __unset(string $name) : void
                        {
                            unset($this->data[$name]);
                        }

                        /**
                         * @psalm-suppress MixedArgument
                         */
                        public function offsetExists($offset) : bool
                        {
                            return $this->__isset($offset);
                        }

                        /**
                         * @psalm-suppress MixedArgument
                         */
                        public function offsetUnset($offset) : void
                        {
                            $this->__unset($offset);
                        }
                    }

                    $container = new C();
                    if ($container["a"] instanceof A) {
                        $container["a"]->foo();
                    }',
            ],
            'assignmentListCheckForNull' => [
                'code' => '<?php
                    /**
                     * @return array{0: int, 1:string}|null
                     */
                    function bar(int $i) {
                        if ( $i < 0)
                            return [$i, "hello"];
                        else
                            return null;
                    }

                    /** @psalm-suppress PossiblyNullArrayAccess */
                    [1 => $foo] = bar(0);

                    if ($foo !== null) {}',
            ],
            'SKIPPED-accessKnownArrayWithPositiveInt' => [
                'code' => '<?php
                    /** @param list<int> $arr */
                    function foo(array $arr) : void {
                        $o = [4, 15, 18, 21, 51];
                        $i = 0;
                        foreach ($arr as $a) {
                            if ($o[$i] === $a) {}
                            $i++;
                        }
                    }',
            ],
            'arrayAccessOnArraylikeObjectOrArray' => [
                'code' => '<?php
                    /**
                     * @param arraylike-object<int, string>|array<int, string> $arr
                     */
                    function test($arr): string {
                        return $arr[0];
                    }

                    test(["a", "b"]);
                    test(new ArrayObject(["a", "b"]));',
            ],
            'nullCoalesceArrayAccess' => [
                'code' => '<?php
                    /** @param ArrayAccess<int, string> $a */
                    function foo(?ArrayAccess $a) : void {
                        echo $a[0] ?? "default";
                    }',
            ],
            'allowUnsettingNested' => [
                'code' => '<?php
                    /** @psalm-immutable */
                    final class test {
                        public function __construct(public int $value) {}
                    }
                    $test = new test(1);
                    $a = [1 => $test];
                    unset($a[$test->value]);',
            ],
            'arrayAssertionShouldNotBeNull' => [
                'code' => '<?php
                    function foo(?array $arr, string $s) : void {
                        /**
                         * @psalm-suppress PossiblyNullArrayAccess
                         * @psalm-suppress MixedArrayAccess
                         */
                        if ($arr[$s]["b"] !== true) {
                            return;
                        }

                        /**
                         * @psalm-suppress MixedArgument
                         * @psalm-suppress MixedArrayAccess
                         * @psalm-suppress PossiblyNullArrayAccess
                         */
                        echo $arr[$s]["c"];
                    }',
            ],
            'TransformBadOffsetToGoodOnes' => [
                'code' => '<?php
                    $index = 1.1;

                    /** @psalm-suppress InvalidArrayOffset */
                    $_arr1 = [$index => 5];

                    $_arr2 = [];
                    /** @psalm-suppress InvalidArrayOffset */
                    $_arr2[$index] = 5;',
                'assertions' => [
                    '$_arr1===' => 'non-empty-array<1, 5>',
                    '$_arr2===' => 'array{1: 5}',
                ],
            ],
            'accessArrayWithSingleStringLiteralOffset' => [
                'code' => '<?php
                    /** @param non-empty-array<"name", int> $p */
                    function f($p): int {
                        return $p["name"];
                    }',
            ],
            'unsetListKeyedArrayDisableListFlag' => [
                'code' => '<?php
                $a = ["a", "b"];
                unset($a[0]);
                ',
                'assertions' => ['$a===' => "array{1: 'b'}"],
            ],
            'noCrashOnUnknownClassArrayAccess' => [
                'code' => <<<'PHP'
                <?php

                namespace Psalmtest\Psalmtest;

                use SomeMissingClass;

                class Test
                {
                    public function f(): void {
                        /** @var SomeMissingClass */
                        $result = null;

                        if ($result['errors'] === true) {}
                    }
                }
                PHP,
                'assertions' => [],
                'ignored_issues' => ['UndefinedDocblockClass'],
            ],
            'canExtendArrayObjectOffsetSet' => [
                'code' => <<<'PHP'
                <?php
                // parameter names in PHP are messed up:
                // ArrayObject::offsetSet(mixed $key, mixed $value) : void;
                // ArrayAccess::offsetSet(mixed $offset, mixed $value) : void;
                // and yet ArrayObject implements ArrayAccess

                /** @extends ArrayObject<int, int> */
                class C extends ArrayObject {
                    public function offsetSet(mixed $key, mixed $value): void {
                        parent::offsetSet($key, $value);
                    }
                }
                PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
        ];
    }

    #[Override]
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'invalidArrayAccess' => [
                'code' => '<?php
                    $a = 5;
                    echo $a[0];',
                'error_message' => 'InvalidArrayAccess',
            ],
            'invalidArrayOffset' => [
                'code' => '<?php
                    $x = ["a"];
                    $y = $x["b"];',
                'error_message' => 'InvalidArrayOffset',
            ],
            'possiblyInvalidArrayOffsetWithInt' => [
                'code' => '<?php
                    $x = rand(0, 5) > 2 ? ["a" => 5] : "hello";
                    $y = $x[0];',
                'error_message' => 'PossiblyInvalidArrayOffset',
            ],
            'possiblyInvalidArrayOffsetWithString' => [
                'code' => '<?php
                    $x = rand(0, 5) > 2 ? ["a" => 5] : "hello";
                    $y = $x["a"];',
                'error_message' => 'PossiblyInvalidArrayOffset',
            ],
            'possiblyInvalidArrayAccessWithNestedArray' => [
                'code' => '<?php
                    /**
                     * @return array<int,array<string,float>>|string
                     */
                    function return_array() {
                        return rand() % 5 > 3 ? [["key" => 3.5]] : "key:3.5";
                    }
                    $result = return_array();
                    $v = $result[0]["key"];',
                'error_message' => 'PossiblyInvalidArrayOffset',
            ],
            'possiblyInvalidArrayAccess' => [
                'code' => '<?php
                    $a = rand(0, 10) > 5 ? 5 : ["hello"];
                    echo $a[0];',
                'error_message' => 'PossiblyInvalidArrayAccess',
            ],
            'insideIssetDisabledForDim' => [
                'code' => '<?php
                    isset($a[$b]);',
                'error_message' => 'UndefinedGlobalVariable',
            ],
            'mixedArrayAccess' => [
                'code' => '<?php
                    /** @var mixed */
                    $a = [];
                    echo $a[0];',
                'error_message' => 'MixedArrayAccess',
                'ignored_issues' => ['MixedAssignment'],
            ],
            'mixedArrayOffset' => [
                'code' => '<?php
                    /** @var mixed */
                    $a = 5;
                    echo [1, 2, 3, 4][$a];',
                'error_message' => 'MixedArrayOffset',
                'ignored_issues' => ['MixedAssignment'],
            ],
            'nullArrayAccess' => [
                'code' => '<?php
                    $a = null;
                    echo $a[0];',
                'error_message' => 'NullArrayAccess',
            ],
            'possiblyNullArrayAccess' => [
                'code' => '<?php
                    $a = rand(0, 1) ? [1, 2] : null;
                    echo $a[0];',
                'error_message' => 'PossiblyNullArrayAccess',
            ],
            'specificErrorMessage' => [
                'code' => '<?php
                    $params = ["key" => "value"];
                    echo $params["fieldName"];',
                'error_message' => 'InvalidArrayOffset - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:26 - Cannot access '
                    . 'value on variable $params using offset value of',
            ],
            'missingArrayOffsetAfterUnset' => [
                'code' => '<?php
                    $x = ["a" => "value", "b" => "value"];
                    unset($x["a"]);
                    echo $x["a"];',
                'error_message' => 'InvalidArrayOffset',
            ],
            'noImpossibleStringAccess' => [
                'code' => '<?php
                    function foo(string $s) : void {
                        echo $s[0][1];
                    }',
                'error_message' => 'InvalidArrayOffset',
            ],
            'mixedKeyStdClassOffset' => [
                'code' => '<?php
                    function example(array $y) : void {
                        echo $y[new stdClass()];
                    }',
                'error_message' => 'InvalidArrayOffset',
            ],
            'toStringOffset' => [
                'code' => '<?php
                    class Foo {
                        public function __toString() {
                            return "Foo";
                        }
                    }

                    $a = ["Foo" => "bar"];
                    echo $a[new Foo];',
                'error_message' => 'InvalidArrayOffset',
            ],
            'possiblyUndefinedIntArrayOffset' => [
                'code' => '<?php
                    /** @var array{0?:string} */
                    $entry = ["a"];

                    [$elt] = $entry;',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'possiblyUndefinedStringArrayOffset' => [
                'code' => '<?php
                    /** @var array{a?:string} */
                    $entry = ["a"];

                    ["a" => $elt] = $entry;',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'possiblyInvalidMixedArrayOffset' => [
                'code' => '<?php
                    /**
                     * @param string|array $key
                     */
                    function foo(array $a, $key) : void {
                        echo $a[$key];
                    }',
                'error_message' => 'PossiblyInvalidArrayOffset',
            ],
            'arrayAccessOnIterable' => [
                'code' => '<?php
                    function foo(iterable $i) {
                        echo $i[0];
                    }',
                'error_message' => 'InvalidArrayAccess',
            ],
            'arrayKeyCannotBeBool' => [
                'code' => '<?php
                    /**
                     * @param string[] $arr
                     */
                    function foo(array $arr) : void {
                        if (!$arr) {
                            return;
                        }

                        foreach ($arr as $i => $_) {}

                        if ($i === false) {}
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'arrayKeyCannotBeFloat' => [
                'code' => '<?php
                    /**
                     * @param string[] $arr
                     */
                    function foo(array $arr) : void {
                        if (!$arr) {
                            return;
                        }

                        foreach ($arr as $i => $_) {}

                        if ($i === 4.0) {}
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'arrayKeyCannotBeObject' => [
                'code' => '<?php
                    /**
                     * @param string[] $arr
                     */
                    function foo(array $arr) : void {
                        if (!$arr) {
                            return;
                        }

                        foreach ($arr as $i => $_) {}

                        if ($i === new stdClass) {}
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'forbidNegativeStringOffsetOutOfRange' => [
                'code' => '<?php
                    $a = "hello";
                    echo $a[-6];',
                'error_message' => 'InvalidArrayOffset',
            ],
            'emptyStringAccess' => [
                'code' => '<?php
                    $a = "";
                    echo $a[0];',
                'error_message' => 'InvalidArrayOffset',
            ],
            'recogniseBadVar' => [
                'code' => '<?php
                    /** @psalm-suppress MixedAssignment */
                    $array = $_GET["foo"] ?? [];

                    $array[$a] = "b";',
                'error_message' => 'UndefinedGlobalVariable',
            ],
            'unsetListElementShouldChangeToArray' => [
                'code' => '<?php
                    /**
                     * @param list<string> $arr
                     * @return list<string>
                     */
                    function takesList(array $arr) : array {
                        unset($arr[0]);

                        return $arr;
                    }',
                'error_message' => 'LessSpecificReturnStatement',
            ],
            'simpleXmlArrayFetchResultCannotEqualString' => [
                'code' => '<?php
                    function foo(SimpleXMLElement $s) : void {
                        $b = $s["a"];

                        if ($b === "hello" || $b === "1") {}
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'undefinedTKeyedArrayOffset' => [
                'code' => '<?php
                    class Example {
                        /**
                         * @param array{a: string, b: int} $context
                         */
                        function foo(array $context): void {
                            $context["c"];
                        }
                    }',
                'error_message' => 'InvalidArrayOffset',
            ],
            'destructureNullable' => [
                'code' => '<?php
                    /**
                     * @return null|array
                     */
                    function maybeReturnArray(): ?array {
                        return rand(0, 1) ? null : ["key" => 1];
                    }

                    ["key" => $a] = maybeReturnArray();',
                'error_message' => 'PossiblyNullArrayAccess',
            ],
            'destructureTuple' => [
                'code' => '<?php
                    /**
                     * @return array{int, int}
                     */
                    function size(): array {
                        return [10, 20];
                    }

                    [$width, $height, $depth] = size();',
                'error_message' => 'InvalidArrayOffset',
            ],
            'negativeListAccess' => [
                'code' => '<?php
                    class HelloWorld
                    {
                        public function sayHello(): void
                        {
                            $a = explode("/", "a/b/c");
                            $x = $a[-3];
                            echo $x;
                        }
                    }',
                'error_message' => 'InvalidArrayOffset',
            ],
            'arrayOpenByDefault' => [
                'code' => '<?php
                    /**
                     * @param array{a: float, b: float} $params
                     */
                    function avg(array $params): void {
                      takesArrayOfFloats($params);
                    }

                    /**
                     * @param array<array-key, float> $arr
                     */
                    function takesArrayOfFloats(array $arr): void {
                        foreach ($arr as $a) {
                            echo $a;
                        }
                    }

                    avg(["a" => 0.5, "b" => 1.5, "c" => new Exception()]);',
                'error_message' => 'InvalidArgument',
            ],
            'possiblyUndefinedArrayOffsetKeyedArray' => [
                'code' => '<?php
                    $d = [];
                    if (!rand(0,1)) {
                        $d[0] = "a";
                    }

                    $x = $d[0];

                    //  should not report TypeDoesNotContainNull
                    if ($x === null) {}',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'cannotUseNamedArgumentsForArrayAccess' => [
                'code' => <<<'PHP'
                <?php
                /** @param ArrayAccess<int, string> $a */
                function f(ArrayAccess $a): void {
                    echo $a->offsetGet(offset: 0);
                }
                PHP,
                'error_message' => 'NamedArgumentNotAllowed',
            ],
        ];
    }
}
