<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

class ListTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'simpleVars' => [
                'code' => '<?php
                    list($a, $b) = ["a", "b"];',
                'assertions' => [
                    '$a' => 'string',
                    '$b' => 'string',
                ],
            ],
            'simpleVarsWithSeparateTypes' => [
                'code' => '<?php
                    list($a, $b) = ["a", 2];',
                'assertions' => [
                    '$a' => 'string',
                    '$b' => 'int',
                ],
            ],
            'simpleVarsWithSeparateTypesInVar' => [
                'code' => '<?php
                    $bar = ["a", 2];
                    list($a, $b) = $bar;',
                'assertions' => [
                    '$a' => 'string',
                    '$b' => 'int',
                ],
            ],
            'thisVar' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        public $a = "";

                        /** @var string */
                        public $b = "";

                        public function fooFoo(): string
                        {
                            list($this->a, $this->b) = ["a", "b"];

                            return $this->a;
                        }
                    }',
            ],
            'mixedNestedAssignment' => [
                'code' => '<?php
                    /** @psalm-suppress MissingReturnType */
                    function getMixed() {}

                    /**
                     * @psalm-suppress MixedArrayAccess
                     * @psalm-suppress MixedAssignment
                     */
                    list($a, list($b, $c)) = getMixed();',
                'assertions' => [
                    '$a' => 'mixed',
                    '$b' => 'mixed',
                    '$c' => 'mixed',
                ],
            ],
            'explicitLiteralKey' => [
                'code' => '<?php
                    /** @param list<int> $a */
                    function takesList($a): void {}

                    $a = [1, 1 => 2, 3];
                    takesList($a);',
            ],
            'simpleTypeInfererNonEmptyList' => [
                'code' => '<?php

                    class Foo {
                        public const VARS = [
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "y",
                        ];
                    }

                    /** @param list<string> $vars */
                    function foo(array $vars): void {
                        print_r($vars);
                    }

                    foo(Foo::VARS);
                    ',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'thisVarWithBadType' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $a = 0;

                        /** @var string */
                        public $b = "";

                        public function fooFoo(): string
                        {
                            list($this->a, $this->b) = ["a", "b"];

                            return $this->a;
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue - src' . DIRECTORY_SEPARATOR . 'somefile.php:11',
            ],
            'explicitVariableKey' => [
                'code' => '<?php
                    /** @param list<int> $a */
                    function takesList($a): void {}

                    /** @return array-key */
                    function getKey() {
                        return 0;
                    }

                    $a = [getKey() => 1];
                    takesList($a);',
                'error_message' => 'ArgumentTypeCoercion',
            ],
        ];
    }
}
