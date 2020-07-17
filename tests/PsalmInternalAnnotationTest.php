<?php
namespace Psalm\Tests;

class PsalmInternalAnnotationTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
    {
        return [
            'internalMethodWithCall' => [
                '<?php
                    namespace A\B {
                        class Foo {
                            /**
                             * @internal
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
                             * @internal
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
            'internalToClassMethodWithCall' => [
                '<?php
                    namespace A\B {
                        class Foo {
                            /**
                             * @internal
                             * @psalm-internal A\B\Foo
                             */
                            public static function barBar(): void {
                            }

                            public static function foo(): void {
                                self::barBar();
                            }
                        }
                    }',
            ],
            'internalClassWithStaticCall' => [
                '<?php
                    namespace A\B {
                        /**
                         * @internal
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
            'internalClassWithInstanceCall' => [
                '<?php
                    namespace A\B {
                        /**
                         * @internal
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
            'internalClassWithPropertyFetch' => [
                '<?php
                    namespace A\B {
                        /**
                         * @internal
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
            'internalClassExtendingNamespaceWithStaticCall' => [
                '<?php
                    namespace A {
                        /**
                         * @internal
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
            'internalClassWithNew' => [
                '<?php
                    namespace A\B {
                        /**
                         * @internal
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
            'internalClassWithInstanceOf' => [
                '<?php
                    namespace A\B {
                        interface Bar {};

                        /**
                         * @internal
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
            'internalClassWithExtends' => [
                '<?php
                    namespace A\B {
                        /**
                         * @internal
                         * @psalm-internal A\B
                         */
                        class Foo { }
                    }

                    namespace A\B\C {
                        class Bar extends \A\B\Foo {}
                    }',
            ],
            'internalPropertyGet' => [
                '<?php
                    namespace A\B {
                        class Foo {
                            /**
                             * @internal
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
            'internalPropertySet' => [
                '<?php
                    namespace A\B {
                        class Foo {
                            /**
                             * @internal
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
            'internalMethodInTraitWithCall' => [
                '<?php
                    namespace A {
                        /**
                         * @internal
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
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse()
    {
        return [
            'internalMethodWithCall' => [
                '<?php
                    namespace A\B {
                        class Foo {
                            /**
                             * @internal
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
                'error_message' => 'The method A\B\Foo::barBar has been marked as internal to A\B',
            ],
            'internalToClassMethodWithCall' => [
                '<?php
                    namespace A\B {
                        class Foo {
                            /**
                             * @internal
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
                'error_message' => 'The method A\B\Foo::barBar has been marked as internal to A\B\Foo',
            ],
            'internalClassWithStaticCall' => [
                '<?php
                    namespace A\B {
                        /**
                         * @internal
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
            'internalClassWithPropertyFetch' => [
                '<?php
                    namespace A\B {
                        /**
                         * @internal
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
                'error_message' => 'A\B\Foo::$barBar is marked internal to A\B',
            ],
            'internalClassWithInstanceCall' => [
                '<?php
                    namespace A\B {
                        /**
                         * @internal
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
                'error_message' => 'The method A\B\Foo::barBar has been marked as internal to A\B',
            ],
            'internalClassWithNew' => [
                '<?php
                    namespace A\B {
                        /**
                         * @internal
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
            'internalClassWithInstanceOf' => [
                '<?php
                    namespace A\B {
                        interface Bar {};

                        /**
                         * @internal
                         * @psalm-internal A\B
                         */
                        class Foo { }
                    }

                    namespace A\C {
                        class Bat {
                            public function batBat(\A\B\Bar $bar) : void {
                                $bar instanceOf \A\B\Foo;
                            }
                        }
                    }',
                'error_message' => 'A\B\Foo is internal to A\B',
            ],
            'internalClassWithExtends' => [
                '<?php
                    namespace A\B {
                        /**
                         * @internal
                         * @psalm-internal A\B
                         */
                        class Foo { }
                    }

                    namespace A\C {
                        class Bar extends \A\B\Foo {}
                    }',
                'error_message' => 'A\B\Foo is internal to A\B',
            ],
            'internalPropertyGet' => [
                '<?php
                    namespace A\B {
                        class Foo {
                            /**
                             * @internal
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
                'error_message' => 'A\B\Foo::$foo is marked internal to A\B',
            ],
            'internalPropertySet' => [
                '<?php
                    namespace A\B {
                        class Foo {
                            /**
                             * @internal
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
                'error_message' => 'A\B\Foo::$foo is marked internal to A\B',
            ],
            'internalClassMissingNamespace' => [
                    '<?php

                    /**
                      * @internal
                      * @psalm-internal
                      */
                    class Foo {}

                    ',
                    'error_message' => 'psalm-internal annotation used without specifying namespace',
            ],
            'internalPropertyMissingNamespace' => [
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
            'internalMethodMissingNamespace' => [
                '<?php
                    class Foo {
                        /**
                         * @internal
                         * @psalm-internal
                         */
                        function Bar(): void {}
                    }

                    ',
                'error_message' => 'psalm-internal annotation used without specifying namespace',
            ],
            'internalClassMissingInternalAnnotation' => [
                '<?php
                    namespace A\B {
                        /**
                         * @psalm-internal A\B
                         */
                        class Foo { }
                    }
                    ',
                'error_message' => 'psalm-internal annotation used without @internal',
                ],
            'internalPropertyMissingInternalAnnotation' => [
                '<?php
                    namespace A\B {
                        class Foo {
                            /**
                             * @var int
                             * @psalm-internal A\B
                             */
                             public $foo;
                        }
                    }
                    ',
                'error_message' => 'psalm-internal annotation used without @internal',
                ],
            'internalFunctionMissingInternalAnnotation' => [
                '<?php
                    namespace A\B {
                        class Foo {
                            /**
                             * @psalm-internal A\B
                             */
                             public function foo()
                             {
                             }
                        }
                    }
                    ',
                'error_message' => 'psalm-internal annotation used without @internal',
            ],
        ];
    }
}
