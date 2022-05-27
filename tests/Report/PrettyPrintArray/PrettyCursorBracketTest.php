<?php

namespace Psalm\Tests\Report\PrettyPrintArray;

use Generator;
use Psalm\Report\PrettyPrintArray\PrettyCursorBracket;
use Psalm\Tests\TestCase;

use function str_split;

class PrettyCursorBracketTest extends TestCase
{
    /**
     * @dataProvider providerValidCases
     */
    public function testAccept(string $chars, bool $closed, int $numeroBrackets): void
    {
        $sut = new PrettyCursorBracket();

        foreach (str_split($chars) as $char) {
            $sut->accept($char);
        }

        $this->assertSame($closed, $sut->closed());
        $this->assertSame($numeroBrackets, $sut->getNumberBrackets());
    }

    /**
     * @return Generator<int, array{string, bool, int}>
     */
    public function providerValidCases(): Generator
    {
        yield ['',false,0];

        yield ['{',false,1];

        yield ['}',false,1];

        yield ['{}',true,0];

        yield ['{} {}',true,0];

        yield ['{{ }',false,1];

        yield ['{{{ }',false,2];

        yield ['{{{ }}',false,1];

        yield ['{{{ }}}',true,0];
    }
}
