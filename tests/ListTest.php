<?php
namespace Psalm\Tests;

class ListTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'simpleVars' => [
                '<?php
                    list($a, $b) = ["a", "b"];',
                'assertions' => [
                    '$a' => 'string',
                    '$b' => 'string',
                ],
            ],
            'simpleVarsWithSeparateTypes' => [
                '<?php
                    list($a, $b) = ["a", 2];',
                'assertions' => [
                    '$a' => 'string',
                    '$b' => 'int',
                ],
            ],
            'simpleVarsWithSeparateTypesInVar' => [
                '<?php
                    $bar = ["a", 2];
                    list($a, $b) = $bar;',
                'assertions' => [
                    '$a' => 'int|string',
                    '$b' => 'int|string',
                ],
            ],
            'thisVar' => [
                '<?php
                    class A {
                        /** @var string */
                        public $a = "";

                        /** @var string */
                        public $b = "";

                        public function fooFoo() : string
                        {
                            list($this->a, $this->b) = ["a", "b"];

                            return $this->a;
                        }
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
            'thisVarWithBadType' => [
                '<?php
                    class A {
                        /** @var int */
                        public $a = 0;

                        /** @var string */
                        public $b = "";

                        public function fooFoo() : string
                        {
                            list($this->a, $this->b) = ["a", "b"];

                            return $this->a;
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignment - src/somefile.php:11',
            ],
        ];
    }
}
