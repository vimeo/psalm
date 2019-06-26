<?php
namespace Psalm\Tests;

use const DIRECTORY_SEPARATOR;

class ArrayAccessTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
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
                'error_levels' => ['MixedArgument', 'MixedArrayAccess'],
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
                    $e = $doc->getElementsByTagName("node")[0];',
                [
                    '$e' => 'null|DOMElement',
                ],
            ],
            'getOnArrayAcccess' => [
                '<?php
                    /** @param ArrayAccess<int, string> $a */
                    function foo(ArrayAccess $a) : string {
                        return $a[0];
                    }',
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
                    }',
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
            'arrayAccessAfterPassByref' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                'error_levels' => ['MixedArrayTypeCoercion'],
            ],
            'allowNegativeStringOffset' => [
                '<?php
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
                '<?php
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
                [],
                ['MixedArrayAccess', 'MixedArgument'],
            ],
            'arrayAccessOnObjectWithNullGet' => [
                '<?php
                    class C implements ArrayAccess
                    {
                        /**
                         * @var array<C|scalar>
                         */
                        protected $data = [];

                        /**
                         * @param array<scalar|array> $array
                         * @psalm-suppress MixedAssignment
                         * @psalm-suppress MixedTypeCoercion
                         */
                        public function __construct(array $array)
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
                         * @param ?string $name
                         * @param scalar|array $value
                         * @psalm-suppress MixedTypeCoercion
                         */
                        public function offsetSet($name, $value) : void
                        {
                            if (is_array($value)) {
                                $value = new static($value);
                            }

                            if (null === $name) {
                                $this->data[] = $value;
                            } else {
                                $this->data[$name] = $value;
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
                    }

                    $array = new C([]);
                    $array["key"] = [];
                    /** @psalm-suppress PossiblyInvalidArrayAssignment */
                    $array["key"][] = "testing";'
            ],
            'singleLetterOffset' => [
                '<?php
                    ["s" => "str"]["str"[0]];',
            ],
            'assertConstantOffsetsInMethod' => [
                '<?php
                    class C {
                        public const ARR = [
                            "a" => ["foo" => true],
                            "b" => []
                        ];

                        public function bar(string $key): bool {
                            if (!array_key_exists($key, self::ARR) || !array_key_exists("foo", self::ARR[$key])) {
                                return false;
                            }

                            return self::ARR[$key]["foo"];
                        }
                    }',
                [],
                ['MixedReturnStatement', 'MixedInferredReturnType']
            ],
            'assertSelfClassConstantOffsetsInFunction' => [
                '<?php
                    namespace Ns;

                    class C {
                        public const ARR = [
                            "a" => ["foo" => true],
                            "b" => []
                        ];

                        public function bar(?string $key): bool {
                            if ($key === null || !array_key_exists($key, self::ARR) || !array_key_exists("foo", self::ARR[$key])) {
                                return false;
                            }

                            return self::ARR[$key]["foo"];
                        }
                    }',
                [],
                ['MixedReturnStatement', 'MixedInferredReturnType']
            ],
            'assertNamedClassConstantOffsetsInFunction' => [
                '<?php
                    namespace Ns;

                    class C {
                        public const ARR = [
                            "a" => ["foo" => true],
                            "b" => [],
                        ];
                    }

                    function bar(?string $key): bool {
                        if ($key === null || !array_key_exists($key, C::ARR) || !array_key_exists("foo", C::ARR[$key])) {
                            return false;
                        }

                        return C::ARR[$key]["foo"];
                    }',
                [],
                ['MixedReturnStatement', 'MixedInferredReturnType']
            ],
            'arrayAccessAfterByRefArrayOffsetAssignment' => [
                '<?php
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
                [],
                ['MixedArrayAccess'],
            ],
            'byRefArrayAccessWithoutKnownVarNoNotice' => [
                '<?php
                    $a = new stdClass();
                    /** @psalm-suppress MixedPropertyFetch */
                    print_r([&$a->foo->bar]);',
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
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
                'error_message' => 'InvalidArrayOffset - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:26 - Cannot access '
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
            'arrayAccessOnIterable' => [
                '<?php
                    function foo(iterable $i) {
                        echo $i[0];
                    }',
                'error_message' => 'InvalidArrayAccess',
            ],
            'arrayKeyCannotBeBool' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    $a = "hello";
                    echo $a[-6];',
                'error_message' => 'InvalidArrayOffset',
            ],
            'emptyStringAccess' => [
                '<?php
                    $a = "";
                    echo $a[0];',
                'error_message' => 'InvalidArrayOffset',
            ],
            'recogniseBadVar' => [
                '<?php
                    /** @psalm-suppress MixedAssignment */
                    $array = $_GET["foo"] ?? [];

                    $array[$a] = "b";',
                'error_message' => 'UndefinedGlobalVariable',
            ],
        ];
    }
}
