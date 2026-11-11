<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

/**
 * tests for the handling of code using the PHP FFI extension
 *
 * https://www.php.net/manual/en/book.ffi.php
 */
final class FFITest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'memcmpAcceptsValuesAndVariables' => [
                'code' => '<?php
                    function getCData(): FFI\CData {
                        return new FFI\CData;
                    }

                    function cmp(): int {
                        $var = new FFI\CData;

                        return FFI::memcmp(getCData(), $var, 0) + FFI::memcmp($var, "abc", 3);
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'memcmpAcceptsReferencedExpressionsAndValues' => [
                'code' => '<?php
                    final class Holder {
                        public \\FFI\\CData $data;
                        public static \\FFI\\CData $shared;

                        /** @param array<int, \\FFI\\CData> $list */
                        public function cmp(array $list): int {
                            return FFI::memcmp($this->data, self::$shared, 1)
                                + FFI::memcmp($list[0], $list[1], 1)
                                + FFI::memcmp(new FFI\\CData, "abc", 3);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MissingConstructor'],
                'php_version' => '7.4',
            ],
            'memcmpAcceptsNamedArgumentsInAnyOrder' => [
                'code' => '<?php
                    function getCData(): FFI\\CData {
                        return new FFI\\CData;
                    }

                    function cmp(): int {
                        $var = new FFI\\CData;

                        return FFI::memcmp(size: 1, ptr2: $var, ptr1: getCData())
                            + FFI::memcmp(ptr2: "abc", ptr1: $var, size: 3);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'memcmpKeepsTheTypeOfItsVariableArguments' => [
                'code' => '<?php
                    $ptr = new FFI\\CData;
                    $name = "abc";

                    $result = FFI::memcmp($ptr, $name, 3);
                ',
                'assertions' => [
                    '$ptr===' => 'FFI\\CData',
                    '$name===' => "'abc'",
                ],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            // Examples are from https://www.php.net/manual/en/ffi.examples-basic.php
            // Some of them needed tweaks, e.g. due to unsafe use of var_dump().
            'Example #1 Calling a function from shared library' => [
                'code' => '<?php
                    $ffi = FFI::cdef(
                        "int printf(const char *format, ...);", // this is a regular C declaration
                        "libc.so.6"
                    );
                    $ffi->printf("Hello %s!\n", "world");
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'Example #3 Accessing existing C variables' => [
                'code' => '<?php
                    $ffi = FFI::cdef(
                        "int errno;", // this is a regular C declaration
                        "libc.so.6"
                    );
                    echo $ffi->errno;
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'Example #5 Working with C arrays' => [
                'code' => '<?php
                    // create C data structure
                    $a = FFI::new("long[1024]");
                    // work with it like with a regular PHP array
                    $size = count($a);
                    for ($i = 0; $i < $size; $i++) {
                        $a[$i] = $i;
                    }
                    $sum = 0;
                    /** @psalm-suppress MixedAssignment */
                    foreach ($a as $n) {
                        /** @psalm-suppress MixedOperand */
                        $sum += $n;
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'Example #6 Working with C enums' => [
                'code' => '<?php
                    $a = FFI::cdef(
                       "typedef enum _zend_ffi_symbol_kind {
                            ZEND_FFI_SYM_TYPE,
                            ZEND_FFI_SYM_CONST = 2,
                            ZEND_FFI_SYM_VAR,
                            ZEND_FFI_SYM_FUNC
                        } zend_ffi_symbol_kind;"
                    );
                    echo $a->ZEND_FFI_SYM_TYPE;
                    echo $a->ZEND_FFI_SYM_CONST;
                    echo $a->ZEND_FFI_SYM_VAR;
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return [
            'memcmpRejectsAnIntVariable' => [
                'code' => '<?php
                    function cmp(int $number): int {
                        return FFI::memcmp($number, "abc", 3);
                    }
                ',
                'error_message' => 'InvalidScalarArgument',
                'error_levels' => [],
                'php_version' => '7.4',
            ],
            'memcmpRejectsAnIntValue' => [
                'code' => '<?php
                    function number(): int {
                        return 1;
                    }

                    function cmp(): int {
                        return FFI::memcmp(number(), "abc", 3);
                    }
                ',
                'error_message' => 'InvalidScalarArgument',
                'error_levels' => [],
                'php_version' => '7.4',
            ],
        ];
    }
}
