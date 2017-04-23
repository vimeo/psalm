<?php
namespace Psalm\Tests;

class TraitTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'accessible-private-method-from-trait' => [
                '<?php
                    trait T {
                        private function fooFoo() : void {
                        }
                    }
            
                    class B {
                        use T;
            
                        public function doFoo() : void {
                            $this->fooFoo();
                        }
                    }'
            ],
            'accessible-protected-method-from-trait' => [
                '<?php
                    trait T {
                        protected function fooFoo() : void {
                        }
                    }
            
                    class B {
                        use T;
            
                        public function doFoo() : void {
                            $this->fooFoo();
                        }
                    }'
            ],
            'accessible-public-method-from-trait' => [
                '<?php
                    trait T {
                        public function fooFoo() : void {
                        }
                    }
            
                    class B {
                        use T;
            
                        public function doFoo() : void {
                            $this->fooFoo();
                        }
                    }'
            ],
            'accessible-private-property-from-trait' => [
                '<?php
                    trait T {
                        /** @var string */
                        private $fooFoo = "";
                    }
            
                    class B {
                        use T;
            
                        public function doFoo() : void {
                            echo $this->fooFoo;
                        }
                    }'
            ],
            'accessible-protected-property-from-trait' => [
                '<?php
                    trait T {
                        /** @var string */
                        protected $fooFoo = "";
                    }
            
                    class B {
                        use T;
            
                        public function doFoo() : void {
                            echo $this->fooFoo;
                        }
                    }'
            ],
            'accessible-public-property-from-trait' => [
                '<?php
                    trait T {
                        /** @var string */
                        public $fooFoo = "";
                    }
            
                    class B {
                        use T;
            
                        public function doFoo() : void {
                            echo $this->fooFoo;
                        }
                    }'
            ],
            'accessible-protected-method-from-inherited-trait' => [
                '<?php
                    trait T {
                        protected function fooFoo() : void {
                        }
                    }
            
                    class B {
                        use T;
                    }
            
                    class C extends B {
                        public function doFoo() : void {
                            $this->fooFoo();
                        }
                    }'
            ],
            'accessible-public-method-from-inherited-trait' => [
                '<?php
                    trait T {
                        public function fooFoo() : void {
                        }
                    }
            
                    class B {
                        use T;
                    }
            
                    class C extends B {
                        public function doFoo() : void {
                            $this->fooFoo();
                        }
                    }'
            ],
            'static-class-method-from-within-trait' => [
                '<?php
                    trait T {
                        public function fooFoo() : void {
                            self::barBar();
                        }
                    }
            
                    class B {
                        use T;
            
                        public static function barBar() : void {
            
                        }
                    }'
            ],
            'redefined-trait-method-without-alias' => [
                '<?php
                    trait T {
                        public function fooFoo() : void {
                        }
                    }
            
                    class B {
                        use T;
            
                        public function fooFoo(string $a) : void {
                        }
                    }
            
                    (new B)->fooFoo("hello");'
            ],
            'redefined-trait-method-with-alias' => [
                '<?php
                    trait T {
                        public function fooFoo() : void {
                        }
                    }
            
                    class B {
                        use T {
                            fooFoo as barBar;
                        }
            
                        public function fooFoo() : void {
                            $this->barBar();
                        }
                    }'
            ],
            'trait-self' => [
                '<?php
                    trait T {
                        public function g(): self
                        {
                            return $this;
                        }
                    }
            
                    class A {
                        use T;
                    }
            
                    $a = (new A)->g();',
                'assertions' => [
                    ['A' => '$a']
                ]
            ],
            'parent-trait-self' => [
                '<?php
                    trait T {
                        public function g(): self
                        {
                            return $this;
                        }
                    }
            
                    class A {
                        use T;
                    }
            
                    class B extends A {
                    }
            
                    class C {
                        use T;
                    }
            
                    $a = (new B)->g();',
                'assertions' => [
                    ['A' => '$a']
                ]
            ],
            'direct-static-call' => [
                '<?php
                    trait T {
                        /** @return void */
                        public static function foo() {}
                    }
                    class A {
                        use T;
            
                        /** @return void */
                        public function bar() {
                            T::foo();
                        }
                    }'
            ],
            'abstract-trait-method' => [
                '<?php
                    trait T {
                        /** @return void */
                        abstract public function foo();
                    }
            
                    abstract class A {
                        use T;
            
                        /** @return void */
                        public function bar() {
                            $this->foo();
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
            'inaccessible-private-method-from-inherited-trait' => [
                '<?php
                    trait T {
                        private function fooFoo() : void {
                        }
                    }
            
                    class B {
                        use T;
                    }
            
                    class C extends B {
                        public function doFoo() : void {
                            $this->fooFoo();
                        }
                    }',
                'error_message' => 'InaccessibleMethod'
            ],
            'undefined-trait' => [
                '<?php
                    class B {
                        use A;
                    }',
                'error_message' => 'UndefinedTrait'
            ],
            'missing-property-type' => [
                '<?php
                    trait T {
                        public $foo;
                    }
                    class A {
                        use T;
            
                        public function assignToFoo() : void {
                            $this->foo = 5;
                        }
                    }',
                'error_message' => 'MissingPropertyType - somefile.php:3 - Property T::$foo does not have a ' .
                    'declared type - consider null|int'
            ],
            'missing-property-type-with-constructor-init' => [
                '<?php
                    trait T {
                        public $foo;
                    }
                    class A {
                        use T;
            
                        public function __construct() : void {
                            $this->foo = 5;
                        }
                    }',
                'error_message' => 'MissingPropertyType - somefile.php:3 - Property T::$foo does not have a ' .
                    'declared type - consider int'
            ],
            'missing-property-type-with-constructor-init-and-null' => [
                '<?php
                    trait T {
                        public $foo;
                    }
                    class A {
                        use T;
            
                        public function __construct() : void {
                            $this->foo = 5;
                        }
            
                        public function makeNull() : void {
                            $this->foo = null;
                        }
                    }',
                'error_message' => 'MissingPropertyType - somefile.php:3 - Property T::$foo does not have a ' .
                    'declared type - consider null|int'
            ],
            'missing-property-type-with-constructor-init-and-null-default' => [
                '<?php
                    trait T {
                        public $foo = null;
                    }
                    class A {
                        use T;
            
                        public function __construct() : void {
                            $this->foo = 5;
                        }
                    }',
                'error_message' => 'MissingPropertyType - somefile.php:3 - Property T::$foo does not have a ' .
                    'declared type - consider int|nul'
            ]
        ];
    }
}
