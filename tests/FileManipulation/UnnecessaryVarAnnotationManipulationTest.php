<?php
namespace Psalm\Tests\FileManipulation;

class UnnecessaryVarAnnotationManipulationTest extends FileManipulationTestCase
{
    /**
     * @return array<string,array{string,string,string,string[],bool}>
     */
    public function providerValidCodeParse(): array
    {
        return [
            'removeSingleLineVarAnnotation' => [
                '<?php
                    function foo() : string {
                        return "hello";
                    }

                    /** @var string */
                    $a = foo();

                    /** @var "a"|"b" */
                    $b = foo();',
                '<?php
                    function foo() : string {
                        return "hello";
                    }

                    $a = foo();

                    /** @var "a"|"b" */
                    $b = foo();',
                '5.6',
                ['UnnecessaryVarAnnotation'],
                true,
            ],
            'removeSingleLineVarAnnotationFlipped' => [
                '<?php
                    function foo() : string {
                        return "hello";
                    }

                    /** @var "a"|"b" */
                    $b = foo();

                    /** @var string */
                    $a = foo();',
                '<?php
                    function foo() : string {
                        return "hello";
                    }

                    /** @var "a"|"b" */
                    $b = foo();

                    $a = foo();',
                '5.6',
                ['UnnecessaryVarAnnotation'],
                true,
            ],
            'removeSingleLineVarAnnotationAndType' => [
                '<?php
                    function foo() : string {
                        return "hello";
                    }

                    /** @var string $a */
                    $a = foo();

                    /** @var "a"|"b" */
                    $b = foo();',
                '<?php
                    function foo() : string {
                        return "hello";
                    }

                    $a = foo();

                    /** @var "a"|"b" */
                    $b = foo();',
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
                    $a = foo();

                    /** @var "a"|"b" */
                    $b = foo();',
                '<?php
                    function foo() : string {
                        return "hello";
                    }

                    $a = foo();

                    /** @var "a"|"b" */
                    $b = foo();',
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
                    $a = foo();

                    /** @var "a"|"b" */
                    $b = foo();',
                '<?php
                    function foo() : string {
                        return "hello";
                    }

                    /**
                     * this comment should stay
                     */
                    $a = foo();

                    /** @var "a"|"b" */
                    $b = foo();',
                '5.6',
                ['UnnecessaryVarAnnotation'],
                true,
            ],
            'removeMultipleLineVarAnnotationOnce' => [
                '<?php
                    /** @return string[] */
                    function foo() : array {
                        return ["hello"];
                    }

                    /**
                     * @var int $k
                     * @var string $v
                     */
                    foreach (foo() as $k => $v) {}',
                '<?php
                    /** @return string[] */
                    function foo() : array {
                        return ["hello"];
                    }

                    /**
                     * @var int $k
                     */
                    foreach (foo() as $k => $v) {}',
                '5.6',
                ['UnnecessaryVarAnnotation'],
                true,
            ],
        ];
    }
}
