<?php

declare(strict_types=1);

namespace Psalm\Tests\Shards;

use Override;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;
use Psalm\Tests\TypeReconciliation\TypeAlgebraTest;

/**
 * Shard 3 of the cases of TypeAlgebraTest (see ProviderShard).
 */
final class TypeAlgebraCases3Test extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return ProviderShard::of(TypeAlgebraTest::providerValidCodeParse(), 2, TypeAlgebraTest::SHARDS);
    }

    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return ProviderShard::of(TypeAlgebraTest::providerInvalidCodeParse(), 2, TypeAlgebraTest::SHARDS);
    }
}
