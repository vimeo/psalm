<?php
namespace Psalm\Tests;

class DeprecatedAnnotationTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'deprecatedMethod' => [
                '<?php
                    class Foo {
                        /**
                         * @deprecated
                         */
                        public static function barBar(): void {
                        }
                    }',
            ],
            'deprecatedClassUsedInsideClass' => [
                '<?php
                    /**
                     * @deprecated
                     */
                    class Foo {
                        public static function barBar(): void {
                            new Foo();
                        }
                    }',
            ],
            'annotationOnStatement' => [
                '<?php
                    /** @deprecated */
                    $a = "A";'
            ],
            'noNoticeOnInheritance' => [
                '<?php
                    /**
                     * @deprecated
                     */
                    class Foo {}

                    interface Iface {
                        /**
                         * @psalm-suppress DeprecatedClass
                         * @return Foo[]
                         */
                        public function getFoos();

                        /**
                         * @psalm-suppress DeprecatedClass
                         * @return Foo[]
                         */
                        public function getDifferentFoos();
                    }

                    class Impl implements Iface {
                        public function getFoos(): array {
                            return [];
                        }

                        public function getDifferentFoos() {
                            return [];
                        }
                    }'
            ],
            'suppressDeprecatedClassOnMember' => [
                    '<?php

                        /**
                         * @deprecated
                         */
                        class TheDeprecatedClass {}

                        /**
                         * @psalm-suppress MissingConstructor
                         */
                        class A {
                            /**
                             * @psalm-suppress DeprecatedClass
                             * @var TheDeprecatedClass
                             */
                            public $property;
                        }
                ']
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'deprecatedMethodWithCall' => [
                '<?php
                    class Foo {
                        /**
                         * @deprecated
                         */
                        public static function barBar(): void {
                        }
                    }

                    Foo::barBar();',
                'error_message' => 'DeprecatedMethod',
            ],
            'deprecatedClassWithStaticCall' => [
                '<?php
                    /**
                     * @deprecated
                     */
                    class Foo {
                        public static function barBar(): void {
                        }
                    }

                    Foo::barBar();',
                'error_message' => 'DeprecatedClass',
            ],
            'deprecatedClassWithNew' => [
                '<?php
                    /**
                     * @deprecated
                     */
                    class Foo { }

                    $a = new Foo();',
                'error_message' => 'DeprecatedClass',
            ],
            'deprecatedClassWithExtends' => [
                '<?php
                    /**
                     * @deprecated
                     */
                    class Foo { }

                    class Bar extends Foo {}',
                'error_message' => 'DeprecatedClass',
            ],
            'deprecatedPropertyGet' => [
                '<?php
                    class A{
                        /**
                         * @deprecated
                         * @var ?int
                         */
                        public $foo;
                    }
                    echo (new A)->foo;',
                'error_message' => 'DeprecatedProperty',
            ],
            'deprecatedPropertySet' => [
                '<?php
                    class A{
                        /**
                         * @deprecated
                         * @var ?int
                         */
                        public $foo;
                    }
                    $a = new A;
                    $a->foo = 5;',
                'error_message' => 'DeprecatedProperty',
            ],
            'deprecatedClassConstant' => [
                '<?php
                    /**
                     * @deprecated
                     */
                    class Foo {
                        public const FOO = 5;
                    }

                    echo Foo::FOO;',
                'error_message' => 'DeprecatedClass',
            ],
            'deprecatedClassStringConstant' => [
                '<?php
                    /**
                     * @deprecated
                     */
                    class Foo {}

                    echo Foo::class;',
                'error_message' => 'DeprecatedClass',
            ],
            'deprecatedClassAsParam' => [
                '<?php
                    /**
                     * @deprecated
                     */
                    class DeprecatedClass{}

                    function foo(DeprecatedClass $deprecatedClass): void {}',
                'error_message' => 'DeprecatedClass',
            ],
        ];
    }
}
