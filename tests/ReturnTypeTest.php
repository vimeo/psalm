<?php
namespace Psalm\Tests;

class ReturnTypeTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'return-type-after-useless-null-check' => [
                '<?php
                    class One {}
            
                    class B {
                        /**
                         * @return One|null
                         */
                        public function barBar() {
                            $baz = rand(0,100) > 50 ? new One() : null;
            
                            // should have no effect
                            if ($baz === null) {
                                $baz = null;
                            }
            
                            return $baz;
                        }
                    }'
            ],
            'return-type-not-empty-check' => [
                '<?php
                    class B {
                        /**
                         * @param string|null $str
                         * @return string
                         */
                        public function barBar($str) {
                            if (empty($str)) {
                                $str = "";
                            }
                            return $str;
                        }
                    }'
            ],
            'return-type-not-empty-check-in-else-if' => [
                '<?php
                    class B {
                        /**
                         * @param string|null $str
                         * @return string
                         */
                        public function barBar($str) {
                            if ($str === "badger") {
                                // do nothing
                            }
                            elseif (empty($str)) {
                                $str = "";
                            }
                            return $str;
                        }
                    }'
            ],
            'return-type-not-empty-check-in-else' => [
                '<?php
                    class B {
                        /**
                         * @param string|null $str
                         * @return string
                         */
                        public function barBar($str) {
                            if (!empty($str)) {
                                // do nothing
                            }
                            else {
                                $str = "";
                            }
                            return $str;
                        }
                    }'
            ],
            'return-type-after-if' => [
                '<?php
                    class B {
                        /**
                         * @return string|null
                         */
                        public function barBar() {
                            $str = null;
                            $bar1 = rand(0, 100) > 40;
                            if ($bar1) {
                                $str = "";
                            }
                            return $str;
                        }
                    }'
            ],
            'return-type-after-two-ifs-with-throw' => [
                '<?php
                    class A1 {
                    }
                    class A2 {
                    }
                    class B {
                        /**
                         * @return A1
                         */
                        public function barBar(A1 $a1 = null, A2 $a2 = null) {
                            if (!$a1) {
                                throw new \Exception();
                            }
                            if (!$a2) {
                                throw new \Exception();
                            }
                            return $a1;
                        }
                    }'
            ],
            'return-type-after-if-else-if-with-throw' => [
                '<?php
                    class A1 {
                    }
                    class A2 {
                    }
                    class B {
                        /**
                         * @return A1
                         */
                        public function barBar(A1 $a1 = null, A2 $a2 = null) {
                            if (!$a1) {
                                throw new \Exception();
                            }
                            elseif (!$a2) {
                                throw new \Exception();
                            }
                            return $a1;
                        }
                    }'
            ],
            'try-catch-return-type' => [
                '<?php
                    class A {
                        /** @return bool */
                        public function fooFoo() {
                            try {
                                // do a thing
                                return true;
                            }
                            catch (\Exception $e) {
                                throw $e;
                            }
                        }
                    }'
            ],
            'switch-return-type-with-fallthrough' => [
                '<?php
                    class A {
                        /** @return bool */
                        public function fooFoo() {
                            switch (rand(0,10)) {
                                case 1:
                                default:
                                    return true;
                            }
                        }
                    }'
            ],
            'switch-return-type-with-fallthrough-and-statement' => [
                '<?php
                    class A {
                        /** @return bool */
                        public function fooFoo() {
                            switch (rand(0,10)) {
                                case 1:
                                    $a = 5;
                                default:
                                    return true;
                            }
                        }
                    }'
            ],
            'switch-return-type-with-default-exception' => [
                '<?php
                    class A {
                        /**
                         * @psalm-suppress TooManyArguments
                         * @return bool
                         */
                        public function fooFoo() {
                            switch (rand(0,10)) {
                                case 1:
                                case 2:
                                    return true;
            
                                default:
                                    throw new \Exception("badness");
                            }
                        }
                    }'
            ],
            'extends-static-call-return-type' => [
                '<?php
                    abstract class A {
                        /** @return static */
                        public static function load() {
                            return new static();
                        }
                    }
            
                    class B extends A {
                    }
            
                    $b = B::load();',
                'assertions' => [
                    ['B' => '$b']
                ]
            ],
            'extends-static-call-array-return-type' => [
                '<?php
                    abstract class A {
                        /** @return array<int,static> */
                        public static function loadMultiple() {
                            return [new static()];
                        }
                    }
            
                    class B extends A {
                    }
            
                    $bees = B::loadMultiple();',
                'assertions' => [
                    ['array<int, B>' => '$bees']
                ]
            ],
            'isset-return-type' => [
                '<?php
                    /**
                     * @param  mixed $foo
                     * @return bool
                     */
                    function a($foo = null) {
                        return isset($foo);
                    }'
            ],
            'this-return-type' => [
                '<?php
                    class A {
                        /** @return $this */
                        public function getThis() {
                            return $this;
                        }
                    }'
            ],
            'override-return-type' => [
                '<?php
                    class A {
                        /** @return string|null */
                        public function blah() {
                            return rand(0, 10) === 4 ? "blah" : null;
                        }
                    }
            
                    class B extends A {
                        /** @return string */
                        public function blah() {
                            return "blah";
                        }
                    }
            
                    $blah = (new B())->blah();',
                'assertions' => [
                    ['string' => '$blah']
                ]
            ],
            'interface-return-type' => [
                '<?php
                    interface A {
                        /** @return string|null */
                        public function blah();
                    }
            
                    class B implements A {
                        public function blah() {
                            return rand(0, 10) === 4 ? "blah" : null;
                        }
                    }
            
                    $blah = (new B())->blah();',
                'assertions' => [
                    ['string|null' => '$blah']
                ]
            ],
            'override-return-type-in-grandparent' => [
                '<?php
                    abstract class A {
                        /** @return string|null */
                        abstract public function blah();
                    }
            
                    class B extends A {
                    }
            
                    class C extends B {
                        public function blah() {
                            return rand(0, 10) === 4 ? "blahblah" : null;
                        }
                    }
            
                    $blah = (new C())->blah();',
                'assertions' => [
                    ['string|null' => '$blah']
                ]
            ],
            'backwards-return-type' => [
                '<?php
                    class A {}
                    class B extends A {}
            
                    /** @return B|A */
                    function foo() {
                      return rand(0, 1) ? new A : new B;
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
            'switch-return-type-with-fallthrough-and-break' => [
                '<?php
                    class A {
                        /** @return bool */
                        public function fooFoo() {
                            switch (rand(0,10)) {
                                case 1:
                                    break;
                                default:
                                    return true;
                            }
                        }
                    }',
                'error_message' => 'InvalidReturnType'
            ],
            'switch-return-type-with-fallthrough-and-conditional-break' => [
                '<?php
                    class A {
                        /** @return bool */
                        public function fooFoo() {
                            switch (rand(0,10)) {
                                case 1:
                                    if (rand(0,10) === 5) {
                                        break;
                                    }
                                default:
                                    return true;
                            }
                        }
                    }',
                'error_message' => 'InvalidReturnType'
            ],
            'switch-return-type-with-no-default' => [
                '<?php
                    class A {
                        /** @return bool */
                        public function fooFoo() {
                            switch (rand(0,10)) {
                                case 1:
                                case 2:
                                    return true;
                            }
                        }
                    }',
                'error_message' => 'InvalidReturnType'
            ],
            'wrong-return-type-1' => [
                '<?php
                    function fooFoo() : string {
                        return 5;
                    }',
                'error_message' => 'InvalidReturnType'
            ],
            'wrong-return-type-2' => [
                '<?php
                    function fooFoo() : string {
                        return rand(0, 5) ? "hello" : null;
                    }',
                'error_message' => 'InvalidReturnType'
            ],
            'wrong-return-type-in-namespace-1' => [
                '<?php
                    namespace bar;
            
                    function fooFoo() : string {
                        return 5;
                    }',
                'error_message' => 'InvalidReturnType'
            ],
            'wrong-return-type-in-namespace-2' => [
                '<?php
                    namespace bar;
            
                    function fooFoo() : string {
                        return rand(0, 5) ? "hello" : null;
                    }',
                'error_message' => 'InvalidReturnType'
            ],
            'missing-return-type' => [
                '<?php
                    function fooFoo() {
                        return rand(0, 5) ? "hello" : null;
                    }',
                'error_message' => 'MissingReturnType'
            ],
            'mixed-inferred-return-type' => [
                '<?php
                    function fooFoo() : string {
                        return array_pop([]);
                    }',
                'error_message' => 'MixedInferredReturnType'
            ],
            'invalid-return-type-class' => [
                '<?php
                    function fooFoo() : A {
                        return array_pop([]);
                    }',
                'error_message' => 'UndefinedClass',
                'error_levels' => ['MixedInferredReturnType']
            ],
            'invalid-class-on-call' => [
                '<?php
                    /**
                     * @psalm-suppress UndefinedClass
                     * @psalm-suppress MixedInferredReturnType
                     */
                    function fooFoo() : A {
                        return array_pop([]);
                    }
            
                    fooFoo()->bar();',
                'error_message' => 'UndefinedClass'
            ]
        ];
    }
}
