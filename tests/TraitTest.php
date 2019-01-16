<?php
namespace Psalm\Tests;

class TraitTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return array
     */
    public function providerValidCodeParse()
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
                            if (get_class($this) === B::class) {
                                $this->foo();
                            }
                        }
                    }

                    class A {
                        use T;
                    }

                    class B {
                        use T;

                        public function foo() : void {}
                    }',
            ],
            'staticClassTraitUser' => [
                '<?php
                    trait T {
                        public function f(): void {
                            if (static::class === B::class) {
                                $this->foo();
                            }
                        }
                    }

                    class A {
                        use T;
                    }

                    class B {
                        use T;

                        public function foo() : void {}
                    }',
            ],
            'isAClassTraitUser' => [
                '<?php
                    trait T {
                        public function f(): void {
                            if (is_a(static::class, "B")) { }
                        }
                    }

                    class A {
                        use T;
                    }

                    class B {
                        use T;

                        public function foo() : void {}
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
            'aliasedMethodInternalCallNoReplacement' => [
                '<?php
                    trait T {
                        public function foo() : int {
                            return $this->bar();
                        }

                        public function bar() : int {
                            return 3;
                        }
                    }

                    class A {
                        use T {
                            bar as bat;
                        }

                        public function baz() : int {
                            return $this->bar();
                        }
                    }',
            ],
            'aliasedMethodInternalCallWithLocalDefinition' => [
                '<?php
                    trait T {
                        public function bar() : int {
                            return 3;
                        }
                    }

                    class A {
                        use T {
                            bar as bat;
                        }

                        public function bar() : string {
                            return "hello";
                        }

                        public function baz() : string {
                            return $this->bar();
                        }
                    }',
            ],
            'allMethodsReplaced' => [
                '<?php
                    trait T {
                        protected function foo() : void {}

                        public function bat() : void {
                            $this->foo();
                        }
                    }

                    class C {
                        use T;

                        protected function foo(string $s) : void {}

                        public function bat() : void {
                            $this->foo("bat");
                        }
                    }',
            ],
            'aliasedPrivateMethodInternalCallWithLocalDefinition' => [
                '<?php
                    trait T1 {
                        use T2;

                        private function foo() : int {
                            return $this->bar();
                        }
                    }

                    trait T2 {
                        private function bar() : int {
                            return 3;
                        }
                    }

                    class A {
                        use T1;

                        private function baz() : int {
                            return $this->bar();
                        }
                    }',
            ],
            'traitClassConst' => [
                '<?php
                    trait A {
                        public function foo(): string {
                            return B::class;
                        }
                    }

                    trait B {}

                    class C {
                        use A;
                    }'
            ],
            'noRedundantConditionForTraitStatic' => [
                '<?php
                    trait Foo {
                        public function bar() : array {
                            $type = static::class;
                            $r = new \ReflectionClass($type);
                            $values = $r->getConstants();
                            $callback =
                                /** @param mixed $v */
                                function ($v) : bool {
                                    return \is_int($v) || \is_string($v);
                                };

                            if (is_a($type, \Bat::class, true)) {
                                $callback =
                                    /** @param mixed $v */
                                    function ($v) : bool {
                                        return \is_int($v) && 0 === ($v & $v - 1) && $v > 0;
                                    };
                            }

                            return array_filter($values, $callback);
                        }
                    }

                    class Bar {
                        use Foo;
                    }

                    class Bat {
                        use Foo;
                    }'
            ],
            'nonMemoizedAssertions' => [
                '<?php
                    trait T {
                        public function compare(O $other) : void {
                            if ($other instanceof self) {
                                if ($other->value === $this->value) {}
                            }
                        }
                    }

                    class O {}

                    class A extends O {
                        use T;

                        /** @var string */
                        private $value;

                        public function __construct(string $string) {
                           $this->value = $string;
                        }
                    }

                    class B extends O {
                        use T;

                        /** @var bool */
                        private $value;

                        public function __construct(bool $bool) {
                           $this->value = $bool;
                        }
                    }'
            ],
            'manyTraitAliases' => [
                '<?php
                    trait Foo {
                        public static function staticMethod():void {}
                        public function nonstatic():void {}
                    }

                    Class Bar {
                        use Foo {
                            Foo::staticMethod as foo;
                            Foo::staticMethod as foobar;
                            Foo::staticMethod as fine;
                            Foo::nonstatic as bad;
                            Foo::nonstatic as good;
                        }
                    }

                    $b = new Bar();

                    Bar::fine();
                    $b::fine();
                    $b->fine();

                    $b->good();

                    Bar::foo();
                    Bar::foobar();

                    $b::foo();
                    $b::foobar();

                    $b->foo();
                    $b->foobar();

                    $b->bad();'
            ],
            'inheritedProtectedTraitMethodAccess' => [
                '<?php
                    trait T {
                        private function bar() : void {}
                    }

                    class A {
                        use T {
                            bar as protected;
                        }
                    }

                    class AChild extends A {
                        public function foo() : void {
                            $this->bar();
                        }
                    }'
            ],
            'inheritedPublicTraitMethodAccess' => [
                '<?php
                    trait T {
                        private function bar() : void {}
                    }

                    class A {
                        use T {
                            bar as public;
                        }
                    }

                    (new A)->bar();'
            ],
            'allowImplementMethodMadePublicInClass' => [
                '<?php
                    interface I {
                        public function boo();
                    }

                    trait T {
                        private function boo() : void {}
                    }

                    class A implements I {
                        use T { boo as public; }
                    }',
            ],
            'allowImplementMethodMadePublicInParent' => [
                '<?php
                    interface I {
                        public function boo();
                    }

                    trait T {
                        private function boo() : void {}
                    }

                    class B {
                        use T { boo as public; }
                    }

                    class BChild extends B implements I {}',
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerInvalidCodeParse()
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
                'error_message' => 'MissingPropertyType - src' . DIRECTORY_SEPARATOR . 'somefile.php:3 - Property T::$foo does not have a ' .
                    'declared type - consider int|null',
            ],
            'missingPropertyTypeWithConstructorInit' => [
                '<?php
                    trait T {
                        public $foo;
                    }
                    class A {
                        use T;

                        public function __construct() {
                            $this->foo = 5;
                        }
                    }',
                'error_message' => 'MissingPropertyType - src' . DIRECTORY_SEPARATOR . 'somefile.php:3 - Property T::$foo does not have a ' .
                    'declared type - consider int',
            ],
            'missingPropertyTypeWithConstructorInitAndNull' => [
                '<?php
                    trait T {
                        public $foo;
                    }
                    class A {
                        use T;

                        public function __construct() {
                            $this->foo = 5;
                        }

                        public function makeNull(): void {
                            $this->foo = null;
                        }
                    }',
                'error_message' => 'MissingPropertyType - src' . DIRECTORY_SEPARATOR . 'somefile.php:3 - Property T::$foo does not have a ' .
                    'declared type - consider int|null',
            ],
            'missingPropertyTypeWithConstructorInitAndNullDefault' => [
                '<?php
                    trait T {
                        public $foo = null;
                    }
                    class A {
                        use T;

                        public function __construct() {
                            $this->foo = 5;
                        }
                    }',
                'error_message' => 'MissingPropertyType - src' . DIRECTORY_SEPARATOR . 'somefile.php:3 - Property T::$foo does not have a ' .
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
            'nestedTraitWithBadReturnType' => [
                '<?php
                    trait A {
                        public function foo() : string {
                            return 5;
                        }
                    }

                    trait B {
                        use A;
                    }

                    class C {
                        use B;
                    }',
                'error_message' => 'InvalidReturnType',
            ],
            'replaceTraitMethod' => [
                '<?php
                    trait T {
                        protected function foo() : void {}

                        public function bat() : void {
                            $this->foo();
                        }
                    }

                    class C {
                        use T;

                        protected function foo(string $s) : void {}
                    }',
                'error_message' => 'TooFewArguments',
            ],
            'traitMethodMadePrivate' => [
                '<?php
                    trait T {
                        public function foo() : void {
                            echo "here";
                        }
                    }

                    class C {
                        use T {
                            foo as private traitFoo;
                        }

                        public function bar() : void {
                            $this->traitFoo();
                        }
                    }

                    class D extends C {
                        public function bar() : void {
                            $this->traitFoo(); // should fail
                        }
                    }',
                'error_message' => 'InaccessibleMethod'
            ],
        ];
    }
}
