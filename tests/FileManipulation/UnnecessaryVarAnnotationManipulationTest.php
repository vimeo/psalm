<?php

declare(strict_types=1);

namespace Psalm\Tests\FileManipulation;

class UnnecessaryVarAnnotationManipulationTest extends FileManipulationTestCase
{
    public function providerValidCodeParse(): array
    {
        return [
            'removeSingleLineVarAnnotation' => [
                'input' => '<?php
                    function foo() : string {
                        return "hello";
                    }

                    /** @var string */
                    $a = foo();

                    /** @var "a"|"b" */
                    $b = foo();',
                'output' => '<?php
                    function foo() : string {
                        return "hello";
                    }

                    $a = foo();

                    /** @var "a"|"b" */
                    $b = foo();',
                'php_version' => '5.6',
                'issues_to_fix' => ['UnnecessaryVarAnnotation'],
                'safe_types' => true,
            ],
            'removeSingleLineVarAnnotationFlipped' => [
                'input' => '<?php
                    function foo() : string {
                        return "hello";
                    }

                    /** @var "a"|"b" */
                    $b = foo();

                    /** @var string */
                    $a = foo();',
                'output' => '<?php
                    function foo() : string {
                        return "hello";
                    }

                    /** @var "a"|"b" */
                    $b = foo();

                    $a = foo();',
                'php_version' => '5.6',
                'issues_to_fix' => ['UnnecessaryVarAnnotation'],
                'safe_types' => true,
            ],
            'removeSingleLineVarAnnotationAndType' => [
                'input' => '<?php
                    function foo() : string {
                        return "hello";
                    }

                    /** @var string $a */
                    $a = foo();

                    /** @var "a"|"b" */
                    $b = foo();',
                'output' => '<?php
                    function foo() : string {
                        return "hello";
                    }

                    $a = foo();

                    /** @var "a"|"b" */
                    $b = foo();',
                'php_version' => '5.6',
                'issues_to_fix' => ['UnnecessaryVarAnnotation'],
                'safe_types' => true,
            ],
            'removeMultipleLineVarAnnotation' => [
                'input' => '<?php
                    function foo() : string {
                        return "hello";
                    }

                    /**
                     * @var string
                     */
                    $a = foo();

                    /** @var "a"|"b" */
                    $b = foo();',
                'output' => '<?php
                    function foo() : string {
                        return "hello";
                    }

                    $a = foo();

                    /** @var "a"|"b" */
                    $b = foo();',
                'php_version' => '5.6',
                'issues_to_fix' => ['UnnecessaryVarAnnotation'],
                'safe_types' => true,
            ],
            'removeMultipleLineVarAnnotationKeepComment' => [
                'input' => '<?php
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
                'output' => '<?php
                    function foo() : string {
                        return "hello";
                    }

                    /**
                     * this comment should stay
                     */
                    $a = foo();

                    /** @var "a"|"b" */
                    $b = foo();',
                'php_version' => '5.6',
                'issues_to_fix' => ['UnnecessaryVarAnnotation'],
                'safe_types' => true,
            ],
            'removeMultipleLineVarAnnotationOnce' => [
                'input' => '<?php
                    /** @return string[] */
                    function foo() : array {
                        return ["hello"];
                    }

                    /**
                     * @var int $k
                     * @var string $v
                     */
                    foreach (foo() as $k => $v) {}',
                'output' => '<?php
                    /** @return string[] */
                    function foo() : array {
                        return ["hello"];
                    }

                    /**
                     * @var int $k
                     */
                    foreach (foo() as $k => $v) {}',
                'php_version' => '5.6',
                'issues_to_fix' => ['UnnecessaryVarAnnotation'],
                'safe_types' => true,
            ],
        ];
    }
}
