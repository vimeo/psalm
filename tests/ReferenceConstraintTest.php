<?php
namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class ReferenceConstraintTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
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
            'paramOutRefineType' => [
                '<?php
                    /**
                     * @param-out string $s
                     */
                    function addFoo(?string &$s) : void {
                        if ($s === null) {
                            $s = "hello";
                        }
                        $s .= "foo";
                    }

                    addFoo($a);

                    echo strlen($a);',
            ],
            'paramOutChangeType' => [
                '<?php
                    /**
                     * @param-out int $s
                     */
                    function addFoo(?string &$s) : void {
                        if ($s === null) {
                            $s = 5;
                            return;
                        }
                        $s = 4;
                    }

                    addFoo($a);',
                'assertions' => [
                    '$a' => 'int',
                ],
            ],
            'paramOutReturn' => [
                '<?php
                    /**
                     * @param-out bool $s
                     */
                    function foo(?bool &$s) : void {
                        $s = true;
                    }

                    $b = false;
                    foo($b);',
                'assertions' => [
                    '$b' => 'bool',
                ],
            ],
            'dontChangeThis' => [
                '<?php
                    interface I {}
                    class C implements I {
                        public function foo() : self {
                            bar($this);
                            return $this;
                        }
                    }

                    function bar(I &$i) : void {}',
            ],
            'notEmptyArrayAccess' => [
                '<?php
                    /**
                     * @param mixed $value
                     * @param-out int $value
                     */
                    function addValue(&$value) : void {
                        $value = 5;
                    }

                    $foo = [];

                    addValue($foo["a"]);'
            ],
            'paramOutArrayDefaultNullWithThrow' => [
                '<?php
                    /**
                     * @param-out array{errors: int}|null $info
                     */
                    function idnToAsci(?array &$info = null): void {
                        if (rand(0, 1)) {
                            $info = null;
                        }

                        throw new \UnexpectedValueException();
                    }'
            ],
            'specificArrayWalkBehavior' => [
                '<?php
                    function withArrayWalk(array &$val): void {
                        array_walk($val, /** @param mixed $arg */ function (&$arg): void {});
                    }
                    function withArrayWalkRecursive(array &$val): void {
                        array_walk_recursive($val, /** @param mixed $arg */ function (&$arg): void {});
                    }'
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
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
            'invalidDocblockForBadAnnotation' => [
                '<?php
                    /**
                     * @param-out array<a(),bool> $ar
                     */
                    function foo(array &$ar) : void {}',
                'error_message' => 'InvalidDocblock',
            ],
            'preventTernaryPassedByReference' => [
                '<?php
                    /**
                     * @param string $p
                     */
                    function b(&$p): string {
                        return $p;
                    }

                    function main(bool $a, string $b, string $c): void {
                        b($a ? $b : $c);
                    }',
                'error_message' => 'InvalidPassByReference',
            ],
        ];
    }
}
