<?php
namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;

class EnumTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return array
     */
    public function providerValidCodeParse()
    {
        return [
            'enumStringOrEnumIntCorrect' => [
                '<?php
                    namespace Ns;

                    /** @psalm-param ( "foo\"with" | "bar" | 1 | 2 | 3 ) $s */
                    function foo($s) : void {}
                    foo("foo\"with");
                    foo("bar");
                    foo(1);
                    foo(2);
                    foo(3);',
            ],
            'enumStringOrEnumIntWithoutSpacesCorrect' => [
                '<?php
                    namespace Ns;

                    /** @psalm-param "foo\"with"|"bar"|1|2|3|4.0|4.1 $s */
                    function foo($s) : void {}
                    foo("foo\"with");
                    foo("bar");
                    foo(1);
                    foo(2);
                    foo(3);
                    foo(4.0);
                    foo(4.1);',
            ],
            'noRedundantConditionWithSwitch' => [
                '<?php
                    namespace Ns;

                    /**
                     * @psalm-param ( "foo" | "bar") $s
                     */
                    function foo(string $s) : void {
                        switch ($s) {
                          case "foo":
                            break;
                          case "bar":
                            break;
                        }
                    }',
            ],
            'classConstantCorrect' => [
                '<?php
                    namespace Ns;

                    class C {
                        const A1 = "bat";
                        const B = "baz";
                    }
                    /** @psalm-param "foo"|"bar"|C::A1|C::B $s */
                    function foo($s) : void {}
                    foo("foo");
                    foo("bar");
                    foo("bat");
                    foo("baz");',
            ],
            'selfClassConstGoodValue' => [
                '<?php
                    class A {
                        const FOO = "foo";
                        const BAR = "bar";

                        /**
                         * @param (self::FOO | self::BAR) $s
                         */
                        public static function foo(string $s) : void {}
                    }

                    A::foo("foo");',
            ],
            'classConstants' => [
                '<?php
                    namespace NS {
                        use OtherNS\C as E;
                        class C {}
                        class D {};
                        /** @psalm-param C::class|D::class|E::class $s */
                        function foo(string $s) : void {}
                        foo(C::class);
                        foo(D::class);
                        foo(E::class);
                        foo(\OtherNS\C::class);
                    }

                    namespace OtherNS {
                        class C {}
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
            'enumStringOrEnumIntIncorrectString' => [
                '<?php
                    namespace Ns;

                    /** @psalm-param ( "foo" | "bar" | 1 | 2 | 3 ) $s */
                    function foo($s) : void {}
                    foo("bat");',
                'error_message' => 'InvalidArgument',
            ],
            'enumStringOrEnumIntIncorrectInt' => [
                '<?php
                    namespace Ns;

                    /** @psalm-param ( "foo" | "bar" | 1 | 2 | 3 ) $s */
                    function foo($s) : void {}
                    foo(4);',
                'error_message' => 'InvalidArgument',
            ],
            'enumStringOrEnumIntWithoutSpacesIncorrect' => [
                '<?php
                    namespace Ns;

                    /** @psalm-param "foo\"with"|"bar"|1|2|3 $s */
                    function foo($s) : void {}
                    foo(4);',
                'error_message' => 'InvalidArgument',
            ],
            'enumWrongFloat' => [
                '<?php
                    namespace Ns;

                    /** @psalm-param 1.2|3.4|5.6 $s */
                    function foo($s) : void {}
                    foo(7.8);',
                'error_message' => 'InvalidArgument',
            ],
            'classConstantIncorrect' => [
                '<?php
                    namespace Ns;

                    class C {
                        const A = "bat";
                        const B = "baz";
                    }
                    /** @psalm-param "foo"|"bar"|C::A|C::B $s */
                    function foo($s) : void {}
                    foo("for");',
                'error_message' => 'InvalidArgument',
            ],
            'classConstantNoClass' => [
                '<?php
                    namespace Ns;

                    /** @psalm-param "foo"|"bar"|C::A|C::B $s */
                    function foo($s) : void {}',
                'error_message' => 'UndefinedClass',
            ],
            'selfClassConstBadValue' => [
                '<?php
                    class A {
                        const FOO = "foo";
                        const BAR = "bar";

                        /**
                         * @param (self::FOO | self::BAR) $s
                         */
                        public static function foo(string $s) : void {}
                    }

                    A::foo("for");',
                'error_message' => 'InvalidArgument',
            ],
            'selfClassConstBadConst' => [
                '<?php
                    class A {
                        const FOO = "foo";
                        const BAR = "bar";

                        /**
                         * @param (self::1FOO | self::BAR) $s
                         */
                        public static function foo(string $s) : void {}
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'classConstantInvalidValue' => [
                '<?php
                    namespace NS {
                        use OtherNS\C as E;
                        class C {}
                        class D {};
                        class F {};
                        /** @psalm-param C::class|D::class|E::class $s */
                        function foo(string $s) : void {}
                        foo(F::class);
                    }

                    namespace OtherNS {
                        class C {}
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'nonExistentConstantClass' => [
                '<?php
                    /**
                     * @return Foo::HELLO|5
                     */
                    function getVal()
                    {
                        return 5;
                    }',
                'error_message' => 'UndefinedClass',
            ],
            'nonExistentClassConstant' => [
                '<?php
                    class Foo {}
                    /**
                     * @return Foo::HELLO|5
                     */
                    function getVal()
                    {
                        return 5;
                    }',
                'error_message' => 'UndefinedConstant',
            ]
        ];
    }
}
