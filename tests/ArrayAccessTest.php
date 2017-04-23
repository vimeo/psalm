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
            'instance-of-string-offset' => [
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
            'instance-of-int-offset' => [
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
            'not-empty-string-offset' => [
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
            'not-empty-int-offset' => [
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
            'ignore-possibly-null-array-access' => [
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
            'invalid-array-access' => [
                '<?php
                    $a = 5;
                    echo $a[0];',
                'error_message' => 'InvalidArrayAccess'
            ],
            'mixed-array-access' => [
                '<?php
                    /** @var mixed */
                    $a = [];
                    echo $a[0];',
                'error_message' => 'MixedArrayAccess',
                'error_level' => ['MixedAssignment']
            ],
            'mixed-array-offset' => [
                '<?php
                    /** @var mixed */
                    $a = 5;
                    echo [1, 2, 3, 4][$a];',
                'error_message' => 'MixedArrayOffset',
                'error_level' => ['MixedAssignment']
            ],
            'null-array-access' => [
                '<?php
                    $a = null;
                    echo $a[0];',
                'error_message' => 'NullArrayAccess'
            ],
            'possibly-null-array-access' => [
                '<?php
                    $a = rand(0, 1) ? [1, 2] : null;
                    echo $a[0];',
                'error_message' => 'PossiblyNullArrayAccess'
            ]
        ];
    }
}
