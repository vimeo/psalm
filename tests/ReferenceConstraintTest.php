<?php
namespace Psalm\Tests;

class ReferenceConstraintTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return array
     */
    public function providerValidCodeParse()
    {
        return [
            'functionParameterNoViolation' => [
                '<?php
                    /** @return void */
                    function changeInt(int &$a) {
                      $a = 5;
                    }',
            ],
            'dontAllowByRefVarToBeAltered' => [
                '<?php
                    /**
                     * @param ?string $str
                     * @psalm-suppress PossiblyNullArgument
                     */
                    function nullable_ref_modifier(&$str): void {
                        if (strlen($str) > 5) {
                            $str = null;
                        }
                    }',
            ],
            'trackFunctionReturnRefs' => [
                '<?php
                    class A {
                        /** @var string */
                        public $foo = "bar";

                        public function &getString() : string {
                            return $this->foo;
                        }
                    }

                    function useString(string &$s) : void {}
                    $a = new A();

                    useString($a->getString());',
            ],
            'makeByRefUseMixed' => [
                '<?php
                    function s(?string $p): void {}

                    $var = 1;
                    $callback = function() use(&$var): void {
                        s($var);
                    };
                    $var = null;
                    $callback();',
                'assertions' => [],
                'error_levels' => ['MixedArgument'],
            ],
            'assignByRefToMixed' => [
                '<?php
                    function testRef() : array {
                        $result = [];
                        foreach ([1, 2, 1] as $v) {
                            $x = &$result;
                            if (!isset($x[$v])) {
                                $x[$v] = 0;
                            }
                            $x[$v] ++;
                        }
                        return $result;
                    }',
                'assertions' => [],
                'error_levels' => [
                    'MixedAssignment',
                    'MixedArrayAccess',
                    'MixedReturnStatement',
                    'MixedInferredReturnType',
                    'MixedOperand',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerInvalidCodeParse()
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
