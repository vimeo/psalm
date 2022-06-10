<?php

namespace Psalm\Tests\Report\PrettyPrintArray;

use Generator;
use Psalm\Report\PrettyPrintArray\PrettyMatchTokens;
use Psalm\Tests\TestCase;

class PrettyMatchTokensTest extends TestCase
{
    /**
     * @dataProvider payload
     * @param string[] $expected
     */
    public function testTokenizePayload(string $payload, array $expected): void
    {
        $prettyMatchTokens = new PrettyMatchTokens();

        $prettyMatchTokens->tokenize($payload);
        $actual = $prettyMatchTokens->getMatchedTokens();

        $this->assertSame($expected, $actual);
    }

    /**
     * @return Generator<int,array{string,string[]}>
     */
    public function payload(): Generator
    {
        yield [
            'psalm-ke',
            ['psalm-ke']
        ];

        yield [
            'array{psalm-key, array{_id: string, activeFrom: string}}',
            ['array', '{', 'psalm-key', ',', ' ', 'array', '{','_id',':',' ','string',',',' ','activeFrom',':',' ','string','}','}']
        ];

        yield [
            'psalm-keypsalm-key',
            ['psalm-key','psalm-key',]
        ];

        yield [
            '{{',
            ['{','{',]
        ];

        yield [
            ' ,psalm-key{}:',
            [' ', ',', 'psalm-key', '{','}', ':']
        ];

        yield [
            'psalm-key',
            ['psalm-key',]
        ];
    }
}
