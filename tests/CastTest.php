<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class CastTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{code:string,assertions?:array<string,string>,ignored_issues?:list<string>,php_version?:string}>
     */
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
    }
}
