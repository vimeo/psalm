<?php

declare(strict_types=1);

namespace Psalm\Tests\ReturnTypeProvider;

use Override;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function addslashes;

use const DIRECTORY_SEPARATOR;

final class DirnameTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-mutation-free
     */
    #[Override]
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

        yield 'dirnameOfIntLevelLiteralReturnsLiteral' => [
            'code' => '<?php
                $dir = dirname("' . $input . '", 10);
            ',
            'assertions' => [
                '$dir===' => "'.'",
            ],
        ];

        yield 'dirnameOfNonEmptyStringIntLevelOneReturnsNonEmptyString' => [
            'code' => '<?php
                $dir = dirname(uniqid() . "abc", 2);
            ',
            'assertions' => [
                '$dir===' => 'non-falsy-string',
            ],
        ];

        yield 'dirnameOfNonEmptyShouldBeNonFalsy' => [
            'code' => '<?php
                $foo = rand(0, 1) ? "0" : "world";
                $dir = dirname($foo, 20);
            ',
            'assertions' => [
                '$dir===' => 'non-falsy-string',
            ],
        ];

        yield 'dirnameOfEmptyShouldBeString' => [
            'code' => '<?php
                $foo = rand(0, 1) ? "" : "world";
                $dir = dirname($foo, 20);
            ',
            'assertions' => [
                '$dir===' => 'string',
            ],
        ];
    }
}
