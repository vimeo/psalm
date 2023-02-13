<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

/**
 * tests for the handling of code using the PHP FFI extension
 *
 * https://www.php.net/manual/en/book.ffi.php
 */
class FFITest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
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
}
