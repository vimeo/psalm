<?php

declare(strict_types=1);

namespace Psalm\Tests\Shards;

use Override;
use Psalm\Tests\FunctionCallTest;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

/**
 * Shard 3 of the cases of FunctionCallTest (see ProviderShard).
 */
final class FunctionCallCases3Test extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return ProviderShard::of(FunctionCallTest::providerValidCodeParse(), 2, FunctionCallTest::SHARDS);
    }

    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return ProviderShard::of(FunctionCallTest::providerInvalidCodeParse(), 2, FunctionCallTest::SHARDS);
    }
}
