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
            'accessiblePrivateMethodFromSubclass' => [
                '<?php
                    class A {
                        private function fooFoo() : void {

                        }

                        private function barBar() : void {
                            $this->fooFoo();
                        }
                    }'
            ],
            'accessibleProtectedMethodFromSubclass' => [
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
            'accessibleProtectedMethodFromOtherSubclass' => [
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
            'accessibleProtectedPropertyFromSubclass' => [
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
            'accessibleProtectedPropertyFromGreatGrandparent' => [
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
            'accessibleProtectedPropertyFromOtherSubclass' => [
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
            'accessibleStaticPropertyFromSubclass' => [
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
            'definedPrivateMethod' => [
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
            'inaccessiblePrivateMethod' => [
                '<?php
                    class A {
                        private function fooFoo() : void {

                        }
                    }

                    (new A())->fooFoo();',
                'error_message' => 'InaccessibleMethod'
            ],
            'inaccessibleProtectMethod' => [
                '<?php
                    class A {
                        protected function fooFoo() : void {

                        }
                    }

                    (new A())->fooFoo();',
                'error_message' => 'InaccessibleMethod'
            ],
            'inaccessiblePrivateMethodFromSubclass' => [
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
            'inaccessibleProtectredMethodFromOtherSubclass' => [
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
            'inaccessiblePrivateProperty' => [
                '<?php
                    class A {
                        /** @var string */
                        private $fooFoo;
                    }

                    echo (new A())->fooFoo;',
                'error_message' => 'InaccessibleProperty'
            ],
            'inaccessibleProtectedProperty' => [
                '<?php
                    class A {
                        /** @var string */
                        protected $fooFoo;
                    }

                    echo (new A())->fooFoo;',
                'error_message' => 'InaccessibleProperty'
            ],
            'inaccessiblePrivatePropertyFromSubclass' => [
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
            'inaccessibleStaticPrivateProperty' => [
                '<?php
                    class A {
                        /** @var string */
                        private static $fooFoo;
                    }

                    echo A::$fooFoo;',
                'error_message' => 'InaccessibleProperty'
            ],
            'inaccessibleStaticProtectedProperty' => [
                '<?php
                    class A {
                        /** @var string */
                        protected static $fooFoo;
                    }

                    echo A::$fooFoo;',
                'error_message' => 'InaccessibleProperty'
            ],
            'inaccessibleStaticPrivatePropertyFromSubclass' => [
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
            ],
            'privateConstructorInheritance' => [
                '<?php
                    class A {
                        private function __construct() { }
                    }
                    class B extends A {}
                    new B();',
                'error_message' => 'InaccessibleMethod'
            ],
            'privateConstructorInheritanceCall' => [
                '<?php
                    class A {
                        private function __construct() { }
                    }
                    class B extends A {
                        public function __construct() {
                            parent::__construct();
                        }
                    }',
                'error_message' => 'InaccessibleMethod'
            ]
        ];
    }
}
