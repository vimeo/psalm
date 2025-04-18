<?php

declare(strict_types=1);

namespace Psalm\Tests\TypeReconciliation;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

class TypeTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'sealArray' => [
                'code' => '<?php
                    /** @var array */
                    $a = [];

                    assert(isset($a["a"]));
                    assert(count($a) === 1);
                    ',
                'assertions' => [
                    '$a===' => 'array{a: mixed}',
                ],
            ],
            'sealedArrayCount' => [
                'code' => '<?php
                    $a = random_int(0,1) ? [] : [0, 1];

                    $b = null;
                    if (count($a) === 2) {
                        $b = $a;
                    }',
                'assertions' => [
                    '$a===' => 'list{0?: 0, 1?: 1}',
                    '$b===' => 'list{0, 1}|null',
                ],
            ],
            'sealedArrayMagic' => [
                'code' => '<?php

                /** @var array{invoice?: string, utd?: "utd", cancel_agreement?: "test", installment?: "test"} */
                $b = [];


                $buttons = [];
                foreach ($b as $text) {
                    $buttons[] = $text;
                }
                if (count($buttons) === 0) {
                    echo "Zero";
                }


                /** @var ?string */
                $test = null;
                $urls = array_filter([$test]);

                $mainUrlSet = false;
                foreach ($urls as $_) {
                    if (!$mainUrlSet) {
                        $mainUrlSet = true;
                    }
                }
                if (!$mainUrlSet) {
                    echo "SKIP";
                }


                /**
                 * @param string|list<bool|array{0:string, 1:string}> $time
                 */
                function mapTime($time): void
                {
                    $atime = is_array($time) ? $time : [];
                    if ($time === "24h") {
                        return;
                    }

                    for ($day = 0; $day < 7; ++$day) {
                        if (!array_key_exists($day, $atime) || !is_array($atime[$day])) {
                            continue;
                        }

                        $dayWh = $atime[$day];
                        array_pop($dayWh);
                    }
                }',
                'assertions' => [
                    '$buttons===' => 'list<string>',
                    '$urls===' => 'list{0?: non-falsy-string}',
                    '$mainUrlSet===' => 'bool',
                ],
            ],
            'validSealedArrayAssertions' => [
                'code' => '<?php
                    /** @var array{a: string, b: string, c?: string} */
                    $a = [];

                    if (count($a) > 2) {
                        echo "Have C!";
                    }

                    if (count($a) < 3) {
                        echo "Do not have C!";
                    }
                ',
            ],
            'validSealedArrayAssertions2' => [
                'code' => '<?php
                    /** @var array{a: string, b: string, c?: string} */
                    $a = [];

                    assert(count($a) > 2);
                ',
                'assertions' => [
                    '$a===' => 'array{a: string, b: string, c: string}',
                ],
            ],
            'instanceOfInterface' => [
                'code' => '<?php
                    interface Supplier {
                        public function get(): iterable;
                    }

                    class SomeClass {
                        protected Supplier|iterable $prop;

                        public function __construct(Supplier|iterable $value) {
                            $this->prop = $value;
                        }

                        public function do(): void {
                            $var = $this->prop;

                            if ($var instanceof Supplier) {
                                $var->get();
                            }
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'nullableMethodWithTernaryGuard' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                            if ($one !== null && ($two || rand(0, 1))) {
                                $one->fooFoo();
                            }
                        }
                    }',
            ],
            'nullableMethodInConditionWithIfGuardBefore' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                            $a = rand(0, 4);

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
                'code' => '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(One $one = null) {
                            $a = rand(0, 4);

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
                'code' => '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /**
                         * @return void
                         */
                        public function barBar(One $one = null) {
                            $a = rand(0, 4);

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
                'code' => '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(One $one = null) {
                            $a = rand(0, 4);

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
                'code' => '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(One $one = null) {
                            $a = rand(0, 4);

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
                'code' => '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(One $one = null) {
                            $a = rand(0, 4);

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
                'code' => '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(One $one = null) {
                            $a = rand(0, 4);

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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    $ids = (1 + 1 === 2) ? [] : null;

                    if ($ids === null) {
                        $ids = [];
                    }',
                'assertions' => [
                    '$ids' => 'array<never, never>',
                ],
            ],
            'arrayUnionTypeAssertionWithIsArray' => [
                'code' => '<?php
                    $ids = (1 + 1 === 2) ? [] : null;

                    if (!is_array($ids)) {
                        $ids = [];
                    }',
                'assertions' => [
                    '$ids' => 'array<never, never>',
                ],
            ],
            '2dArrayUnionTypeAssertionWithIsArray' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    class A {}

                    class B {
                        /** @return void */
                        public function barBar(A $a) {}
                    }

                    $b = new B();
                    $b->barBar(new A);',
            ],
            'nullToNullableParam' => [
                'code' => '<?php
                    class A {}

                    class B {
                        /** @return void */
                        public function barBar(A $a = null) {}
                    }

                    $b = new B();
                    $b->barBar(null);',
            ],
            'objectToNullableObjectParam' => [
                'code' => '<?php
                    class A {}

                    class B {
                        /** @return void */
                        public function barBar(A $a = null) {}
                    }

                    $b = new B();
                    $b->barBar(new A);',
            ],
            'paramCoercion' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    $a = 0;
                    $b = $a++;',
                'assertions' => [
                    '$a===' => '1',
                ],
            ],
            'typedValueAssertion' => [
                'code' => '<?php
                    /**
                     * @param array|string $a
                     */
                    function fooFoo($a): void {
                        $b = "aadad";

                        if ($a === $b) {
                            echo substr($a, 1);
                        }
                    }',
            ],
            'isIntOnUnaryPlus' => [
                'code' => '<?php
                    $a = +"5";
                    if (!is_int($a)) {
                    }',
            ],
            'suppressOneSuppressesAll' => [
                'code' => '<?php
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
                'code' => '<?php
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
            'intersectionTypeAfterInstanceof' => [
                'code' => '<?php
                    /**
                     * @psalm-consistent-constructor
                     */
                    abstract class A {
                      /** @var string|null */
                      public $foo;

                      public static function getFoo(): void {
                        $a = new static();
                        if ($a instanceof I) {}
                        $a->foo = "bar";
                      }
                    }

                    interface I {}',
            ],
            'intersectionTypeInsideInstanceof' => [
                'code' => '<?php
                    /**
                     * @psalm-consistent-constructor
                     */
                    abstract class A {
                      /** @var string|null */
                      public $foo;

                      public static function getFoo(): void {
                        $a = new static();
                        if ($a instanceof I) {
                          takesI($a);
                          takesA($a);
                        }
                      }
                    }

                    interface I {}

                    function takesI(I $i): void {}
                    function takesA(A $i): void {}',
            ],
            'intersectionInNamespace' => [
                'code' => '<?php
                    namespace NS;
                    use Countable;

                    class Item {}
                    /**
                     * @var iterable<Item>&Countable $collection
                     */
                    $collection = [];
                    count($collection);

                    /**
                     * @param iterable<Item>&Countable $collection
                     */
                    function mycount($collection): int {
                        return count($collection);
                    }
                    mycount($collection);',
            ],
            'scalarTypeParam' => [
                'code' => '<?php
                    /**
                     * @param scalar $var
                     */
                    function test($var): void {}

                    test("a");
                    test(1);
                    test(1.1);
                    test(true);
                    test(false);',
            ],
            'assignOpUpdateArray' => [
                'code' => '<?php
                    $optgroup = ["a" => ""];

                    if (rand(0, 1)) {
                        $optgroup["a"] .= "v";
                    }

                    if ($optgroup["a"] !== "") {}',
            ],
            'redefineArrayKeyInsideIsStringConditional' => [
                'code' => '<?php
                    /**
                     * @param string|int $key
                     */
                    function get($key, array $arr) : void {
                        if (!isset($arr[$key])) {
                            if (is_string($key)) {
                                $key = "p" . $key;
                            }

                            if (!isset($arr[$key])) {}
                        }
                    }',
            ],
            'redefineArrayKeyInsideIsStringConditionalElse' => [
                'code' => '<?php
                    /**
                     * @param string|int $key
                     */
                    function get($key, array $arr) : void {
                        if (!isset($arr[$key])) {
                            if (!is_string($key)) {
                                // do nothing
                            } else {
                                $key = "p" . $key;
                            }

                            if (!isset($arr[$key])) {}
                        }
                    }',
            ],
            'redefineArrayKeyInsideIsStringConditionalElseif' => [
                'code' => '<?php
                    /**
                     * @param string|int $key
                     */
                    function get($key, array $arr) : void {
                        if (!isset($arr[$key])) {
                            if (!is_string($key)) {
                                // do nothing
                            } elseif (rand(0, 1)) {
                                $key = "p" . $key;
                            }

                            if (!isset($arr[$key])) {}
                        }
                    }',
            ],
            'redefineArrayKeyInsideIsStringConditionalWhile' => [
                'code' => '<?php
                    /**
                     * @param string|int $key
                     */
                    function get($key, array $arr) : void {
                        if (!isset($arr[$key])) {
                            while (rand(0, 1)) {
                                $key = "p" . $key;
                            }

                            if (!isset($arr[$key])) {}
                        }
                    }',
            ],
            'redefineArrayKeyInsideIsIntConditional' => [
                'code' => '<?php
                    /**
                     * @param string|int $key
                     */
                    function get($key, array $arr) : void {
                        if (!isset($arr[$key])) {
                            if (is_int($key)) {
                                $key++;
                            }

                            if (!isset($arr[$key])) {}
                        }
                    }',
            ],
            'arrayKeyCanBeNumeric' => [
                'code' => '<?php
                    /** @param array<string> $arr */
                    function foo(array $arr) : void {
                        foreach ($arr as $k => $_) {
                            if (is_numeric($k)) {}
                            if (!is_numeric($k)) {}
                        }
                    }',
            ],
            'narrowScalar' => [
                'code' => '<?php
                    /** @var scalar $s */
                    $s = 1;

                    if (!is_int($s) && !is_bool($s) && !is_float($s)) {
                        strlen($s);
                    }',
            ],
            'testIsIntAndAliasesTypeNarrowing' => [
                'code' => '<?php
                    /** @var mixed $a */
                    $a;
                    /** @var never $b */
                    $b;
                    /** @var never $c */
                    $c;
                    /** @var never $d */
                    $d;
                    if (is_int($a)) {
                        $b = $a;
                    }
                    if (is_integer($a)) {
                        $c = $a;
                    }
                    if (is_long($a)) {
                        $d = $a;
                    }
                ',
                'assertions' => [
                    '$b===' => 'int',
                    '$c===' => 'int',
                    '$d===' => 'int',
                ],
            ],
            'narrowWithCountToAllowNonTupleKeyedArray' => [
                'code' => '<?php
                    /**
                     * @param list<string> $arr
                     */
                    function foo($arr): void {
                        if (count($arr) === 2) {
                            consume($arr);
                        }
                    }

                    /**
                     * @param array{0:string, 1: string} $input
                     */
                    function consume($input): void{}',
            ],
            'notDateTimeWithDateTimeInterface' => [
                'code' => '<?php
                    function foo(DateTimeInterface $dateTime): DateTimeInterface {
                        $dateInterval = new DateInterval("P1D");

                        if ($dateTime instanceof DateTime) {
                            $dateTime->add($dateInterval);

                            return $dateTime;
                        } else {
                            return $dateTime->add($dateInterval);
                        }
                    }
                ',
            ],
            'notDateTimeImmutableWithDateTimeInterface' => [
                'code' => '<?php
                    function foo(DateTimeInterface $dateTime): DateTimeInterface {
                        $dateInterval = new DateInterval("P1D");

                        if ($dateTime instanceof DateTimeImmutable) {
                            return $dateTime->add($dateInterval);
                        } else {
                            $dateTime->add($dateInterval);

                            return $dateTime;
                        }
                    }
                ',
            ],
            'CountEqual0MakesNonEmptyArray' => [
                'code' => '<?php
                    function a(array $a): void {
                        if (count($a) === 0) {
                            throw new \LogicException;
                        }
                        expectNonEmptyArray($a);
                    }
                    function b(array $a): void {
                        if (count($a) !== 0) {
                            expectNonEmptyArray($a);
                        }
                    }
                    function c(array $a): void {
                        if (count($a) === 0) {
                            throw new \LogicException;
                        } else {
                            expectNonEmptyArray($a);
                        }
                    }
                    /** @param non-empty-array $a */
                    function expectNonEmptyArray(array $a): array { return $a; }',
            ],
            'isObjectMakesObject' => [
                'code' => '<?php

                    final class test {}

                    /** @var array|int|float|test|null */
                    $a = null;
                    if (\is_object($a)) {
                    }',
            ],
        ];
    }

    /**
     * @return iterable<string,array{code:string,error_message:string,ignored_issues?:list<string>,php_version?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'possiblyUndefinedVariable' => [
                'code' => '<?php
                    if (rand(0, 1)) {
                        $a = 5;
                    }

                    echo $a;',
                'error_message' => 'PossiblyUndefinedGlobalVariable',
            ],
            'nullableMethodCall' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'error_message' => 'PossiblyNullReference',
            ],
            'nullableMethodWithGuardedNestedIncompleteRedefinition' => [
                'code' => '<?php
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
                            $a = rand(0, 4);

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
                'code' => '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(One $one = null) {
                            $a = rand(0, 4);

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
                'code' => '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(One $one = null) {
                            $a = rand(0, 4);

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
                'code' => '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(One $one = null) {
                            $a = rand(0, 4);

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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    $a = null;

                    $a->fooBar();',
                'error_message' => 'NullReference',
            ],
            'possiblyUndefinedMethod' => [
                'code' => '<?php
                    class A {
                        public function foo(): void {}
                    }
                    class B {
                        public function other(): void {}
                    }

                    function a(bool $cond): void {
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
                'code' => '<?php
                    /** @return true */
                    function returnsTrue() { return rand() % 2 > 0; }
                    ',
                'error_message' => 'InvalidReturnStatement',
            ],
            'notFalseTest' => [
                'code' => '<?php
                    /** @return false */
                    function returnsFalse() { return rand() % 2 > 0; }
                    ',
                'error_message' => 'InvalidReturnStatement',
            ],
            'invalidSealedArrayAssertion1' => [
                'code' => '<?php
                    /** @var array{a: string, b: string, c?: string} */
                    $a = [];

                    if (count($a) > 1) {
                    }',
                'error_message' => 'RedundantConditionGivenDocblockType',
            ],
            'invalidSealedArrayAssertion2' => [
                'code' => '<?php
                    /** @var array{a: string, b: string, c?: string} */
                    $a = [];

                    if (count($a) > 3) {
                    }',
                'error_message' => 'DocblockTypeContradiction',
            ],
            'invalidSealedArrayAssertion3' => [
                'code' => '<?php
                    /** @var array{a: string, b: string, c?: string} */
                    $a = [];

                    if (count($a) > 4) {
                    }',
                'error_message' => 'DocblockTypeContradiction',
            ],
            'invalidSealedArrayAssertion4' => [
                'code' => '<?php
                    /** @var array{a: string, b: string, c?: string} */
                    $a = [];

                    if (count($a) < 1) {
                    }',
                'error_message' => 'DocblockTypeContradiction',
            ],
            'invalidSealedArrayAssertion5' => [
                'code' => '<?php
                    /** @var array{a: string, b: string, c?: string} */
                    $a = [];

                    if (count($a) < 2) {
                    }',
                'error_message' => 'DocblockTypeContradiction',
            ],
            'invalidSealedArrayAssertion6' => [
                'code' => '<?php
                    /** @var array{a: string, b: string, c?: string} */
                    $a = [];

                    if (count($a) < 4) {
                    }',
                'error_message' => 'RedundantConditionGivenDocblockType',
            ],
            'intersectionTypeClassCheckAfterInstanceof' => [
                'code' => '<?php
                    /**
                     * @psalm-consistent-constructor
                     */
                    abstract class A {
                      /** @var string|null */
                      public $foo;

                      public static function getFoo(): void {
                        $a = new static();
                        if ($a instanceof B) {}
                        elseif ($a instanceof C) {}
                        else {}
                        takesB($a);
                      }
                    }

                    class B extends A {}
                    class C extends A {}

                    function takesB(B $i): void {}',
                'error_message' => 'ArgumentTypeCoercion - src' . DIRECTORY_SEPARATOR . 'somefile.php:14:32 - Argument 1 of takesB expects B,'
                    . ' but parent type A&static provided',
            ],
            'intersectionTypeInterfaceCheckAfterInstanceof' => [
                'code' => '<?php
                    /**
                     * @psalm-consistent-constructor
                     */
                    abstract class A {
                      /** @var string|null */
                      public $foo;

                      public static function getFoo(): void {
                        $a = new static();
                        if ($a instanceof I) {}
                        takesI($a);
                      }
                    }

                    interface I {}

                    function takesI(I $i): void {}',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:12:32 - Argument 1 of takesI expects I, but A&static provided',
            ],
        ];
    }
}
