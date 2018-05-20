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
                    /** @psalm-param "foo\"with"|"bar"|1|2|3 $s */
                    function foo($s) : void {}
                    foo("foo\"with");
                    foo("bar");
                    foo(1);
                    foo(2);
                    foo(3);',
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
                    /** @psalm-param ( "foo" | "bar" | 1 | 2 | 3 ) $s */
                    function foo($s) : void {}
                    foo("bat");',
                'error_message' => 'InvalidArgument',
            ],
            'enumStringOrEnumIntIncorrectInt' => [
                '<?php
                    /** @psalm-param ( "foo" | "bar" | 1 | 2 | 3 ) $s */
                    function foo($s) : void {}
                    foo(4);',
                'error_message' => 'InvalidArgument',
            ],
            'enumStringOrEnumIntWithoutSpacesIncorrect' => [
                '<?php
                    /** @psalm-param "foo\"with"|"bar"|1|2|3 $s */
                    function foo($s) : void {}
                    foo(4);',
                'error_message' => 'InvalidArgument',
            ],
        ];
    }
}
