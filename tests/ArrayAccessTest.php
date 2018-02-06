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
            'specificErrorMessage' => [
                '<?php
                    $params = ["key" => "value"];
                    echo $params["fieldName"];',
                'error_message' => 'InvalidArrayOffset - src/somefile.php:3 - Cannot access '
                    . 'value on variable $params using offset value of',
            ],
        ];
    }
}
