<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class InternalAnnotationTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'internalMethodWithCall' => [
                'code' => '<?php
                    namespace A {
                        class Foo {
                            /**
                             * @internal
                             */
                            public static function barBar(): void {
                            }
                        }
                    }

                    namespace A\B {
                        class Bat {
                            public function batBat() : void {
                                \A\Foo::barBar();
                            }
                        }
                    }',
            ],
            'internalClassWithStaticCall' => [
                'code' => '<?php
                    namespace A {
                        /**
                         * @internal
                         */
                        class Foo {
                            public static function barBar(): void {
                            }
                        }
                    }

                    namespace A\B {
                        class Bat {
                            public function batBat() : void {
                                \A\Foo::barBar();
                            }
                        }
                    }',
            ],
            'internalClassWithInstanceCall' => [
                'code' => '<?php
                    namespace A {
                        /**
                         * @internal
                         */
                        class Foo {
                            public function barBar(): void {
                            }
                        }

                        function getFoo(): Foo {
                            return new Foo();
                        }
                    }

                    namespace A\B {
                        class Bat {
                            public function batBat(): void {
                                \A\getFoo()->barBar();
                            }
                        }
                    }',
            ],
            'internalClassWithPropertyFetch' => [
                'code' => '<?php
                    namespace A\B {
                        /**
                         * @internal
                         */
                        class Foo {
                            public int $barBar = 0;
                        }

                        function getFoo(): Foo {
                            return new Foo();
                        }
                    }

                    namespace A\C {
                        class Bat {
                            public function batBat(\A\B\Foo $instance): void {
                                \A\B\getFoo()->barBar;
                            }
                        }
                    }',
            ],
            'internalClassExtendingNamespaceWithStaticCall' => [
                'code' => '<?php
                    namespace A {
                        /**
                         * @internal
                         */
                        class Foo extends \B\Foo {
                            public function __construct() {
                                parent::__construct();
                            }
                            public static function barBar(): void {
                            }
                        }
                    }

                    namespace B {
                        class Foo {
                            public function __construct() {
                                static::barBar();
                            }

                            public static function barBar(): void {
                            }
                        }
                    }',
            ],
            'internalClassWithNew' => [
                'code' => '<?php
                    namespace A {
                        /**
                         * @internal
                         */
                        class Foo { }
                    }

                    namespace A\B {
                        class Bat {
                            public function batBat() : void {
                                $a = new \A\Foo();
                            }
                        }
                    }',
            ],
            'internalClassWithExtends' => [
                'code' => '<?php
                    namespace A {
                        /**
                         * @internal
                         */
                        class Foo { }
                    }

                    namespace A\B {
                        class Bar extends \A\Foo {}
                    }',
            ],
            'internalPropertyGet' => [
                'code' => '<?php
                    namespace A {
                        class Foo {
                            /**
                             * @internal
                             * @var ?int
                             */
                            public $foo;
                        }
                    }

                    namespace A\B {
                        class Bat {
                            public function batBat() : void {
                                echo (new \A\Foo)->foo;
                            }
                        }
                    }',
            ],
            'internalPropertySet' => [
                'code' => '<?php
                    namespace A {
                        class Foo {
                            /**
                             * @internal
                             * @var ?int
                             */
                            public $foo;
                        }
                    }
                    namespace A\B {
                        class Bat {
                            public function batBat() : void {
                                $a = new \A\Foo;
                                $a->foo = 5;
                            }
                        }
                    }',
            ],
            'internalMethodInTraitWithCall' => [
                'code' => '<?php
                    namespace A {
                        /**
                         * @internal
                         */
                        trait T {
                            public static function barBar(): void {
                            }
                        }

                        class Foo {
                            use T;

                        }
                    }

                    namespace B {
                        class Bat {
                            public function batBat() : void {
                                \A\Foo::barBar();
                            }
                        }
                    }',
            ],
            'magicPropertyGetInternalImplicit' => [
                'code' => '<?php
                    namespace A {
                        class Foo {
                            /**
                             * @internal
                             */
                            public function __get(string $s): string {
                              return "hello";
                            }
                        }
                    }
                    namespace B {
                        class Bat {
                            public function batBat() : void {
                                echo (new \A\Foo)->foo;
                            }
                        }
                    }',
            ],
            'constInternalClass' => [
                'code' => '<?php
                    namespace A {
                        /**
                         * @internal
                         */
                        class Foo {
                            const AA = "a";
                        }

                        class Bat {
                            public function batBat() : void {
                                echo \A\Foo::AA;
                            }
                        }
                    }',
            ],
            'psalmInternalMethodWithCall' => [
                'code' => '<?php
                    namespace A\B {
                        class Foo {
                            /**
                             * @psalm-internal A\B
                             */
                            public static function barBar(): void {
                            }
                        }
                    }

                    namespace A\B\C {
                        class Bat {
                            public function batBat() : void {
                                \A\B\Foo::barBar();
                            }
                        }
                    }',
            ],
            'internalMethodWithCallWithCaseMisMatched' => [
                'code' => '<?php
                    namespace A\B {
                        class Foo {
                            /**
                             * @psalm-internal A\B
                             */
                            public static function barBar(): void {
                            }
                        }
                    }

                    namespace a\b\c {
                        class Bat {
                            public function batBat() : void {
                                \A\B\Foo::barBar();
                            }
                        }
                    }',
            ],
            'psalmInternalMethodWithTrailingWhitespace' => [
                'code' => '<?php
                    namespace A\B {
                        class Foo {
                            /** @psalm-internal A\B */
                            public static function barBar(): void {
                                self::barBar();
                            }
                        }
                    }',
            ],
            'internalToClassMethodWithCallSameNamespace' => [
                'code' => '<?php
                    namespace A\B {
                        class Foo {
                            /**
                             * @psalm-internal A\B
                             */
                            public static function barBar(): void {
                            }

                            public static function foo(): void {
                                self::barBar();
                            }
                        }
                    }',
            ],
            'psalmInternalClassWithStaticCall' => [
                'code' => '<?php
                    namespace A\B {
                        /**
                         * @psalm-internal A
                         */
                        class Foo {
                            public static function barBar(): void {
                            }
                        }
                    }

                    namespace A\B\C {
                        class Bat {
                            public function batBat() : void {
                                \A\B\Foo::barBar();
                            }
                        }
                    }',
            ],
            'psalmInternalClassWithInstanceCall' => [
                'code' => '<?php
                    namespace A\B {
                        /**
                         * @psalm-internal A\B
                         */
                        class Foo {
                            public function barBar(): void {
                            }
                        }

                        function getFoo(): Foo {
                            return new Foo();
                        }
                    }

                    namespace A\B\C {
                        class Bat {
                            public function batBat(\A\B\Foo $instance): void {
                                \A\B\getFoo()->barBar();
                            }
                        }
                    }',
            ],
            'psalmInternalClassWithPropertyFetch' => [
                'code' => '<?php
                    namespace A\B {
                        /**
                         * @psalm-internal A\B
                         */
                        class Foo {
                            public int $barBar = 0;
                        }

                        function getFoo(): Foo {
                            return new Foo();
                        }
                    }

                    namespace A\B\C {
                        class Bat {
                            public function batBat(\A\B\Foo $instance): void {
                                \A\B\getFoo()->barBar;
                            }
                        }
                    }',
            ],
            'psalmInternalClassExtendingNamespaceWithStaticCall' => [
                'code' => '<?php
                    namespace A {
                        /**
                         * @psalm-internal A
                         */
                        class Foo extends \B\Foo {
                            public function __construct() {
                                parent::__construct();
                            }
                            public static function barBar(): void {
                            }
                        }
                    }

                    namespace B {
                        class Foo {
                            public function __construct() {
                                static::barBar();
                            }

                            public static function barBar(): void {
                            }
                        }
                    }',
            ],
            'psalmInternalClassWithNew' => [
                'code' => '<?php
                    namespace A\B {
                        /**
                         * @psalm-internal A\B
                         */
                        class Foo { }
                    }

                    namespace A\B\C {
                        class Bat {
                            public function batBat() : void {
                                $a = new \A\B\Foo();
                            }
                        }
                    }',
            ],
            'psalmInternalClassWithInstanceOf' => [
                'code' => '<?php
                    namespace A\B {
                        interface Bar {};

                        /**
                         * @psalm-internal A\B
                         */
                        class Foo { }
                    }

                    namespace A\B\C {
                        class Bat {
                            public function batBat(\A\B\Bar $bar) : void {
                                $bar instanceOf \A\B\Foo;
                            }
                        }
                    }',
            ],
            'psalmInternalClassWithExtends' => [
                'code' => '<?php
                    namespace A\B {
                        /**
                         * @psalm-internal A\B
                         */
                        class Foo { }
                    }

                    namespace A\B\C {
                        class Bar extends \A\B\Foo {}
                    }',
            ],
            'psalmInternalClassWithTrailingWhitespace' => [
                'code' => '<?php
                    namespace A\B {
                        /** @psalm-internal A\B */
                        class Foo {}
                        class Bar extends Foo {}
                    }',
            ],
            'psalmInternalPropertyGet' => [
                'code' => '<?php
                    namespace A\B {
                        class Foo {
                            /**
                             * @psalm-internal A\B
                             * @var ?int
                             */
                            public $foo;
                        }
                    }

                    namespace A\B\C {
                        class Bat {
                            public function batBat() : void {
                                echo (new \A\B\Foo)->foo;
                            }
                        }
                    }',
            ],
            'psalmInternalPropertySet' => [
                'code' => '<?php
                    namespace A\B {
                        class Foo {
                            /**
                             * @psalm-internal A\B
                             * @var ?int
                             */
                            public $foo;
                        }
                    }
                    namespace A\B\C {
                        class Bat {
                            public function batBat() : void {
                                $a = new \A\B\Foo;
                                $a->foo = 5;
                            }
                        }
                    }',
            ],
            'psalmInternalPropertyWithTrailingWhitespace' => [
                'code' => '<?php
                    namespace A\B {
                        class Foo {
                            /** @psalm-internal A\B */
                            public int $foo = 0;

                            public function barBar() : void {
                                $this->foo = 42;
                            }
                        }
                    }',
            ],
            'psalmInternalMethodInTraitWithCall' => [
                'code' => '<?php
                    namespace A {
                        /**
                         * @psalm-internal A
                         */
                        trait T {
                            public static function barBar(): void {
                            }
                        }

                        class Foo {
                            use T;

                        }
                    }

                    namespace B {
                        class Bat {
                            public function batBat() : void {
                                \A\Foo::barBar();
                            }
                        }
                    }',
            ],
            'psalmInternalMultipleNamespaces' => [
                'code' => '<?php
                    namespace A
                    {
                        class Foo
                        {
                            /**
                             * @psalm-internal \B
                             * @psalm-internal \C
                             */
                            public static function foobar(): void {}
                        }
                    }

                    namespace B
                    {
                        \A\Foo::foobar();
                    }

                    namespace C
                    {
                        \A\Foo::foobar();
                    }
                ',
            ],
            'psalmInternalToClass' => [
                'code' => '<?php
                    namespace A
                    {
                        class Foo
                        {
                            /** @psalm-internal B\Bar */
                            public static function foo(): void {}

                            /** @psalm-internal B\Bar */
                            public function bar(): void {}
                        }
                    }

                    namespace B
                    {
                        class Bar
                        {
                            public function baz(): void
                            {
                                \A\Foo::foo();
                                $foo = new \A\Foo();
                                $foo->bar();
                            }
                        }
                    }
                ',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'internalMethodWithCall' => [
                'code' => '<?php
                    namespace A {
                        class Foo {
                            /**
                             * @internal
                             */
                            public static function barBar(): void {
                            }
                        }
                    }

                    namespace B {
                        class Bat {
                            public function batBat(): void {
                                \A\Foo::barBar();
                            }
                        }
                    }',
                'error_message' => 'The method A\Foo::barBar is internal to A but called from B\Bat',
            ],
            'internalCloneMethodWithCall' => [
                'code' => '<?php
                    namespace A {
                        class Foo {
                            /**
                             * @internal
                             */
                            public function __clone() {
                            }
                        }
                    }

                    namespace B {
                        class Bat {
                            public function batBat(): void {
                                $a = new \A\Foo;
                                $aa = clone $a;
                            }
                        }
                    }',
                'error_message' => 'The method A\Foo::__clone is internal to A but called from B',
            ],
            'internalMethodWithCallFromRootNamespace' => [
                'code' => '<?php
                    namespace A {
                        class Foo {
                            /**
                             * @internal
                             */
                            public static function barBar(): void {
                            }
                        }
                    }

                    namespace {
                        \A\Foo::barBar();
                    }',
                'error_message' => 'The method A\Foo::barBar is internal to A but called from root namespace',
            ],
            'internalClassWithStaticCall' => [
                'code' => '<?php
                    namespace A {
                        /**
                         * @internal
                         */
                        class Foo {
                            public static function barBar(): void {
                            }
                        }
                    }

                    namespace B {
                        class Bat {
                            public function batBat() {
                                \A\Foo::barBar();
                            }
                        }
                    }',
                'error_message' => 'InternalClass',
            ],
            'internalClassWithInstanceCall' => [
                'code' => '<?php
                    namespace A {
                        /**
                         * @internal
                         */
                        class Foo {
                            public function barBar(): void {
                            }
                        }

                        function getFoo(): Foo {
                            return new Foo();
                        }
                    }

                    namespace B {
                        class Bat {
                            public function batBat(): void {
                                \A\getFoo()->barBar();
                            }
                        }
                    }',
                'error_message' => 'InternalMethod',
            ],
            'internalClassWithPropertyFetch' => [
                'code' => '<?php
                    namespace A\B {
                        /**
                         * @internal
                         */
                        class Foo {
                            public int $barBar = 0;
                        }

                        function getFoo(): Foo {
                            return new Foo();
                        }
                    }

                    namespace C {
                        class Bat {
                            public function batBat(): void {
                                \A\B\getFoo()->barBar;
                            }
                        }
                    }',
                'error_message' => 'A\B\Foo::$barBar is internal',
            ],
            'internalClassWithNew' => [
                'code' => '<?php
                    namespace A {
                        /**
                         * @internal
                         */
                        class Foo { }
                    }

                    namespace B {
                        class Bat {
                            public function batBat() {
                                $a = new \A\Foo();
                            }
                        }
                    }',
                'error_message' => 'InternalClass',
            ],
            'internalClassWithExtends' => [
                'code' => '<?php
                    namespace A {
                        /**
                         * @internal
                         */
                        class Foo { }
                    }

                    namespace B {
                        class Bar extends \A\Foo {}
                    }',
                'error_message' => 'InternalClass',
            ],
            'internalPropertyGet' => [
                'code' => '<?php
                    namespace A {
                        class Foo {
                            /**
                             * @internal
                             * @var ?int
                             */
                            public $foo;
                        }
                    }

                    namespace B {
                        class Bat {
                            public function batBat() : void {
                                echo (new \A\Foo)->foo;
                            }
                        }
                    }',
                'error_message' => 'InternalProperty',
            ],
            'internalPropertySet' => [
                'code' => '<?php
                    namespace A {
                        class Foo {
                            /**
                             * @internal
                             * @var ?int
                             */
                            public $foo;
                        }
                    }
                    namespace B {
                        class Bat {
                            public function batBat() : void {
                                $a = new \A\Foo;
                                $a->foo = 5;
                            }
                        }
                    }',
                'error_message' => 'InternalProperty',
            ],
            'magicPropertyGetInternalExplicit' => [
                'code' => '<?php
                    namespace A {
                        class Foo {
                            /**
                             * @internal
                             */
                            public function __get(string $s): string {
                              return "hello";
                            }
                        }
                    }
                    namespace B {
                        class Bat {
                            public function batBat() : void {
                                echo (new \A\Foo)->__get("foo");
                            }
                        }
                    }',
                'error_message' => 'InternalMethod',
            ],
            'constInternalClass' => [
                'code' => '<?php
                    namespace A {
                        /**
                         * @internal
                         */
                        class Foo {
                            const AA = "a";
                        }
                    }
                    namespace B {
                        class Bat {
                            public function batBat() : void {
                                echo \A\Foo::AA;
                            }
                        }
                    }',
                'error_message' => 'InternalClass',
            ],
            'psalmInternalMethodWithCall' => [
                'code' => '<?php
                    namespace A\B {
                        class Foo {
                            /**
                             * @psalm-internal A\B
                             */
                            public static function barBar(): void {
                            }
                        }
                    }

                    namespace A\C {
                        class Bat {
                            public function batBat(): void {
                                \A\B\Foo::barBar();
                            }
                        }
                    }',
                'error_message' => 'The method A\B\Foo::barBar is internal to A\B',
            ],
            'psalmInternalToClassMethodWithCall' => [
                'code' => '<?php
                    namespace A\B {
                        class Foo {
                            /**
                             * @psalm-internal A\B\Foo
                             */
                            public static function barBar(): void {
                            }
                        }
                    }

                    namespace A\C {
                        class Bat {
                            public function batBat(): void {
                                \A\B\Foo::barBar();
                            }
                        }
                    }',
                'error_message' => 'The method A\B\Foo::barBar is internal to A\B\Foo',
            ],
            'psalmInternalClassWithStaticCall' => [
                'code' => '<?php
                    namespace A\B {
                        /**
                         * @psalm-internal A\B
                         */
                        class Foo {
                            public static function barBar(): void {
                            }
                        }
                    }

                    namespace A\C {
                        class Bat {
                            public function batBat(): void {
                                \A\B\Foo::barBar();
                            }
                        }
                    }',
                'error_message' => 'InternalClass',
            ],
            'psalmInternalClassWithPropertyFetch' => [
                'code' => '<?php
                    namespace A\B {
                        /**
                         * @psalm-internal A\B
                         */
                        class Foo {
                            public int $barBar = 0;
                        }

                        function getFoo(): Foo {
                            return new Foo();
                        }
                    }

                    namespace A\C {
                        class Bat {
                            public function batBat(): void {
                                \A\B\getFoo()->barBar;
                            }
                        }
                    }',
                'error_message' => 'A\B\Foo::$barBar is internal to A\B',
            ],
            'psalmInternalClassWithInstanceCall' => [
                'code' => '<?php
                    namespace A\B {
                        /**
                         * @psalm-internal A\B
                         */
                        class Foo {
                            public function barBar(): void {
                            }
                        }

                        function getFoo(): Foo {
                            return new Foo();
                        }
                    }

                    namespace A\C {
                        class Bat {
                            public function batBat(): void {
                                \A\B\getFoo()->barBar();
                            }
                        }
                    }',
                'error_message' => 'The method A\B\Foo::barBar is internal to A\B',
            ],
            'psalmInternalClassWithNew' => [
                'code' => '<?php
                    namespace A\B {
                        /**
                         * @psalm-internal A\B
                         */
                        class Foo { }
                    }

                    namespace A\C {
                        class Bat {
                            public function batBat(): void {
                                $a = new \A\B\Foo();
                            }
                        }
                    }',
                'error_message' => 'InternalClass',
            ],
            'psalmInternalClassWithExtends' => [
                'code' => '<?php
                    namespace A\B {
                        /**
                         * @psalm-internal A\B
                         */
                        class Foo { }
                    }

                    namespace A\C {
                        class Bar extends \A\B\Foo {}
                    }',
                'error_message' => 'A\B\Foo is internal to A\B',
            ],
            'psalmInternalPropertyGet' => [
                'code' => '<?php
                    namespace A\B {
                        class Foo {
                            /**
                             * @psalm-internal A\B
                             * @var ?int
                             */
                            public $foo;
                        }
                    }

                    namespace A\C {
                        class Bat {
                            public function batBat() : void {
                                echo (new \A\B\Foo)->foo;
                            }
                        }
                    }',
                'error_message' => 'A\B\Foo::$foo is internal to A\B',
            ],
            'psalmInternalPropertySet' => [
                'code' => '<?php
                    namespace A\B {
                        class Foo {
                            /**
                             * @psalm-internal A\B
                             * @var ?int
                             */
                            public $foo;
                        }
                    }
                    namespace A\C {
                        class Bat {
                            public function batBat() : void {
                                $a = new \A\B\Foo;
                                $a->foo = 5;
                            }
                        }
                    }',
                'error_message' => 'A\B\Foo::$foo is internal to A\B',
            ],
            'psalmInternalClassMissingNamespace' => [
                    'code' => '<?php

                    /**
                      * @internal
                      * @psalm-internal
                      */
                    class Foo {}

                    ',
                    'error_message' => 'psalm-internal annotation used without specifying namespace',
            ],
            'psalmInternalPropertyMissingNamespace' => [
                'code' => '<?php
                    class Foo {
                        /**
                          * @var int
                          * @internal
                          * @psalm-internal
                          */
                        var $bar;
                    }
                    ',
                'error_message' => 'psalm-internal annotation used without specifying namespace',
            ],
            'psalmInternalMethodMissingNamespace' => [
                'code' => '<?php
                    class Foo {
                        /**
                         * @psalm-internal
                         */
                        function Bar(): void {}
                    }

                    ',
                'error_message' => 'psalm-internal annotation used without specifying namespace',
            ],
            'internalConstructor' => [
                'code' => '<?php
                    namespace A {
                        class C {
                            /** @internal */
                            public function __construct() {}
                        }
                    }
                    namespace B {
                        use A\C;
                        new C;
                    }
                ',
                'error_message' => 'InternalMethod',
            ],
        ];
    }
}
