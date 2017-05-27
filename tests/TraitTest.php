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
            'accessiblePrivateMethodFromTrait' => [
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
                    }',
            ],
            'accessibleProtectedMethodFromTrait' => [
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
                    }',
            ],
            'accessiblePublicMethodFromTrait' => [
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
                    }',
            ],
            'accessiblePrivatePropertyFromTrait' => [
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
                    }',
            ],
            'accessibleProtectedPropertyFromTrait' => [
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
                    }',
            ],
            'accessiblePublicPropertyFromTrait' => [
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
                    }',
            ],
            'accessibleProtectedMethodFromInheritedTrait' => [
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
                    }',
            ],
            'accessiblePublicMethodFromInheritedTrait' => [
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
                    }',
            ],
            'staticClassMethodFromWithinTrait' => [
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
                    }',
            ],
            'redefinedTraitMethodWithoutAlias' => [
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
            
                    (new B)->fooFoo("hello");',
            ],
            'redefinedTraitMethodWithAlias' => [
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
                    }',
            ],
            'traitSelf' => [
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
                    ['A' => '$a'],
                ],
            ],
            'parentTraitSelf' => [
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
                    ['A' => '$a'],
                ],
            ],
            'directStaticCall' => [
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
                    }',
            ],
            'abstractTraitMethod' => [
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
            'inaccessiblePrivateMethodFromInheritedTrait' => [
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
                'error_message' => 'InaccessibleMethod',
            ],
            'undefinedTrait' => [
                '<?php
                    class B {
                        use A;
                    }',
                'error_message' => 'UndefinedTrait',
            ],
            'missingPropertyType' => [
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
                    'declared type - consider null|int',
            ],
            'missingPropertyTypeWithConstructorInit' => [
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
                    'declared type - consider int',
            ],
            'missingPropertyTypeWithConstructorInitAndNull' => [
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
                    'declared type - consider null|int',
            ],
            'missingPropertyTypeWithConstructorInitAndNullDefault' => [
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
                    'declared type - consider int|nul',
            ],
        ];
    }
}
