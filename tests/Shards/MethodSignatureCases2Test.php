<?php

declare(strict_types=1);

namespace Psalm\Tests\Shards;

use Override;
use Psalm\Tests\MethodSignatureTest;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\InvalidCodeAnalysisWithIssuesTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

/**
 * Shard 2 of the cases of MethodSignatureTest (see ProviderShard).
 */
final class MethodSignatureCases2Test extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisWithIssuesTestTrait;
    use ValidCodeAnalysisTestTrait;

    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return ProviderShard::of(MethodSignatureTest::providerValidCodeParse(), 1, MethodSignatureTest::SHARDS);
    }

    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return ProviderShard::of(MethodSignatureTest::providerInvalidCodeParse(), 1, MethodSignatureTest::SHARDS);
    }
}
