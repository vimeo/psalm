<?php
namespace Psalm\Tests;

class ReferenceConstraintTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'functionParameterNoViolation' => [
                '<?php
                    /** @return void */
                    function changeInt(int &$a) {
                      $a = 5;
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
            'functionParameterViolation' => [
                '<?php
                    /** @return void */
                    function changeInt(int &$a) {
                      $a = "hello";
                    }',
                'error_message' => 'ReferenceConstraintViolation',
            ],
            'classMethodParameterViolation' => [
                '<?php
                    class A {
                      /** @var int */
                      private $foo;
            
                        public function __construct(int &$foo) {
                            $this->foo = &$foo;
                            $foo = "hello";
                        }
                    }
            
                    $bar = 5;
                    $a = new A($bar); // $bar is constrained to an int
                    $bar = null; // ReferenceConstraintViolation issue emitted',
                'error_message' => 'ReferenceConstraintViolation',
            ],
            'classMethodParameterViolationInPostAssignment' => [
                '<?php
                    class A {
                      /** @var int */
                      private $foo;
            
                        public function __construct(int &$foo) {
                            $this->foo = &$foo;
                        }
                    }
            
                    $bar = 5;
                    $a = new A($bar);
                    $bar = null;',
                'error_message' => 'ReferenceConstraintViolation',
            ],
            'contradictoryReferenceConstraints' => [
                '<?php
                    class A {
                        /** @var int */
                        private $foo;
            
                        public function __construct(int &$foo) {
                            $this->foo = &$foo;
                        }
                    }
            
                    class B {
                        /** @var string */
                        private $bar;
            
                        public function __construct(string &$bar) {
                            $this->bar = &$bar;
                        }
                    }
            
                    if (rand(0, 1)) {
                        $v = 5;
                        $c = (new A($v)); // $v is constrained to an int
                    } else {
                        $v = "hello";
                        $c =  (new B($v)); // $v is constrained to a string
                    }
            
                    $v = 8;',
                'error_message' => 'ConflictingReferenceConstraint',
            ],
        ];
    }
}
