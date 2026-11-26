<?php

declare(strict_types=1);

namespace Psalm\Tests\Shards;

use Override;
use Psalm\Tests\ArrayFunctionCallTest;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

/**
 * Shard 2 of the cases of ArrayFunctionCallTest (see ProviderShard).
 */
final class ArrayFunctionCallCases2Test extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return ProviderShard::of(ArrayFunctionCallTest::providerValidCodeParse(), 1, ArrayFunctionCallTest::SHARDS);
    }

    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return ProviderShard::of(ArrayFunctionCallTest::providerInvalidCodeParse(), 1, ArrayFunctionCallTest::SHARDS);
    }
}
