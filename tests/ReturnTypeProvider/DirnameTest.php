<?php

namespace Psalm\Tests\ReturnTypeProvider;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class DirnameTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        yield 'dirnameReturnsLiteralString' => [
            '<?php
                $dir = dirname(__FILE__);
            ',
            'assertions' => [
                '$dir===' => 'literal-string',
            ],
        ];

        yield 'dirnameReturnsString' => [
            '<?php
                $dir = dirname(implode("", range("a", "c")));
            ',
            'assertions' => [
                '$dir===' => 'string',
            ],
        ];
    }
}
