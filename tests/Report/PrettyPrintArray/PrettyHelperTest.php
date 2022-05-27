<?php

namespace Psalm\Tests\Report\PrettyPrintArray;

use Psalm\Report\PrettyPrintArray\PrettyHelper;
use Psalm\Tests\TestCase;

class PrettyHelperTest extends TestCase
{
    public function testNormalizeBracket(): void
    {
        $payload = '< > aa bb cc';
        $actual = PrettyHelper::normalizeBracket($payload);

        $this->assertSame('{ } aa bb cc', $actual);
    }

    public function testNormalizeTokens(): void
    {
        $payload = 'array-key';
        $actual = PrettyHelper::normalizeTokens($payload);

        $this->assertSame('psalm-key', $actual);
    }

    public function testRevertNormalizedTokens(): void
    {
        $payload = 'psalm-key';
        $actual = PrettyHelper::revertNormalizedTokens($payload);

        $this->assertSame('array-key', $actual);
    }
}
