<?php

declare(strict_types=1);

namespace Psalm\Tests\Shards;

use Override;
use Psalm\Tests\CallableTest;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

/**
 * Shard 2 of the cases of CallableTest (see ProviderShard).
 */
final class CallableCases2Test extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return ProviderShard::of(CallableTest::providerValidCodeParse(), 1, CallableTest::SHARDS);
    }

    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return ProviderShard::of(CallableTest::providerInvalidCodeParse(), 1, CallableTest::SHARDS);
    }
}
