<?php
namespace Psalm\Tests;

class ArrayAccessTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return array
     */
    public function providerValidCodeParse()
    {
        return [
            'instanceOfStringOffset' => [
                '<?php
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
                '<?php
                    class A {
                        public function fooFoo(): void { }
                    }
                    function bar (array $a): void {
                        if ($a[0] instanceof A) {
                            $a[0]->fooFoo();
                        }
                    }',
            ],
            'notEmptyStringOffset' => [
                '<?php
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
                '<?php
                    class A {
                        /** @var array<string, string> */
                        public $arr = [];
                    }
                    $a = new A();
                    if (!isset($a->arr["bat"]) || strlen($a->arr["bat"])) { }',
            ],
            'issetPropertyStringOffsetUndefinedClass' => [
                '<?php
                    /** @psalm-suppress UndefinedClass */
                    $a = new A();
                    /** @psalm-suppress UndefinedClass */
                    if (!isset($a->arr["bat"]) || strlen($a->arr["bat"])) { }',
                'assertions' => [],
                'error_levels' => ['MixedArgument'],
            ],
            'notEmptyIntOffset' => [
                '<?php
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
                '<?php
                    $a = rand(0, 1) ? [1, 2] : null;
                    echo $a[0];',
                'assertions' => [],
                'error_levels' => ['PossiblyNullArrayAccess'],
            ],
            'ignoreEmptyArrayAccess' => [
                '<?php
                    $arr = [];
                    $x = $arr[0];
                    if (isset($arr[0]) && $arr[0]) { }',
                'assertions' => [
                    '$x' => 'mixed',
                ],
                'error_levels' => ['EmptyArrayAccess', 'MixedAssignment'],
            ],
            'objectLikeWithoutKeys' => [
                '<?php
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
                '<?php
                    $array = ["01" => "01", "02" => "02"];

                    foreach ($array as $key => $value) {
                        $len = strlen($key);
                    }',
            ],
            'listAssignmentKeyOffset' => [
                '<?php
                    $a = [];
                    list($a["foo"]) = explode("+", "a+b");
                    echo $a["foo"];',
            ],
            'objectlikeOptionalNamespacedParam' => [
                '<?php
                    namespace N;

                    /**
                     * @psalm-param array{key?:string} $p
                     */
                    function f(array $p): void
                    {
                        echo isset($p["key"]) ? $p["key"] : "";
                    }',
            ],
            'unsetObjectLikeOffset' => [
                '<?php
                    function takesInt(int $i) : void {}
                    $x = ["a" => "value"];
                    unset($x["a"]);
                    $x[] = 5;
                    takesInt($x[0]);',
            ],
            'domNodeListAccessible' => [
                '<?php
                    $doc = new DOMDocument();
                    $doc->loadXML("<node key=\"value\"/>");
                    $doc->getElementsByTagName("node")[0];'
            ],
            'getOnArrayAcccess' => [
                '<?php
                    function foo(ArrayAccess $a) : void {
                        echo $a[0];
                    }',
                'assertions' => [],
                'error_levels' => ['MixedArgument'],
            ],
            'mixedKeyMixedOffset' => [
                '<?php
                    function example(array $x, $y) : void {
                        echo $x[$y];
                    }',
                'assertions' => [],
                'error_levels' => ['MixedArgument', 'MixedArrayOffset', 'MissingParamType'],
            ],
            'suppressPossiblyUndefinedStringArrayOffet' => [
                '<?php
                    /** @var array{a?:string} */
                    $entry = ["a"];

                    ["a" => $elt] = $entry;
                    strlen($elt);
                    strlen($entry["a"]);',
                'assertions' => [],
                'error_levels' => ['PossiblyUndefinedArrayOffset'],
            ],
            'noRedundantConditionOnMixedArrayAccess' => [
                '<?php
                    /** @var array<int, int> */
                    $b = [];

                    /** @var array<int, int> */
                    $c = [];

                    /** @var array<int, mixed> */
                    $d = [];

                    if (!empty($d[0]) && !isset($c[$d[0]])) {
                        if (isset($b[$d[0]])) {}
                    }',
                [],
                'error_levels' => ['MixedArrayOffset'],
            ],
            'noEmptyArrayAccessInLoop' => [
                '<?php
                    /**
                     * @psalm-suppress MixedAssignment
                     * @psalm-suppress MixedArrayAccess
                     * @psalm-suppress MixedOperand
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
                    }'
            ],
            'arrayAccessPropertyAssertion' => [
                '<?php
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
        ];
    }

    /**
     * @return array
     */
    public function providerInvalidCodeParse()
    {
        return [
            'invalidArrayAccess' => [
                '<?php
                    $a = 5;
                    echo $a[0];',
                'error_message' => 'InvalidArrayAccess',
            ],
            'invalidArrayOffset' => [
                '<?php
                    $x = ["a"];
                    $y = $x["b"];',
                'error_message' => 'InvalidArrayOffset',
            ],
            'possiblyInvalidArrayOffsetWithInt' => [
                '<?php
                    $x = rand(0, 5) > 2 ? ["a" => 5] : "hello";
                    $y = $x[0];',
                'error_message' => 'PossiblyInvalidArrayOffset',
            ],
            'possiblyInvalidArrayOffsetWithString' => [
                '<?php
                    $x = rand(0, 5) > 2 ? ["a" => 5] : "hello";
                    $y = $x["a"];',
                'error_message' => 'PossiblyInvalidArrayOffset',
            ],
            'possiblyInvalidArrayAccessWithNestedArray' => [
                '<?php
                    /**
                     * @return array<int,array<string,float>>|string
                     * @return string
                     */
                    function return_array() {
                        return rand() % 5 > 3 ? [["key" => 3.5]] : "key:3.5";
                    }
                    $result = return_array();
                    $v = $result[0]["key"];',
                'error_message' => 'PossiblyInvalidArrayOffset',
            ],
            'possiblyInvalidArrayAccess' => [
                '<?php
                    $a = rand(0, 10) > 5 ? 5 : ["hello"];
                    echo $a[0];',
                'error_message' => 'PossiblyInvalidArrayAccess',
            ],
            'mixedArrayAccess' => [
                '<?php
                    /** @var mixed */
                    $a = [];
                    echo $a[0];',
                'error_message' => 'MixedArrayAccess',
                'error_level' => ['MixedAssignment'],
            ],
            'mixedArrayOffset' => [
                '<?php
                    /** @var mixed */
                    $a = 5;
                    echo [1, 2, 3, 4][$a];',
                'error_message' => 'MixedArrayOffset',
                'error_level' => ['MixedAssignment'],
            ],
            'nullArrayAccess' => [
                '<?php
                    $a = null;
                    echo $a[0];',
                'error_message' => 'NullArrayAccess',
            ],
            'possiblyNullArrayAccess' => [
                '<?php
                    $a = rand(0, 1) ? [1, 2] : null;
                    echo $a[0];',
                'error_message' => 'PossiblyNullArrayAccess',
            ],
            'specificErrorMessage' => [
                '<?php
                    $params = ["key" => "value"];
                    echo $params["fieldName"];',
                'error_message' => 'InvalidArrayOffset - src' . DIRECTORY_SEPARATOR . 'somefile.php:3 - Cannot access '
                    . 'value on variable $params using offset value of',
            ],
            'missingArrayOffsetAfterUnset' => [
                '<?php
                    $x = ["a" => "value", "b" => "value"];
                    unset($x["a"]);
                    echo $x["a"];',
                'error_message' => 'InvalidArrayOffset',
            ],
            'noImpossibleStringAccess' => [
                '<?php
                    function foo(string $s) : void {
                        echo $s[0][1];
                    }',
                'error_message' => 'InvalidArrayOffset',
            ],
            'mixedKeyStdClassOffset' => [
                '<?php
                    function example(array $y) : void {
                        echo $y[new stdClass()];
                    }',
                'error_message' => 'InvalidArrayOffset',
            ],
            'toStringOffset' => [
                '<?php
                    class Foo {
                        public function __toString() {
                            return "Foo";
                        }
                    }

                    $a = ["Foo" => "bar"];
                    echo $a[new Foo];',
                'error_message' => 'InvalidArrayOffset',
            ],
            'possiblyUndefinedIntArrayOffet' => [
                '<?php
                    /** @var array{0?:string} */
                    $entry = ["a"];

                    [$elt] = $entry;',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'possiblyUndefinedStringArrayOffet' => [
                '<?php
                    /** @var array{a?:string} */
                    $entry = ["a"];

                    ["a" => $elt] = $entry;',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'possiblyInvalidMixedArrayOffset' => [
                '<?php
                    /**
                     * @param string|array $key
                     */
                    function foo(array $a, $key) : void {
                        echo $a[$key];
                    }',
                'error_message' => 'PossiblyInvalidArrayOffset',
            ],
            'possiblyInvalidMixedUnionArrayOffset' => [
                '<?php
                    function foo(?array $index): void {
                        if (!$index) {
                            $index = ["foo", []];
                        }
                        $index[1][] = "bar";
                    }',
                'error_message' => 'PossiblyInvalidArrayOffset',
                'error_level' => ['MixedArrayAssignment'],
            ],
        ];
    }
}
