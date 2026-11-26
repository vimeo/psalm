<?php

declare(strict_types=1);

namespace Psalm\Tests\Shards;

use Override;
use Psalm\Tests\CapabilitiesTest;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

/**
 * Shard 2 of the cases of CapabilitiesTest (see ProviderShard).
 */
final class CapabilitiesCases2Test extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return ProviderShard::of(CapabilitiesTest::providerValidCodeParse(), 1, CapabilitiesTest::SHARDS);
    }

    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return ProviderShard::of(CapabilitiesTest::providerInvalidCodeParse(), 1, CapabilitiesTest::SHARDS);
    }
}
