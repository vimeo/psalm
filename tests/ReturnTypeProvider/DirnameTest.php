<?php

namespace Psalm\Tests\ReturnTypeProvider;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function addslashes;

use const DIRECTORY_SEPARATOR;

class DirnameTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        $input = 'a' . DIRECTORY_SEPARATOR . 'b' . DIRECTORY_SEPARATOR . 'c';

        yield 'dirnameOfLiteralStringPathReturnsLiteralString' => [
            'code' => '<?php
                $dir = dirname("' . $input . '");
            ',
            'assertions' => [
                '$dir===' => "'a" . addslashes(DIRECTORY_SEPARATOR) . "b'",
            ],
        ];

        yield 'dirnameOfStringPathReturnsString' => [
            'code' => '<?php
                $dir = dirname(implode("", range("a", "c")));
            ',
            'assertions' => [
                '$dir===' => 'string',
            ],
        ];

        yield 'dirnameOfIntLevelReturnsString' => [
            'code' => '<?php
                $dir = dirname("' . $input . '", 0);
            ',
            'assertions' => [
                '$dir===' => 'string',
            ],
        ];
    }
}
