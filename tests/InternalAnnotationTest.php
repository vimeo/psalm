<?php
namespace Psalm\Tests;

class InternalAnnotationTest extends TestCase
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
                'error_message' => 'A\B\Foo::$barBar is marked internal',
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
        ];
    }
}
