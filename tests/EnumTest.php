<?php
namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;

class EnumTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
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

                    /** @psalm-param "foo\"with"|"bar"|1|2|3 $s */
                    function foo($s) : void {}
                    foo("foo\"with");
                    foo("bar");
                    foo(1);
                    foo(2);
                    foo(3);',
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
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
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
            'selfClassConstBadValue' => [
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
        ];
    }
}
