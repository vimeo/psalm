<?php

namespace Psalm\Tests\ReturnTypeProvider;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

class BasenameTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        $input = 'a' . DIRECTORY_SEPARATOR . 'b' . DIRECTORY_SEPARATOR . 'c';

        yield 'basenameOfLiteralStringPathReturnsLiteralString' => [
            'code' => '<?php
                $base = basename("' . $input . '");
            ',
            'assertions' => [
                '$base===' => "'c'",
            ],
        ];

        yield 'basenameOfStringPathReturnsString' => [
            'code' => '<?php
                $base = basename(implode("", range("a", "c")));
            ',
            'assertions' => [
                '$base===' => 'string',
            ],
        ];

        yield 'basenameOfStringPathReturnsNonEmptyString' => [
            'code' => '<?php
                $foo = rand(0, 1) ? "0" : "world";
                $base = basename($foo);
            ',
            'assertions' => [
                '$base===' => 'non-empty-string',
            ],
        ];

        yield 'basenameOfStringPathReturnsNonFalsyString' => [
            'code' => '<?php
                $foo = rand(0, 1) ? "hello" : "world";
                $base = basename($foo);
            ',
            'assertions' => [
                '$base===' => 'non-falsy-string',
            ],
        ];
    }
}
