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
                    ['string' => '$a'],
                    ['string' => '$b']
                ]
            ],
            'simpleVarsWithSeparateTypes' => [
                '<?php
                    list($a, $b) = ["a", 2];',
                'assertions' => [
                    ['string' => '$a'],
                    ['int' => '$b']
                ]
            ],
            'simpleVarsWithSeparateTypesInVar' => [
                '<?php
                    $bar = ["a", 2];
                    list($a, $b) = $bar;',
                'assertions' => [
                    ['int|string' => '$a'],
                    ['int|string' => '$b']
                ]
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
                    }'
            ]
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
                'error_message' => 'InvalidPropertyAssignment - somefile.php:11'
            ]
        ];
    }
}
