<?php

declare(strict_types=1);

namespace Psalm\Tests\Shards;

use Override;
use Psalm\Tests\PropertyTypeTest;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

/**
 * Shard 1 of the cases of PropertyTypeTest (see ProviderShard).
 */
final class PropertyTypeCases1Test extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return ProviderShard::of(PropertyTypeTest::providerValidCodeParse(), 0, PropertyTypeTest::SHARDS);
    }

    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return ProviderShard::of(PropertyTypeTest::providerInvalidCodeParse(), 0, PropertyTypeTest::SHARDS);
    }
}
