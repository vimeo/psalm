<?php
namespace Psalm\Tests;

class TypeTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'nullableMethodWithTernaryGuard' => [
                '<?php
                    class A {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(A $a = null) {
                            $b = $a ? $a->fooFoo() : null;
                        }
                    }',
            ],
            'nullableMethodWithTernaryIfNullGuard' => [
                '<?php
                    class A {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(A $a = null) {
                            $b = $a === null ? null : $a->fooFoo();
                        }
                    }',
            ],
            'nullableMethodWithTernaryEmptyGuard' => [
                '<?php
                    class A {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(A $a = null) {
                            $b = empty($a) ? null : $a->fooFoo();
                        }
                    }',
            ],
            'nullableMethodWithTernaryIsNullGuard' => [
                '<?php
                    class A {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(A $a = null) {
                            $b = is_null($a) ? null : $a->fooFoo();
                        }
                    }',
            ],
            'nullableMethodWithIfGuard' => [
                '<?php
                    class A {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(A $a = null) {
                            if ($a) {
                                $a->fooFoo();
                            }
                        }
                    }',
            ],
            'nullableMethodWithTernaryGuardWithThis' => [
                '<?php
                    class A {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @var A|null */
                        public $a;

                        /** @return void */
                        public function barBar(A $a = null) {
                            $this->a = $a;
                            $b = $this->a ? $this->a->fooFoo() : null;
                        }
                    }',
            ],
            'nullableMethodWithTernaryIfNullGuardWithThis' => [
                '<?php
                    class A {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @var A|null */
                        public $a;

                        /** @return void */
                        public function barBar(A $a = null) {
                            $this->a = $a;
                            $b = $this->a === null ? null : $this->a->fooFoo();
                        }
                    }',
            ],
            'nullableMethodWithIfGuardWithThis' => [
                '<?php
                    class A {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @var A|null */
                        public $a;

                        /** @return void */
                        public function barBar(A $a = null) {
                            $this->a = $a;

                            if ($this->a) {
                                $this->a->fooFoo();
                            }
                        }
                    }',
            ],
            'nullableMethodWithExceptionThrown' => [
                '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(One $one = null) {
                            if (!$one) {
                                throw new Exception();
                            }

                            $one->fooFoo();
                        }
                    }',
            ],
            'nullableMethodWithRedefinitionAndElse' => [
                '<?php
                    class One {
                        /** @var int|null */
                        public $two;

                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(One $one = null) {
                            if (!$one) {
                                $one = new One();
                            }
                            else {
                                $one->two = 3;
                            }

                            $one->fooFoo();
                        }
                    }',
            ],
            'nullableMethodWithBooleanIfGuard' => [
                '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class Two {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(One $one = null, Two $two = null) {
                            if ($one && $two) {
                                $two->fooFoo();
                            }
                        }
                    }',
            ],
            'nullableMethodWithNonNullBooleanIfGuard' => [
                '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class Two {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(One $one = null, Two $two = null) {
                            if ($one !== null && $two) {
                                $one->fooFoo();
                            }
                        }
                    }',
            ],
            'nullableMethodWithNonNullBooleanIfGuardAndBooleanAnd' => [
                '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class Two {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(One $one = null, Two $two = null) {
                            if ($one !== null && ($two || 1 + 1 === 3)) {
                                $one->fooFoo();
                            }
                        }
                    }',
            ],
            'nullableMethodInConditionWithIfGuardBefore' => [
                '<?php
                    class One {
                        /** @var string */
                        public $a = "";

                        /** @return void */
                        public function fooFoo() {}
                    }

                    class Two {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(One $one = null, Two $two = null) {
                            if ($one === null) {
                                return;
                            }

                            if (!$one->a && $one->fooFoo()) {
                                // do something
                            }
                        }
                    }',
            ],
            'nullableMethodWithBooleanIfGuardBefore' => [
                '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class Two {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(One $one = null, Two $two = null) {
                            if ($one === null || $two === null) {
                                return;
                            }

                            $one->fooFoo();
                        }
                    }',
            ],
            'nullableMethodWithGuardedRedefinition' => [
                '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(One $one = null) {
                            if ($one === null) {
                                $one = new One();
                            }

                            $one->fooFoo();
                        }
                    }',
            ],
            'nullableMethodWithGuardedRedefinitionInElse' => [
                '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(One $one = null) {
                            if ($one) {
                                // do nothing
                            }
                            else {
                                $one = new One();
                            }

                            $one->fooFoo();
                        }
                    }',
            ],
            'nullableMethodWithGuardedNestedRedefinition' => [
                '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class Two {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(One $one = null) {
                            $a = 4;

                            if ($one === null) {
                                if ($a === 4) {
                                    $one = new One();
                                }
                                else {
                                    $one = new One();
                                }
                            }

                            $one->fooFoo();
                        }
                    }',
            ],
            'nullableMethodWithGuardedSwitchRedefinition' => [
                '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(One $one = null) {
                            $a = 4;

                            if ($one === null) {
                                switch ($a) {
                                    case 4:
                                        $one = new One();
                                        break;

                                    default:
                                        $one = new One();
                                        break;
                                }
                            }

                            $one->fooFoo();
                        }
                    }',
            ],
            'nullableMethodWithGuardedSwitchRedefinitionDueToException' => [
                '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /**
                         * @return void
                         */
                        public function barBar(One $one = null) {
                            $a = 4;

                            if ($one === null) {
                                switch ($a) {
                                    case 4:
                                        $one = new One();
                                        break;

                                    default:
                                        throw new \Exception("bad");
                                }
                            }

                            $one->fooFoo();
                        }
                    }',
            ],
            'nullableMethodWithGuardedSwitchThatAlwaysReturns' => [
                '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(One $one = null) {
                            $a = 4;

                            if ($one === null) {
                                switch ($a) {
                                    case 4:
                                        return;

                                    default:
                                        return;
                                }
                            }

                            $one->fooFoo();
                        }
                    }',
            ],
            'nullableMethodWithGuardedNestedRedefinitionWithReturn' => [
                '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(One $one = null) {
                            $a = 4;

                            if ($one === null) {
                                if ($a === 4) {
                                    $one = new One();
                                    return;
                                }
                                else {
                                    $one = new One();
                                }
                            }

                            $one->fooFoo();
                        }
                    }',
            ],
            'nullableMethodWithGuardedNestedRedefinitionWithElseReturn' => [
                '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(One $one = null) {
                            $a = 4;

                            if ($one === null) {
                                if ($a === 4) {
                                    $one = new One();
                                }
                                else {
                                    $one = new One();
                                    return;
                                }
                            }

                            $one->fooFoo();
                        }
                    }',
            ],
            'nullableMethodWithGuardedNestedRedefinitionWithElseifReturn' => [
                '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(One $one = null) {
                            $a = 4;

                            if ($one === null) {
                                if ($a === 4) {
                                    $one = new One();
                                }
                                else if ($a === 3) {
                                    // do nothing
                                    return;
                                }
                                else {
                                    $one = new One();
                                }
                            }

                            $one->fooFoo();
                        }
                    }',
            ],
            'nullableMethodWithGuardedSwitchBreak' => [
                '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(One $one = null) {
                            $a = 4;

                            switch ($a) {
                                case 4:
                                    if ($one === null) {
                                        break;
                                    }

                                    $one->fooFoo();
                                    break;
                            }
                        }
                    }',
            ],
            'nullableMethodWithGuardedRedefinitionOnThis' => [
                '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @var One|null */
                        public $one;

                        /** @return void */
                        public function barBar(One $one = null) {
                            $this->one = $one;

                            if ($this->one === null) {
                                $this->one = new One();
                            }

                            $this->one->fooFoo();
                        }
                    }',
            ],
            'arrayUnionTypeAssertion' => [
                '<?php
                    $ids = (1 + 1 === 2) ? [] : null;

                    if ($ids === null) {
                        $ids = [];
                    }',
                'assertions' => [
                    '$ids' => 'array<empty, empty>',
                ],
            ],
            'arrayUnionTypeAssertionWithIsArray' => [
                '<?php
                    $ids = (1 + 1 === 2) ? [] : null;

                    if (!is_array($ids)) {
                        $ids = [];
                    }',
                'assertions' => [
                    '$ids' => 'array<empty, empty>',
                ],
            ],
            '2dArrayUnionTypeAssertionWithIsArray' => [
                '<?php
                    /** @return array<array<string>>|null */
                    function foo() {
                        $ids = rand(0, 1) ? [["hello"]] : null;

                        if (is_array($ids)) {
                            return $ids;
                        }

                        return null;
                    }',
            ],
            'variableReassignment' => [
                '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class Two {
                        /** @return void */
                        public function barBar() {}
                    }

                    $one = new One();

                    $one = new Two();

                    $one->barBar();',
            ],
            'variableReassignmentInIf' => [
                '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class Two {
                        /** @return void */
                        public function barBar() {}
                    }

                    $one = new One();

                    if (1 + 1 === 2) {
                        $one = new Two();

                        $one->barBar();
                    }',
            ],
            'unionTypeFlow' => [
                '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class Two {
                        /** @return void */
                        public function barBar() {}
                    }

                    class Three {
                        /** @return void */
                        public function baz() {}
                    }

                    /** @var One|Two|Three|null */
                    $var = null;

                    if ($var instanceof One) {
                        $var->fooFoo();
                    }
                    else {
                        if ($var instanceof Two) {
                            $var->barBar();
                        }
                        else if ($var) {
                            $var->baz();
                        }
                    }',
            ],
            'unionTypeFlowWithThrow' => [
                '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    /** @return void */
                    function a(One $var = null) {
                        if (!$var) {
                            throw new \Exception("some exception");
                        }
                        else {
                            $var->fooFoo();
                        }
                    }',
            ],
            'unionTypeFlowWithElseif' => [
                '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    /** @var One|null */
                    $var = null;

                    if (rand(0,100) === 5) {

                    }
                    elseif (!$var) {

                    }
                    else {
                        $var->fooFoo();
                    }',
            ],
            'typedAdjustment' => [
                '<?php
                    $var = 0;

                    if (5 + 3 === 8) {
                        $var = "hello";
                    }

                    echo $var;',
                'assertions' => [
                    '$var' => 'int|string',
                ],
            ],
            'typeMixedAdjustment' => [
                '<?php
                    $var = 0;

                    $arr = ["hello"];

                    if (5 + 3 === 8) {
                        $var = $arr[0];
                    }

                    echo $var;',
                'assertions' => [
                    '$var' => 'int|string',
                ],
            ],
            'typeAdjustmentIfNull' => [
                '<?php
                    class A {}
                    class B {}

                    $var = rand(0,10) > 5 ? new A : null;

                    if ($var === null) {
                        $var = new B;
                    }',
                'assertions' => [
                    '$var' => 'A|B',
                ],
            ],
            'whileTrue' => [
                '<?php
                    class One {
                        /**
                         * @return array|false
                         */
                        public function fooFoo(){
                            return rand(0,100) ? ["hello"] : false;
                        }

                        /** @return void */
                        public function barBar(){
                            while ($row = $this->fooFoo()) {
                                $row[0] = "bad";
                            }
                        }
                    }',
            ],
            'passingParam' => [
                '<?php
                    class A {}

                    class B {
                        /** @return void */
                        public function barBar(A $a) {}
                    }

                    $b = new B();
                    $b->barBar(new A);',
            ],
            'nullToNullableParam' => [
                '<?php
                    class A {}

                    class B {
                        /** @return void */
                        public function barBar(A $a = null) {}
                    }

                    $b = new B();
                    $b->barBar(null);',
            ],
            'objectToNullableObjectParam' => [
                '<?php
                    class A {}

                    class B {
                        /** @return void */
                        public function barBar(A $a = null) {}
                    }

                    $b = new B();
                    $b->barBar(new A);',
            ],
            'paramCoercion' => [
                '<?php
                    class A {}
                    class B extends A {
                        /** @return void */
                        public function barBar() {}
                    }

                    class C {
                        /** @return void */
                        function fooFoo(A $a) {
                            if ($a instanceof B) {
                                $a->barBar();
                            }
                        }
                    }',
            ],
            'paramElseifCoercion' => [
                '<?php
                    class A {}
                    class B extends A {
                        /** @return void */
                        public function barBar() {}
                    }
                    class C extends A {
                        /** @return void */
                        public function baz() {}
                    }

                    class D {
                        /** @return void */
                        function fooFoo(A $a) {
                            if ($a instanceof B) {
                                $a->barBar();
                            }
                            elseif ($a instanceof C) {
                                $a->baz();
                            }
                        }
                    }',
            ],
            'plusPlus' => [
                '<?php
                    $a = 0;
                    $b = $a++;',
                'assertions' => [
                    '$a' => 'int',
                ],
            ],
            'typedValueAssertion' => [
                '<?php
                    /**
                     * @param array|string $a
                     */
                    function fooFoo($a) : void {
                        $b = "aadad";

                        if ($a === $b) {
                            echo substr($a, 1);
                        }
                    }',
            ],
            'issetWithSimpleAssignment' => [
                '<?php
                    $array = [];

                    if (isset($array[$a = 5])) {
                        print "hello";
                    }

                    print $a;',
                'assertions' => [],
                'error_levels' => ['EmptyArrayAccess'],
            ],
            'issetWithMultipleAssignments' => [
                '<?php
                    if (rand(0, 4) > 2) {
                        $arr = [5 => [3 => "hello"]];
                    }

                    if (isset($arr[$a = 5][$b = 3])) {

                    }

                    echo $a;
                    echo $b;',
                'assertions' => [],
                'error_levels' => ['MixedArrayAccess'],
            ],
            'isIntOnUnaryPlus' => [
                '<?php
                    $a = +"5";
                    if (!is_int($a)) {
                    }',
            ],
            'suppressOneSuppressesAll' => [
                '<?php
                    class A {
                        /** @return void */
                        public function fooFoo() {}

                        /** @return void */
                        public function barFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(A $a = null) {
                            /** @psalm-suppress PossiblyNullReference */
                            $a->fooFoo();

                            $a->barFoo();
                        }
                    }',
                'assertions' => [],
            ],
            'trueFalseTest' => [
                '<?php
                    class A {
                        /** @return true */
                        public function returnsTrue() { return true; }

                        /** @return false */
                        public function returnsFalse() { return false; }

                        /** @return bool */
                        public function returnsBool() {
                            if (rand() % 2 > 0) {
                                return true;
                            }
                            return false;
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
            'nullableMethodCall' => [
                '<?php
                    class A {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(A $a = null) {
                            $a->fooFoo();
                        }
                    }',
                'error_message' => 'PossiblyNullReference',
            ],
            'nullableMethodCallWithThis' => [
                '<?php
                    class A {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @var A|null */
                        protected $a;

                        /** @return void */
                        public function barBar(A $a = null) {
                            $this->a = $a;
                            $this->a->fooFoo();
                        }
                    }',
                'error_message' => 'PossiblyNullReference',
            ],
            'nullableMethodWithIfGuard' => [
                '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class Two {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(One $one = null, Two $two = null) {
                            if ($one) {
                                $two->fooFoo();
                            }
                        }
                    }',
                'error_message' => 'PossiblyNullReference',
            ],
            'nullableMethodWithWrongBooleanIfGuard' => [
                '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class Two {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(One $one = null, Two $two = null) {
                            if ($one || $two) {
                                $two->fooFoo();
                            }
                        }
                    }',
                'error_message' => 'PossiblyNullReference',
            ],
            'nullableMethodWithWrongIfGuardedBefore' => [
                '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class Two {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(One $one = null, Two $two = null) {
                            if ($two === null) {
                                return;
                            }

                            $one->fooFoo();
                        }
                    }',
                'error_message' => 'PossiblyNullReference',
            ],
            'nullableMethodWithWrongBooleanIfGuardBefore' => [
                '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class Two {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(One $one = null, Two $two = null) {
                            if ($one === null && $two === null) {
                                return;
                            }

                            $one->fooFoo();
                        }
                    }',
                'error_mesage' => 'PossiblyNullReference',
            ],
            'nullableMethodWithGuardedNestedIncompleteRedefinition' => [
                '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class Two {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(One $one = null, Two $two = null) {
                            $a = 4;

                            if ($one === null) {
                                if ($a === 4) {
                                    $one = new One();
                                }
                            }

                            $one->fooFoo();
                        }
                    }',
                'error_message' => 'PossiblyNullReference',
            ],
            'nullableMethodWithGuardedSwitchRedefinitionNoDefault' => [
                '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(One $one = null) {
                            $a = 4;

                            if ($one === null) {
                                switch ($a) {
                                    case 4:
                                        $one = new One();
                                        break;
                                }
                            }

                            $one->fooFoo();
                        }
                    }',
                'error_message' => 'PossiblyNullReference',
            ],
            'nullableMethodWithGuardedSwitchRedefinitionEmptyDefault' => [
                '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(One $one = null) {
                            $a = 4;

                            if ($one === null) {
                                switch ($a) {
                                    case 4:
                                        $one = new One();
                                        break;

                                    default:
                                        break;
                                }
                            }

                            $one->fooFoo();
                        }
                    }',
                'error_message' => 'PossiblyNullReference',
            ],
            'nullableMethodWithGuardedNestedRedefinitionWithUselessElseReturn' => [
                '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(One $one = null) {
                            $a = 4;

                            if ($one === null) {
                                if ($a === 4) {
                                    $one = new One();
                                }
                                else if ($a === 3) {
                                    // do nothing
                                }
                                else {
                                    $one = new One();
                                    return;
                                }
                            }

                            $one->fooFoo();
                        }
                    }',
                'error_message' => 'PossiblyNullReference',
            ],
            'variableReassignmentInIfWithOutsideCall' => [
                '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class Two {
                        /** @return void */
                        public function barBar() {}
                    }

                    $one = new One();

                    if (1 + 1 === 2) {
                        $one = new Two();

                        $one->barBar();
                    }

                    $one->barBar();',
                'error_message' => 'PossiblyUndefinedMethod',
            ],
            'wrongParam' => [
                '<?php
                    class A {}

                    class B {
                        /** @return void */
                        public function barBar(A $a) {}
                    }

                    $b = new B();
                    $b->barBar(5);',
                'error_message' => 'InvalidArgument',
            ],
            'intToNullableObjectParam' => [
                '<?php
                    class A {}

                    class B {
                        /** @return void */
                        public function barBar(A $a = null) {}
                    }

                    $b = new B();
                    $b->barBar(5);',
                'error_message' => 'InvalidArgument',
            ],
            'paramCoercionWithBadArg' => [
                '<?php
                    class A {}
                    class B extends A {
                        /** @return void */
                        public function blab() {}
                    }

                    class C {
                        /** @return void */
                        function fooFoo(A $a) {
                            if ($a instanceof B) {
                                $a->barBar();
                            }
                        }
                    }',
                'error_message' => 'UndefinedMethod',
            ],
            'nullCheckInsideForeachWithNoLeaveStatement' => [
                '<?php
                    $a = null;

                    $a->fooBar();',
                'error_message' => 'NullReference',
            ],
            'possiblyUndefinedMethod' => [
                '<?php
                    class A {
                        public function foo() : void {}
                    }
                    class B {
                        public function other() : void {}
                    }

                    function a(bool $cond) : void {
                        if ($cond) {
                            $a = new A();
                        } else {
                            $a = new B();
                        }

                        if ($cond) {
                            $a->foo();
                        }
                    }',
                'error_message' => 'PossiblyUndefinedMethod',
            ],
            'notTrueTest' => [
                '<?php
                    /** @return true */
                    function returnsTrue() { return rand() % 2 > 0; }
                    ',
                'error_message' => 'InvalidReturnStatement',
            ],
            'notFalseTest' => [
                '<?php
                    /** @return false */
                    function returnsFalse() { return rand() % 2 > 0; }
                    ',
                'error_message' => 'InvalidReturnStatement',
            ],
        ];
    }
}
