<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class EncapsulatedStringTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    #[Override]
    public function providerValidCodeParse(): iterable
    {
        return [
            'correctlyIdentifiesNonEmptyStringFromExpr' => [
                'code' => '<?php
                    /**
                     * @param non-empty-string $x
                     * @return non-empty-string
                     */
                    function x(string $x): string
                    {
                        return "$x";
                    }
                ',
            ],
            'correctlyIdentifiesLiteralStringFromExpr' => [
                'code' => '<?php
                    /**
                     * @param "X" $x
                     * @param "Y" $y
                     * @return "Hello, X and Y!"
                     */
                    function hello(string $x, string $y): string
                    {
                        return "Hello, $x and $y!";
                    }
                ',
            ],
            'compositeStringAllLiteralPartsAreNonEmpty' => [
                'code' => '<?php
                    /** @return "Hello world!" */
                    function get_greeting(): string {
                        $name = "world";
                        return "Hello $name!";
                    }
                ',
            ],
            'compositeStringAllLiteralPartsCanBeEmpty' => [
                'code' => '<?php
                    /**
                     * @param ""|literal-string $name
                     * @return literal-string
                     */
                    function get_greeting_optional_name(string $name): string {
                        return "Hello $name";
                    }

                    /**
                     * @param ""|"user" $name_part
                     * @return non-empty-literal-string
                     */
                    function get_prefix_maybe(string $name_part, bool $flag): string {
                         $prefix = "";
                         if ($flag) {
                            $prefix = "prefix";
                        }

                        return "$prefix-$name_part";
                    }

                    /**
                     * @param ""|"A" $p1
                     * @param ""|"B" $p2
                     * @return literal-string
                     */
                    function combine_optional_parts(string $p1, string $p2): string {
                        return "$p1$p2";
                    }
                ',
            ],
            'compositeStringBoolInterpolated' => [
                'code' => '<?php
                    /** @return "T:1 F:" (literal) */
                    function get_string_with_bool(): string {
                        $t = true;
                        $f = false;

                        return "T:$t F:$f";
                    }
                ',
            ],
            'compositeStringNullInterpolated' => [
                'code' => '<?php
                    /** @return "Value: " (literal) */
                    function get_string_with_null(): string {
                        $val = null;
                        return "Value: $val";
                    }
                ',
            ],
            'compositeStringAllEmptyLiterals' => [
                'code' => '<?php
                    /** @return "" (literal) */
                    function get_empty_string_from_parts(): string {
                        $a = "";
                        $b = "";
                        return "$a$b";
                    }
                ',
            ],
            'compositeStringLiteralAndGeneralStringNonEmpty' => [
                'code' => '<?php
                    /**
                     * @param string $name
                     * @return non-empty-string
                     */
                    function greet(string $name): string {
                        return "Hello $name!";
                    }
                ',
            ],
            'compositeStringLiteralAndNonEmptyString' => [
                'code' => '<?php
                    /**
                     * @param non-empty-string $name
                     * @return non-empty-string
                     */
                    function greet_strong(string $name): string {
                        return "User: $name";
                    }
                ',
            ],
            'compositeStringLiteralZeroString' => [
                'code' => '<?php
                    /** @return "Count: 0" */
                    function get_count_zero_string(): string {
                        $countStr = "0";
                        return "Count: $countStr";
                    }
                ',
            ],
            'compositeStringWithArray' => [
                'code' => '<?php
                    /** @return "Array: Array" */
                    function get_string_with_array(): string {
                        $arr = [1, 2];
                        return "Array: $arr";
                    }
                ',
            ],
        ];
    }

    /**
     * @return array<string, array{code: string, error_message: string}>
     */
    #[Override]
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'compositeStringObjectNoToString' => [
                'code' => '<?php
                    class MySimpleClass {}

                    /** @return non-empty-string */
                    function get_string_with_object(MySimpleClass $obj): string {
                        return "Object: $obj";
                    }
                ',
                'error_message' => 'InvalidCast',
            ],
        ];
    }
}
