<?php

declare(strict_types=1);

namespace Psalm\Tests\Shards;

use Override;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;
use Psalm\Tests\TypeReconciliation\ConditionalTest;

/**
 * Shard 1 of the cases of ConditionalTest (see ProviderShard).
 */
final class ConditionalCases1Test extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return ProviderShard::of(ConditionalTest::providerValidCodeParse(), 0, ConditionalTest::SHARDS);
    }

    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return ProviderShard::of(ConditionalTest::providerInvalidCodeParse(), 0, ConditionalTest::SHARDS);
    }
}
