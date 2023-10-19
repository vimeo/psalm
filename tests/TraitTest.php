<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

class TraitTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'accessiblePrivateMethodFromTrait' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
            'isAClassTraitUserStringClass' => [
                'code' => '<?php
                    trait T {
                        public function f(): void {
                            if (is_a(static::class, B::class, true)) { }
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
            'isAClassTraitUserClassConstant' => [
                'code' => '<?php
                    trait T {
                        public function f(): void {
                            if (is_a(static::class, B::class, true)) { }
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
                'code' => '<?php
                    trait T {
                      abstract public function foo(): void;
                    }

                    class A {
                      public function foo(): void {}
                    }',
            ],
            'useTraitInSubclassWithAbstractMethod' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    class C
                    {
                        use T2;

                        use T1 {
                            traitFunc as aliasedTraitFunc;
                        }

                        public static function func(): void
                        {
                            static::aliasedTraitFunc();
                        }
                    }
                    trait T1
                    {
                        public static function traitFunc(): void {}
                    }
                    trait T2 { }',
            ],
            'mapAndUse' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    trait A {
                        public function foo(): string {
                            return B::class;
                        }
                    }

                    trait B {}

                    class C {
                        use A;
                    }',
            ],
            'noRedundantConditionForTraitStatic' => [
                'code' => '<?php
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
                    }',
            ],
            'nonMemoizedAssertions' => [
                'code' => '<?php
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
                    }',
            ],
            'manyTraitAliases' => [
                'code' => '<?php
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

                    $b->bad();',
            ],
            'inheritedProtectedTraitMethodAccess' => [
                'code' => '<?php
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
                    }',
            ],
            'inheritedPublicTraitMethodAccess' => [
                'code' => '<?php
                    trait T {
                        private function bar() : void {}
                    }

                    class A {
                        use T {
                            bar as public;
                        }
                    }

                    (new A)->bar();',
            ],
            'allowImplementMethodMadePublicInClass' => [
                'code' => '<?php
                    interface I {
                        public function boo() : void;
                    }

                    trait T {
                        private function boo() : void {}
                    }

                    class A implements I {
                        use T { boo as public; }
                    }',
            ],
            'allowImplementMethodMadePublicInParent' => [
                'code' => '<?php
                    interface I {
                        public function boo() : void;
                    }

                    trait T {
                        private function boo() : void {}
                    }

                    class B {
                        use T { boo as public; }
                    }

                    class BChild extends B implements I {}',
            ],
            'allowTraitParentDefinition' => [
                'code' => '<?php
                    class A {}

                    class C extends A
                    {
                        use T;
                    }

                    trait T
                    {
                        public function bar() : ?C
                        {
                            if ($this instanceof A) {
                                return $this;
                            }

                            return null;
                        }
                    }',
            ],
            'noCrashOnUndefinedIgnoredTrait' => [
                'code' => '<?php
                    /** @psalm-suppress UndefinedTrait */
                    class C {
                        use UnknownTrait;
                    }',
            ],
            'reconcileStaticTraitProperties' => [
                'code' => '<?php
                    trait T {
                        /**
                         * @var string|null
                         */
                        private static $b;

                        private static function booA(): string {
                            if (self::$b === null) {
                                return "hello";
                            }
                            return self::$b;
                        }
                    }

                    class C {
                        use T;
                    }',
            ],
            'covariantAbstractReturn' => [
                'code' => '<?php
                    trait T {
                        /** @return iterable */
                        abstract public function bar();
                    }

                    class C {
                        use T;

                        /** @return array */
                        public function bar() { return []; }
                    }',
            ],
            'traitSelfParam' => [
                'code' => '<?php
                    trait T {
                        public function bar(self $object): self {
                            return $this;
                        }
                    }

                    class Foo {
                        use T;
                    }

                    $f1 = new Foo();
                    $f2 = (new Foo())->bar($f1);',
            ],
            'traitSelfDocblockReturn' => [
                'code' => '<?php
                    trait T {
                        /** @return self */
                        public function getSelf() {
                            return $this;
                        }
                    }

                    class C {
                        use T;
                    }',
            ],
            'abstractThisMethod' => [
                'code' => '<?php
                    trait ATrait {
                        /** @return $this */
                        abstract public function bar();
                    }

                    class C {
                        use ATrait;

                        /** @return $this */
                        public function bar() {
                            return $this;
                        }
                    }',
            ],
            'classAliasedTrait' => [
                'code' => '<?php
                    trait FeatureV1 {}

                    class_alias(FeatureV1::class, Feature::class);

                    class Application {
                        use Feature;
                    }',
            ],
            'renameMethodNoCrash' => [
                'code' => '<?php

                    trait HelloTrait {
                        protected function sayHello() : string {
                            return "Hello";
                        }
                    }

                    class Person {
                        use HelloTrait {
                            sayHello as originalSayHello;
                        }

                        protected function sayHello() : string {
                            return $this->originalSayHello();
                        }
                    }

                    class BrokenPerson extends Person {
                        protected function originalSayHello() : string {
                            return "bad";
                        }
                    }',
            ],
            'instanceofStaticInsideTrait' => [
                'code' => '<?php
                    trait T {
                        /**
                         * @param mixed $instance
                         * @return ?static
                         */
                        public static function filterInstance($instance) {
                            return $instance instanceof static ? $instance : null;
                        }
                    }

                    class A {
                        use T;
                    }',
            ],
            'propertyNotDefinedInTrait' => [
                'code' => '<?php
                    class A1 {
                        use A2;

                        public static string $titlefield = "blah";
                    }

                    trait A2 {
                        public static function test() : string {
                            /**
                             * @var string
                             */
                            $sortfield = (isset(static::$sortfield)) ?
                                        static::$sortfield
                                        : static::$titlefield;
                            return $sortfield;
                        }
                    }',
            ],
            'staticNotBoundInFinal' => [
                'code' => '<?php
                    trait Foo {
                        /**
                         * @return static
                         */
                        final public function foo(): self
                        {
                            return $this;
                        }
                    }

                    class A {
                        use Foo;
                    }',
            ],
            'staticReturnWithFinal' => [
                'code' => '<?php
                    trait T {
                        /** @return static */
                        public function instance() {
                            return new static();
                        }
                    }

                    final class A {
                        use T;
                    }',
            ],
            'suppressIssueOnTrait' => [
                'code' => '<?php
                    /** @psalm-suppress InvalidAttribute */
                    #[Attribute]
                    trait Foo {}',
            ],
            'noCrashOnConditionalTrait' => [
                'code' => '<?php
                    namespace NS;
                    if (rand(0, 1)) {
                        trait T {}
                    }
                ',
            ],
            'constant in trait' => [
                'code' => <<<'PHP'
                    <?php
                    trait TraitA {
                        public const PUBLIC_CONST = 'PUBLIC_CONST';
                        protected const PROTECTED_CONST = 'PROTECTED_CONST';
                        private const PRIVATE_CONST = 'PRIVATE_CONST';
                    }
                    class ClassB {
                        use TraitA;
                        public static function getPublicConst(): string { return self::PUBLIC_CONST; }
                        public static function getProtectedConst(): string { return self::PROTECTED_CONST; }
                        public static function getPrivateConst(): string { return self::PRIVATE_CONST; }
                    }
                    class ClassC extends ClassB {
                        public static function getPublicConst(): string { return self::PUBLIC_CONST; }
                        public static function getProtectedConst(): string { return self::PROTECTED_CONST; }
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'constant in trait with alias' => [
                'code' => <<<'PHP'
                    <?php
                    trait TraitA { private const PRIVATE_CONST = 'PRIVATE_CONST'; }
                    class ClassB { use TraitA { PRIVATE_CONST as public PUBLIC_CONST; } }
                    $c = ClassB::PUBLIC_CONST;
                    PHP,
                'assertions' => ['$c' => 'string'],
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'inaccessiblePrivateMethodFromInheritedTrait' => [
                'code' => '<?php
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
                'code' => '<?php
                    class B {
                        use A;
                    }',
                'error_message' => 'UndefinedTrait',
            ],
            'missingPropertyType' => [
                'code' => '<?php
                    trait T {
                        public $foo = null;
                    }
                    class A {
                        use T;

                        public function assignToFoo(): void {
                            $this->foo = 5;
                        }
                    }',
                'error_message' => 'MissingPropertyType - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:32 - Property T::$foo does not have a ' .
                    'declared type - consider int|null',
            ],
            'missingPropertyTypeWithConstructorInit' => [
                'code' => '<?php
                    trait T {
                        public $foo;
                    }
                    class A {
                        use T;

                        public function __construct() {
                            $this->foo = 5;
                        }
                    }',
                'error_message' => 'MissingPropertyType - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:32 - Property T::$foo does not have a ' .
                    'declared type - consider int',
            ],
            'missingPropertyTypeWithConstructorInitAndNull' => [
                'code' => '<?php
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
                'error_message' => 'MissingPropertyType - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:32 - Property T::$foo does not have a ' .
                    'declared type - consider int|null',
            ],
            'missingPropertyTypeWithConstructorInitAndNullDefault' => [
                'code' => '<?php
                    trait T {
                        public $foo = null;
                    }
                    class A {
                        use T;

                        public function __construct() {
                            $this->foo = 5;
                        }
                    }',
                'error_message' => 'MissingPropertyType - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:32 - Property T::$foo does not have a ' .
                    'declared type - consider int|null',
            ],
            'redefinedTraitMethodInSubclass' => [
                'code' => '<?php
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
                'code' => '<?php
                    trait T {
                        public $foo = 5;
                    }

                    class A {
                        use T;
                    }',
                'error_message' => 'MissingPropertyType',
            ],
            'nestedTraitWithBadReturnType' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'error_message' => 'InaccessibleMethod',
            ],
            'preventTraitPropertyType' => [
                'code' => '<?php
                    trait T {}

                    class X {
                      /** @var T|null */
                      public $hm;
                    }',
                'error_message' => 'UndefinedDocblockClass',
            ],
            'constant declaration in trait, php <8.2.0' => [
                'code' => <<<'PHP'
                    <?php
                    trait A { const B = 0; }
                    PHP,
                'error_message' => 'ConstantDeclarationInTrait',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'duplicateTraitProperty' => [
                'code' => '<?php
                    trait T {
                        public mixed $foo = 5;
                        protected static mixed $foo;
                    }
                    ',
                'error_message' => 'DuplicateProperty',
            ],
        ];
    }
}
