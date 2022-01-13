<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class DeprecatedAnnotationTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{code:string,assertions?:array<string,string>,ignored_issues?:array<string>}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'deprecatedMethod' => [
                'code' => '<?php
                    class Foo {
                        /**
                         * @deprecated
                         */
                        public static function barBar(): void {
                        }
                    }',
            ],
            'deprecatedClassUsedInsideClass' => [
                'code' => '<?php
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
                'code' => '<?php
                    /** @deprecated */
                    $a = "A";'
            ],
            'noNoticeOnInheritance' => [
                'code' => '<?php
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
                    'code' => '<?php

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
     * @return iterable<string,array{code:string,error_message:string,ignored_issues?:array<string>,php_version?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'deprecatedMethodWithCall' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    /**
                     * @deprecated
                     */
                    class Foo { }

                    $a = new Foo();',
                'error_message' => 'DeprecatedClass',
            ],
            'deprecatedClassWithExtends' => [
                'code' => '<?php
                    /**
                     * @deprecated
                     */
                    class Foo { }

                    class Bar extends Foo {}',
                'error_message' => 'DeprecatedClass',
            ],
            'deprecatedPropertyGet' => [
                'code' => '<?php
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
                'code' => '<?php
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
            'deprecatedPropertyGetFromInsideTheClass' => [
                'code' => '<?php
                    class A{
                        /**
                         * @deprecated
                         * @var ?int
                         */
                        public $foo;
                        public function bar(): void
                        {
                            echo $this->foo;
                        }
                    }
                ',
                'error_message' => 'DeprecatedProperty',
            ],
            'deprecatedPropertySetFromInsideTheClass' => [
                'code' => '<?php
                    class A{
                        /**
                         * @deprecated
                         * @var ?int
                         */
                        public $foo;
                        public function bar(int $p): void
                        {
                            $this->foo = $p;
                        }
                    }
                ',
                'error_message' => 'DeprecatedProperty',
            ],
            'deprecatedClassConstant' => [
                'code' => '<?php
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
                'code' => '<?php
                    /**
                     * @deprecated
                     */
                    class Foo {}

                    echo Foo::class;',
                'error_message' => 'DeprecatedClass',
            ],
            'deprecatedClassAsParam' => [
                'code' => '<?php
                    /**
                     * @deprecated
                     */
                    class DeprecatedClass{}

                    function foo(DeprecatedClass $deprecatedClass): void {}',
                'error_message' => 'DeprecatedClass',
            ],
            'deprecatedStaticPropertyFetch' => [
                'code' => '<?php

                    class Bar
                    {
                        /**
                         * @deprecated
                         */
                        public static bool $deprecatedPropery = false;
                    }

                    Bar::$deprecatedPropery;
                    ',
                'error_message' => 'DeprecatedProperty',
            ],
            'deprecatedEnumCaseFetch' => [
                'code' => '<?php
                    enum Foo {
                        case A;

                        /** @deprecated */
                        case B;
                    }

                    Foo::B;
                ',
                'error_message' => 'DeprecatedConstant',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ]
        ];
    }
}
