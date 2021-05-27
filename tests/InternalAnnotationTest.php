<?php
namespace Psalm\Tests;

class InternalAnnotationTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'internalMethodWithCall' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
            'internalToClassMethodWithCallSameNamespace' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
            'psalmInternalPropertyGet' => [
                '<?php
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
                '<?php
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
            'psalmInternalMethodInTraitWithCall' => [
                '<?php
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
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'internalMethodWithCall' => [
                '<?php
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
                'error_message' => 'InternalMethod',
            ],
            'internalClassWithStaticCall' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                    '<?php

                    /**
                      * @internal
                      * @psalm-internal
                      */
                    class Foo {}

                    ',
                    'error_message' => 'psalm-internal annotation used without specifying namespace',
            ],
            'psalmInternalPropertyMissingNamespace' => [
                '<?php
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
                '<?php
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
                '<?php
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
