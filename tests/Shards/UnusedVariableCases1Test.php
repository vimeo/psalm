<?php

declare(strict_types=1);

namespace Psalm\Tests\Shards;

use Override;
use Psalm\Tests\UnusedVariableTest;

/**
 * Shard 1 of the cases of UnusedVariableTest (see ProviderShard).
 */
final class UnusedVariableCases1Test extends UnusedVariablesReportingTestCase
{
    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return ProviderShard::of(UnusedVariableTest::providerValidCodeParse(), 0, UnusedVariableTest::SHARDS);
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return ProviderShard::of(UnusedVariableTest::providerInvalidCodeParse(), 0, UnusedVariableTest::SHARDS);
    }
}
