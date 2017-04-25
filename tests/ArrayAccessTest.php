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
                    }'
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
                    }'
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
                    }'
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
                    }'
            ],
            'ignorePossiblyNullArrayAccess' => [
                '<?php
                    $a = rand(0, 1) ? [1, 2] : null;
                    echo $a[0];',
                'assertions' => [],
                'error_levels' => ['PossiblyNullArrayAccess']
            ]
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
                'error_message' => 'InvalidArrayAccess'
            ],
            'mixedArrayAccess' => [
                '<?php
                    /** @var mixed */
                    $a = [];
                    echo $a[0];',
                'error_message' => 'MixedArrayAccess',
                'error_level' => ['MixedAssignment']
            ],
            'mixedArrayOffset' => [
                '<?php
                    /** @var mixed */
                    $a = 5;
                    echo [1, 2, 3, 4][$a];',
                'error_message' => 'MixedArrayOffset',
                'error_level' => ['MixedAssignment']
            ],
            'nullArrayAccess' => [
                '<?php
                    $a = null;
                    echo $a[0];',
                'error_message' => 'NullArrayAccess'
            ],
            'possiblyNullArrayAccess' => [
                '<?php
                    $a = rand(0, 1) ? [1, 2] : null;
                    echo $a[0];',
                'error_message' => 'PossiblyNullArrayAccess'
            ]
        ];
    }
}
