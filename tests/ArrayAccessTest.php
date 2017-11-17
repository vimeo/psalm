<?php
namespace Psalm\Tests;

class ArrayAccessTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'instanceOfStringOffset' => [
                '<?php
                    class A {
                        public function fooFoo() : void { }
                    }
                    function bar (array $a) : void {
                        if ($a["a"] instanceof A) {
                            $a["a"]->fooFoo();
                        }
                    }',
            ],
            'instanceOfIntOffset' => [
                '<?php
                    class A {
                        public function fooFoo() : void { }
                    }
                    function bar (array $a) : void {
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
                    function bar (array $a) : string {
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
            'notEmptyIntOffset' => [
                '<?php
                    /**
                     * @param  array<string>  $a
                     */
                    function bar (array $a) : string {
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
                    function takesInt(int $i) : void {}
                    function takesString(string $s) : void {}
                    function takesBool(bool $b) : void {}

                    /**
                     * @param array{int, string, bool} $b
                     */
                    function a(array $b) : void {
                        takesInt($b[0]);
                        takesString($b[1]);
                        takesBool($b[2]);
                    }',
            ],
            'updateStringIntKey' => [
                '<?php
                    $string = "c";
                    $int = 5;

                    $a = [];

                    $a["a"] = 5;
                    $a[0] = 3;

                    $b = [];

                    $b[$string] = 5;
                    $b[0] = 3;

                    $c = [];

                    $c[0] = 3;
                    $c[$string] = 5;

                    $d = [];

                    $d[$int] = 3;
                    $d["a"] = 5;

                    $e = [];

                    $e[$int] = 3;
                    $e[$string] = 5;',
                'assertions' => [
                    '$a' => 'array<string|int, int>',
                    '$b' => 'array<string|int, int>',
                    '$c' => 'array<int|string, int>',
                    '$d' => 'array<int|string, int>',
                    '$e' => 'array<int|string, int>',
                ],
            ],
            'updateStringIntKeyWithIntRootAndNumberOffset' => [
                '<?php
                    $string = "c";
                    $int = 5;

                    $a = [];

                    $a[0]["a"] = 5;
                    $a[0][0] = 3;',
                'assertions' => [
                    '$a' => 'array<int, array<string|int, int>>',
                ],
            ],
            'updateStringIntKeyWithIntRoot' => [
                '<?php
                    $string = "c";
                    $int = 5;

                    $b = [];

                    $b[0][$string] = 5;
                    $b[0][0] = 3;

                    $c = [];

                    $c[0][0] = 3;
                    $c[0][$string] = 5;

                    $d = [];

                    $d[0][$int] = 3;
                    $d[0]["a"] = 5;

                    $e = [];

                    $e[0][$int] = 3;
                    $e[0][$string] = 5;',
                'assertions' => [
                    '$b' => 'array<int, array<string|int, int>>',
                    '$c' => 'array<int, array<int|string, int>>',
                    '$d' => 'array<int, array<int|string, int>>',
                    '$e' => 'array<int, array<int|string, int>>',
                ],
            ],
            'updateStringIntKeyWithObjectLikeRootAndNumberOffset' => [
                '<?php
                    $string = "c";
                    $int = 5;

                    $a = [];

                    $a["root"]["a"] = 5;
                    $a["root"][0] = 3;',
                'assertions' => [
                    '$a' => 'array{root:array<string|int, int>}',
                ],
            ],
            'updateStringIntKeyWithObjectLikeRoot' => [
                '<?php
                    $string = "c";
                    $int = 5;

                    $b = [];

                    $b["root"][$string] = 5;
                    $b["root"][0] = 3;

                    $c = [];

                    $c["root"][0] = 3;
                    $c["root"][$string] = 5;

                    $d = [];

                    $d["root"][$int] = 3;
                    $d["root"]["a"] = 5;

                    $e = [];

                    $e["root"][$int] = 3;
                    $e["root"][$string] = 5;',
                'assertions' => [
                    '$b' => 'array{root:array<string|int, int>}',
                    '$c' => 'array{root:array<int|string, int>}',
                    '$d' => 'array{root:array<int|string, int>}',
                    '$e' => 'array{root:array<int|string, int>}',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
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
        ];
    }
}
