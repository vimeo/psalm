<?php
namespace Psalm\Tests;

use Psalm\Checker\FileChecker;
use Psalm\Context;

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
            'nullable-method-with-ternary-guard' => [
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
                    }'
            ],
            'nullable-method-with-ternary-if-null-guard' => [
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
                    }'
            ],
            'nullable-method-with-ternary-empty-guard' => [
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
                    }'
            ],
            'nullable-method-with-ternary-is-null-guard' => [
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
                    }'
            ],
            'nullable-method-with-if-guard' => [
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
                    }'
            ],
            'nullable-method-with-ternary-guard-with-this' => [
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
                    }'
            ],
            'nullable-method-with-ternary-if-null-guard-with-this' => [
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
                    }'
            ],
            'nullable-method-with-if-guard-with-this' => [
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
                    }'
            ],
            'nullable-method-with-exception-thrown' => [
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
                    }'
            ],
            'nullable-method-with-redefinition-and-else' => [
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
                    }'
            ],
            'nullable-method-with-boolean-if-guard' => [
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
                    }'
            ],
            'nullable-method-with-non-null-boolean-if-guard' => [
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
                    }'
            ],
            'nullable-method-with-non-null-boolean-if-guard-and-boolean-and' => [
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
                    }'
            ],
            'nullable-method-in-condition-with-if-guard-before' => [
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
                    }'
            ],
            'nullable-method-with-boolean-if-guard-before' => [
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
                    }'
            ],
            'nullable-method-with-guarded-redefinition' => [
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
                    }'
            ],
            'nullable-method-with-guarded-redefinition-in-else' => [
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
                    }'
            ],
            'nullable-method-with-guarded-nested-redefinition' => [
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
                    }'
            ],
            'nullable-method-with-guarded-switch-redefinition' => [
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
                    }'
            ],
            'nullable-method-with-guarded-switch-redefinition-due-to-exception' => [
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
                    }'
            ],
            'nullable-method-with-guarded-switch-that-always-returns' => [
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
                    }'
            ],
            'nullable-method-with-guarded-nested-redefinition-with-return' => [
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
                    }'
            ],
            'nullable-method-with-guarded-nested-redefinition-with-else-return' => [
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
                    }'
            ],
            'nullable-method-with-guarded-nested-redefinition-with-elseif-return' => [
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
                    }'
            ],
            'nullable-method-with-guarded-switch-break' => [
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
                    }'
            ],
            'nullable-method-with-guarded-redefinition-on-this' => [
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
                    }'
            ],
            'array-union-type-assertion' => [
                '<?php
                    /** @var array|null */
                    $ids = (1 + 1 === 2) ? [] : null;
        
                    if ($ids === null) {
                        $ids = [];
                    }',
                'assertions' => [
                    ['array<empty, empty>' => '$ids']
                ]
            ],
            'array-union-type-assertion-with-is-array' => [
                '<?php
                    /** @var array|null */
                    $ids = (1 + 1 === 2) ? [] : null;
        
                    if (!is_array($ids)) {
                        $ids = [];
                    }',
                'assertions' => [
                    ['array<empty, empty>' => '$ids']
                ]
            ],
            '2d-array-union-type-assertion-with-is-array' => [
                '<?php
                    /** @return array<array<string>>|null */
                    function foo() {
                        $ids = rand(0, 1) ? [["hello"]] : null;
            
                        if (is_array($ids)) {
                            return $ids;
                        }
            
                        return null;
                    }'
            ],
            'variable-reassignment' => [
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
            
                    $one->barBar();'
            ],
            'variable-reassignment-in-if' => [
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
                    }'
            ],
            'union-type-flow' => [
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
                    }'
            ],
            'union-type-flow-with-throw' => [
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
                    }'
            ],
            'union-type-flow-with-elseif' => [
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
                    }'
            ],
            'typed-adjustment' => [
                '<?php
                    $var = 0;
            
                    if (5 + 3 === 8) {
                        $var = "hello";
                    }
            
                    echo $var;',
                'assertions' => [
                    ['int|string' => '$var']
                ]
            ],
            'type-mixed-adjustment' => [
                '<?php
                    $var = 0;
            
                    $arr = ["hello"];
            
                    if (5 + 3 === 8) {
                        $var = $arr[0];
                    }
            
                    echo $var;',
                'assertions' => [
                    ['int|string' => '$var']
                ]
            ],
            'type-adjustment-if-null' => [
                '<?php
                    class A {}
                    class B {}
            
                    $var = rand(0,10) > 5 ? new A : null;
            
                    if ($var === null) {
                        $var = new B;
                    }',
                'assertions' => [
                    ['A|B' => '$var']
                ]
            ],
            'while-true' => [
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
                    }'
            ],
            'passing-param' => [
                '<?php
                    class A {}
            
                    class B {
                        /** @return void */
                        public function barBar(A $a) {}
                    }
            
                    $b = new B();
                    $b->barBar(new A);'
            ],
            'null-to-nullable-param' => [
                '<?php
                    class A {}
            
                    class B {
                        /** @return void */
                        public function barBar(A $a = null) {}
                    }
            
                    $b = new B();
                    $b->barBar(null);'
            ],
            'object-to-nullable-object-param' => [
                '<?php
                    class A {}
            
                    class B {
                        /** @return void */
                        public function barBar(A $a = null) {}
                    }
            
                    $b = new B();
                    $b->barBar(new A);'
            ],
            'param-coercion' => [
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
                    }'
            ],
            'param-elseif-coercion' => [
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
                    }'
            ],
            'plus-plus' => [
                '<?php
                    $a = 0;
                    $b = $a++;',
                'assertions' => [
                    ['int' => '$a']
                ]
            ],
            'typed-value-assertion' => [
                '<?php
                    /**
                     * @param array|string $a
                     */
                    function fooFoo($a) : void {
                        $b = "aadad";
            
                        if ($a === $b) {
                            echo substr($a, 1);
                        }
                    }'
            ],
            'isset-with-simple-assignment' => [
                '<?php
                    $array = [];
            
                    if (isset($array[$a = 5])) {
                        print "hello";
                    }
            
                    print $a;'
            ],
            'isset-with-multiple-assignments' => [
                '<?php
                    if (rand(0, 4) > 2) {
                        $arr = [5 => [3 => "hello"]];
                    }
            
                    if (isset($arr[$a = 5][$b = 3])) {
            
                    }
            
                    echo $a;
                    echo $b;'
            ],
            'is-int-on-unary-plus' => [
                '<?php
                    $a = +"5";
                    if (!is_int($a)) {
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
            'nullable-method-call' => [
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
                'error_message' => 'PossiblyNullReference'
            ],
            'nullable-method-call-with-this' => [
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
                'error_message' => 'PossiblyNullReference'
            ],
            'nullable-method-with-if-guard' => [
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
                'error_message' => 'PossiblyNullReference'
            ],
            'nullable-method-with-wrong-boolean-if-guard' => [
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
                'error_message' => 'PossiblyNullReference'
            ],
            'nullable-method-with-wrong-if-guarded-before' => [
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
                'error_message' => 'PossiblyNullReference'
            ],
            'nullable-method-with-wrong-boolean-if-guard-before' => [
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
                'error_mesage' => 'PossiblyNullReference'
            ],
            'method-with-meaningless-check' => [
                '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }
            
                    class B {
                        /** @return void */
                        public function barBar(One $one) {
                            if (!$one) {
                                // do nothing
                            }
            
                            $one->fooFoo();
                        }
                    }',
                'error_message' => 'PossiblyNullReference'
            ],
            'nullable-method-with-guarded-nested-incomplete-redefinition' => [
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
                'error_message' => 'PossiblyNullReference'
            ],
            'nullable-method-with-guarded-switch-redefinition-no-default' => [
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
                'error_message' => 'PossiblyNullReference'
            ],
            'nullable-method-with-guarded-switch-redefinition-empty-default' => [
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
                'error_message' => 'PossiblyNullReference'
            ],
            'nullable-method-with-guarded-nested-redefinition-with-useless-else-return' => [
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
                'error_message' => 'PossiblyNullReference'
            ],
            'variable-reassignment-in-if-with-outside-call' => [
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
                'error_message' => 'UndefinedMethod'
            ],
            'unnecessary-instanceof' => [
                '<?php
                    class One {
                        public function fooFoo() {}
                    }
            
                    $var = new One();
            
                    if ($var instanceof One) {
                        $var->fooFoo();
                    }',
                'error_message' => 'FailedTypeResolution'
            ],
            'un-negatable-instanceof' => [
                '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }
            
                    $var = new One();
            
                    if ($var instanceof One) {
                        $var->fooFoo();
                    }
                    else {
                        // do something
                    }',
                'error_message' => 'FailedTypeResolution'
            ],
            'wrong-param' => [
                '<?php
                    class A {}
            
                    class B {
                        /** @return void */
                        public function barBar(A $a) {}
                    }
            
                    $b = new B();
                    $b->barBar(5);',
                'error_message' => 'InvalidArgument'
            ],
            'int-to-nullable-object-param' => [
                '<?php
                    class A {}
            
                    class B {
                        /** @return void */
                        public function barBar(A $a = null) {}
                    }
            
                    $b = new B();
                    $b->barBar(5);',
                'error_message' => 'InvalidArgument'
            ],
            'param-coercion-with-bad-arg' => [
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
                'error_message' => 'UndefinedMethod'
            ],
            'null-check-inside-foreach-with-no-leave-statement' => [
                '<?php
                    $a = null;
            
                    $a->fooBar();',
                'error_message' => 'NullReference'
            ]
        ];
    }
}
