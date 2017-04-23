<?php
namespace Psalm\Tests;

class ClassScopeTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'accessible-private-method-from-subclass' => [
                '<?php
                    class A {
                        private function fooFoo() : void {
            
                        }
            
                        private function barBar() : void {
                            $this->fooFoo();
                        }
                    }'
            ],
            'accessible-protected-method-from-subclass' => [
                '<?php
                    class A {
                        protected function fooFoo() : void {
                        }
                    }
            
                    class B extends A {
                        public function doFoo() : void {
                            $this->fooFoo();
                        }
                    }'
            ],
            'accessible-protected-method-from-other-subclass' => [
                '<?php
                    class A {
                        protected function fooFoo() : void {
                        }
                    }
            
                    class B extends A { }
            
                    class C extends A {
                        public function doFoo() : void {
                            (new B)->fooFoo();
                        }
                    }'
            ],
            'accessible-protected-property-from-subclass' => [
                '<?php
                    class A {
                        /** @var string */
                        protected $fooFoo = "";
                    }
            
                    class B extends A {
                        public function doFoo() : void {
                            echo $this->fooFoo;
                        }
                    }'
            ],
            'accessible-protected-property-from-great-grandparent' => [
                '<?php
                    class A {
                        /** @var string */
                        protected $fooFoo = "";
                    }
            
                    class B extends A { }
            
                    class C extends B { }
            
                    class D extends C {
                        public function doFoo() : void {
                            echo $this->fooFoo;
                        }
                    }'
            ],
            'accessible-protected-property-from-other-subclass' => [
                '<?php
                    class A {
                        /** @var string */
                        protected $fooFoo = "";
                    }
            
                    class B extends A {
                    }
            
                    class C extends A {
                        public function fooFoo() : void {
                            $b = new B();
                            $b->fooFoo = "hello";
                        }
                    }'
            ],
            'accessible-static-property-from-subclass' => [
                '<?php
                    class A {
                        /** @var string */
                        protected static $fooFoo = "";
            
                        public function barBar() : void {
                            echo self::$fooFoo;
                        }
                    }
            
                    class B extends A {
                        public function doFoo() : void {
                            echo A::$fooFoo;
                        }
                    }'
            ],
            'defined-private-method' => [
                '<?php
                    class A {
                        public function foo() : void {
                            if ($this instanceof B) {
                                $this->boop();
                            }
                        }
            
                        private function boop() : void {}
                    }
            
                    class B extends A {
                        private function boop() : void {}
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
            'inaccessible-private-method' => [
                '<?php
                    class A {
                        private function fooFoo() : void {
            
                        }
                    }
            
                    (new A())->fooFoo();',
                'error_message' => 'InaccessibleMethod'
            ],
            'inaccessible-protect-method' => [
                '<?php
                    class A {
                        protected function fooFoo() : void {
            
                        }
                    }
            
                    (new A())->fooFoo();',
                'error_message' => 'InaccessibleMethod'
            ],
            'inaccessible-private-method-from-subclass' => [
                '<?php
                    class A {
                        private function fooFoo() : void {
            
                        }
                    }
            
                    class B extends A {
                        public function doFoo() : void {
                            $this->fooFoo();
                        }
                    }',
                'error_message' => 'InaccessibleMethod'
            ],
            'inaccessible-protectred-method-from-other-subclass' => [
                '<?php
                    trait T {
                        protected function fooFoo() : void {
                        }
                    }
            
                    class B {
                        use T;
                    }
            
                    class C {
                        use T;
            
                        public function doFoo() : void {
                            (new B)->fooFoo();
                        }
                    }',
                'error_message' => 'InaccessibleMethod'
            ],
            'inaccessible-private-property' => [
                '<?php
                    class A {
                        /** @var string */
                        private $fooFoo;
                    }
            
                    echo (new A())->fooFoo;',
                'error_message' => 'InaccessibleProperty'
            ],
            'inaccessible-protected-property' => [
                '<?php
                    class A {
                        /** @var string */
                        protected $fooFoo;
                    }
            
                    echo (new A())->fooFoo;',
                'error_message' => 'InaccessibleProperty'
            ],
            'inaccessible-private-property-from-subclass' => [
                '<?php
                    class A {
                        /** @var string */
                        private $fooFoo = "";
                    }
            
                    class B extends A {
                        public function doFoo() : void {
                            echo $this->fooFoo;
                        }
                    }',
                'error_message' => 'InaccessibleProperty'
            ],
            'inaccessible-static-private-property' => [
                '<?php
                    class A {
                        /** @var string */
                        private static $fooFoo;
                    }
            
                    echo A::$fooFoo;',
                'error_message' => 'InaccessibleProperty'
            ],
            'inaccessible-static-protected-property' => [
                '<?php
                    class A {
                        /** @var string */
                        protected static $fooFoo;
                    }
            
                    echo A::$fooFoo;',
                'error_message' => 'InaccessibleProperty'
            ],
            'inaccessible-static-private-property-from-subclass' => [
                '<?php
                    class A {
                        /** @var string */
                        private static $fooFoo;
                    }
            
                    class B extends A {
                        public function doFoo() : void {
                            echo A::$fooFoo;
                        }
                    }',
                'error_message' => 'InaccessibleProperty'
            ]
        ];
    }
}
