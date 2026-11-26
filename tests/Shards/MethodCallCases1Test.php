<?php

declare(strict_types=1);

namespace Psalm\Tests\Shards;

use Override;
use Psalm\Tests\MethodCallTest;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\InvalidCodeAnalysisWithIssuesTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

/**
 * Shard 1 of the cases of MethodCallTest (see ProviderShard).
 */
final class MethodCallCases1Test extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisWithIssuesTestTrait;
    use ValidCodeAnalysisTestTrait;

    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return ProviderShard::of(MethodCallTest::providerValidCodeParse(), 0, MethodCallTest::SHARDS);
    }

    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return ProviderShard::of(MethodCallTest::providerInvalidCodeParse(), 0, MethodCallTest::SHARDS);
    }
}
