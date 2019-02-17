<?php
namespace Psalm\Tests;

class DeprecatedAnnotationTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return array
     */
    public function providerValidCodeParse()
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
        ];
    }

    /**
     * @return array
     */
    public function providerInvalidCodeParse()
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
        ];
    }
}
