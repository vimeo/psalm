<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class CastTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    #[Override]
    public function providerValidCodeParse(): iterable
    {
        yield 'SKIPPED-castFalseOrIntToInt' => [
            'code' => '<?php
                /** @var false|int<10, 20> */
                $intOrFalse = 10;
                $int = (int) $intOrFalse;
            ',
            'assertions' => [
                '$int===' => '0|int<10, 20>',
            ],
        ];
        yield 'SKIPPED-castTrueOrIntToInt' => [
            'code' => '<?php
                /** @var true|int<10, 20> */
                $intOrTrue = 10;
                $int = (int) $intOrTrue;
            ',
            'assertions' => [
                '$int===' => '1|int<10, 20>',
            ],
        ];
        yield 'SKIPPED-castBoolOrIntToInt' => [
            'code' => '<?php
                /** @var bool|int<10, 20> */
                $intOrBool = 10;
                $int = (int) $intOrBool;
            ',
            'assertions' => [
                '$int===' => '0|1|int<10, 20>',
            ],
        ];
        yield 'castObjectWithPropertiesToArray' => [
            'code' => '<?php
                /** @var object{a:int,b:string} $o */
                $a = (array) $o;
            ',
            'assertions' => [
                '$a===' => 'array{a: int, b: string, ...<array-key, mixed>}',
            ],
        ];
        yield 'castIntRangeToString' => [
            'code' => '<?php
                /** @var int<-5, 3> */
                $int_range = 2;
                $string = (string) $int_range;
            ',
            'assertions' => [
                '$string===' => "'-1'|'-2'|'-3'|'-4'|'-5'|'0'|'1'|'2'|'3'",
            ],
        ];
        yield 'castAnythingToVoid' => [
            'code' => '<?php
                /** @var int<-5, 3> */
                $int_range = 2;
                (void) $int_range;
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.5',
        ];
    }

    #[Override]
    public function providerInvalidCodeParse(): iterable
    {
        yield 'castAnythingToVoidNotYetSupported' => [
            'code' => '<?php
                /** @var int<-5, 3> */
                $int_range = 2;
                (void) $int_range;
            ',
            // 'error_message' => 'The (void) cast is only supported from PHP 8.5 and causes a fatal error in earlier versions',
            // if not just the config php version but the PHP parser/executable is <8.5, it will result in a ParseError
            'error_message' => 'ParseError - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:24 - Syntax error, unexpected T_VARIABLE on line 4',
            'error_levels' => [],
            'php_version' => '8.4',
        ];
        yield 'castUnsetDeprecated' => [
            'code' => '<?php
                /** @var int<-5, 3> */
                $int_range = 2;
                (unset) $int_range;
            ',
            'error_message' => 'The (unset) cast is deprecated',
            'error_levels' => [],
            'php_version' => '7.4',
        ];
        yield 'castUnsetNotSupported' => [
            'code' => '<?php
                /** @var int<-5, 3> */
                $int_range = 2;
                (unset) $int_range;
            ',
            'error_message' => 'The (unset) cast is no longer supported',
            'error_levels' => [],
            'php_version' => '8.0',
        ];
    }
}
