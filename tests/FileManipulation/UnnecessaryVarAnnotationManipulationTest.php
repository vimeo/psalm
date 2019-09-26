<?php
namespace Psalm\Tests\FileManipulation;

use const PHP_VERSION;

class UnnecessaryVarAnnotationManipulationTest extends FileManipulationTest
{
    /**
     * @return array<string,array{string,string,string,string[],bool}>
     */
    public function providerValidCodeParse()
    {
        return [
            'removeSingleLineVarAnnotation' => [
                '<?php
                    function foo() : string {
                        return "hello";
                    }

                    /** @var string */
                    $a = foo();',
                '<?php
                    function foo() : string {
                        return "hello";
                    }

                    $a = foo();',
                '5.6',
                ['UnnecessaryVarAnnotation'],
                true,
            ],
            'removeMultipleLineVarAnnotation' => [
                '<?php
                    function foo() : string {
                        return "hello";
                    }

                    /**
                     * @var string
                     */
                    $a = foo();',
                '<?php
                    function foo() : string {
                        return "hello";
                    }

                    $a = foo();',
                '5.6',
                ['UnnecessaryVarAnnotation'],
                true,
            ],
            'removeMultipleLineVarAnnotationKeepComment' => [
                '<?php
                    function foo() : string {
                        return "hello";
                    }

                    /**
                     * @var string
                     * this comment should stay
                     */
                    $a = foo();',
                '<?php
                    function foo() : string {
                        return "hello";
                    }

                    /**
                     * this comment should stay
                     */
                    $a = foo();',
                '5.6',
                ['UnnecessaryVarAnnotation'],
                true,
            ],
        ];
    }
}
