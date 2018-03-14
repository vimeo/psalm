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
                        private function fooFoo(): void {
                        }
                    }

                    class B {
                        use T;

                        public function doFoo(): void {
                            $this->fooFoo();
                        }
                    }',
            ],
            'accessibleProtectedMethodFromTrait' => [
                '<?php
                    trait T {
                        protected function fooFoo(): void {
                        }
                    }

                    class B {
                        use T;

                        public function doFoo(): void {
                            $this->fooFoo();
                        }
                    }',
            ],
            'accessiblePublicMethodFromTrait' => [
                '<?php
                    trait T {
                        public function fooFoo(): void {
                        }
                    }

                    class B {
                        use T;

                        public function doFoo(): void {
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

                        public function doFoo(): void {
                            echo $this->fooFoo;
                            $this->fooFoo = "hello";
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

                        public function doFoo(): void {
                            echo $this->fooFoo;
                            $this->fooFoo = "hello";
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

                        public function doFoo(): void {
                            echo $this->fooFoo;
                            $this->fooFoo = "hello";
                        }
                    }',
            ],
            'accessibleProtectedMethodFromInheritedTrait' => [
                '<?php
                    trait T {
                        protected function fooFoo(): void {
                        }
                    }

                    class B {
                        use T;
                    }

                    class C extends B {
                        public function doFoo(): void {
                            $this->fooFoo();
                        }
                    }',
            ],
            'accessiblePublicMethodFromInheritedTrait' => [
                '<?php
                    trait T {
                        public function fooFoo(): void {
                        }
                    }

                    class B {
                        use T;
                    }

                    class C extends B {
                        public function doFoo(): void {
                            $this->fooFoo();
                        }
                    }',
            ],
            'staticClassMethodFromWithinTrait' => [
                '<?php
                    trait T {
                        public function fooFoo(): void {
                            self::barBar();
                        }
                    }

                    class B {
                        use T;

                        public static function barBar(): void {

                        }
                    }',
            ],
            'redefinedTraitMethodWithoutAlias' => [
                '<?php
                    trait T {
                        public function fooFoo(): void {
                        }
                    }

                    class B {
                        use T;

                        public function fooFoo(string $a): void {
                        }
                    }

                    (new B)->fooFoo("hello");',
            ],
            'redefinedTraitMethodWithAlias' => [
                '<?php
                    trait T {
                        public function fooFoo(): void {
                        }
                    }

                    class B {
                        use T {
                            fooFoo as barBar;
                        }

                        public function fooFoo(): void {
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
                    '$a' => 'A',
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
                    '$a' => 'A',
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
            'instanceOfTraitUser' => [
                '<?php
                    trait T {
                      public function f(): void {
                        if ($this instanceof A) { }
                      }
                    }

                    class A {
                      use T;
                    }

                    class B {
                      use T;
                    }',
            ],
            'getClassTraitUser' => [
                '<?php
                    trait T {
                      public function f(): void {
                        if (get_class($this) === "B") { }
                      }
                    }

                    class A {
                      use T;
                    }

                    class B {
                      use T;
                    }',
            ],
            'useTraitInClassWithAbstractMethod' => [
                '<?php
                    trait T {
                      abstract public function foo(): void;
                    }

                    class A {
                      public function foo(): void {}
                    }',
            ],
            'useTraitInSubclassWithAbstractMethod' => [
                '<?php
                    trait T {
                      abstract public function foo(): void;
                    }

                    abstract class A {
                      public function foo(): void {}
                    }

                    class B extends A {
                      use T;
                    }',
            ],
            'useTraitInSubclassWithAbstractMethodInParent' => [
                '<?php
                    trait T {
                      public function foo(): void {}
                    }

                    abstract class A {
                      abstract public function foo(): void {}
                    }

                    class B extends A {
                      use T;
                    }',
            ],
            'differentMethodReturnTypes' => [
                '<?php
                    trait T {
                        public static function getSelf(): self {
                            return new self();
                        }

                        public static function callGetSelf(): self {
                            return self::getSelf();
                        }
                    }

                    class A {
                        use T;
                    }

                    class B {
                        use T;
                    }',
            ],
            'parentRefInTraitShouldNotFail' => [
                '<?php
                    trait T {
                      public function foo(): void {
                        parent::foo();
                      }
                    }
                    class A {
                      public function foo(): void {}
                    }
                    class B extends A {
                      use T;
                    }',
            ],
            'namespacedTraitLookup' => [
                '<?php
                    namespace Classes {
                      use Traits\T;

                      class A {}

                      class B {
                        use T;
                      }
                    }

                    namespace Traits {
                      use Classes\A;

                      trait T {
                        public function getA() : A {
                          return new A;
                        }
                      }
                    }

                    namespace {
                        $a = (new Classes\B)->getA();
                    }',
            ],
            'useAndMap' => [
                '<?php
                    class C
                    {
                        use T2;
                        use T1 {
                            traitFunc as _func;
                        }

                        public static function func(): void
                        {
                            static::_func();
                        }
                    }
                    trait T1
                    {
                        public static function traitFunc(): void {}
                    }
                    trait T2 { }',
            ],
            'mapAndUse' => [
                '<?php
                    class C
                    {
                        use T1 {
                            traitFunc as _func;
                        }
                        use T2;

                        public static function func(): void
                        {
                            static::_func();
                        }
                    }
                    trait T1
                    {
                        public static function traitFunc(): void {}
                    }
                    trait T2 { }',
            ],
            'moreArgsInDefined' => [
                '<?php
                    trait T {
                        abstract public function foo() : void;

                        public function callFoo() : void {
                            $this->foo();
                        }
                    }

                    class A {
                        use T;

                        public function foo(string $s = null) : void {

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
                        private function fooFoo(): void {
                        }
                    }

                    class B {
                        use T;
                    }

                    class C extends B {
                        public function doFoo(): void {
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

                        public function assignToFoo(): void {
                            $this->foo = 5;
                        }
                    }',
                'error_message' => 'MissingPropertyType - src/somefile.php:3 - Property T::$foo does not have a ' .
                    'declared type - consider null|int',
            ],
            'missingPropertyTypeWithConstructorInit' => [
                '<?php
                    trait T {
                        public $foo;
                    }
                    class A {
                        use T;

                        public function __construct(): void {
                            $this->foo = 5;
                        }
                    }',
                'error_message' => 'MissingPropertyType - src/somefile.php:3 - Property T::$foo does not have a ' .
                    'declared type - consider int',
            ],
            'missingPropertyTypeWithConstructorInitAndNull' => [
                '<?php
                    trait T {
                        public $foo;
                    }
                    class A {
                        use T;

                        public function __construct(): void {
                            $this->foo = 5;
                        }

                        public function makeNull(): void {
                            $this->foo = null;
                        }
                    }',
                'error_message' => 'MissingPropertyType - src/somefile.php:3 - Property T::$foo does not have a ' .
                    'declared type - consider null|int',
            ],
            'missingPropertyTypeWithConstructorInitAndNullDefault' => [
                '<?php
                    trait T {
                        public $foo = null;
                    }
                    class A {
                        use T;

                        public function __construct(): void {
                            $this->foo = 5;
                        }
                    }',
                'error_message' => 'MissingPropertyType - src/somefile.php:3 - Property T::$foo does not have a ' .
                    'declared type - consider int|null',
            ],
            'redefinedTraitMethodInSubclass' => [
                '<?php
                    trait T {
                        public function fooFoo(): void {
                        }
                    }

                    class B {
                        use T;
                    }

                    class C extends B {
                        public function fooFoo(string $a): void {
                        }
                    }',
                'error_message' => 'MethodSignatureMismatch',
            ],
            'missingTraitPropertyType' => [
                '<?php
                    trait T {
                        public $foo;
                    }

                    class A {
                        use T;
                    }',
                'error_message' => 'MissingPropertyType',
            ],
        ];
    }
}
