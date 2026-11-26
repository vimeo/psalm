<?php

declare(strict_types=1);

namespace Psalm\Tests\Shards;

use Override;
use Psalm\Tests\AnnotationTest;

/**
 * Shard 1 of the cases of AnnotationTest (see ProviderShard).
 */
final class AnnotationCases1Test extends UnusedVariablesReportingTestCase
{
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return ProviderShard::of(AnnotationTest::providerValidCodeParse(), 0, AnnotationTest::SHARDS);
    }

    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return ProviderShard::of(AnnotationTest::providerInvalidCodeParse(), 0, AnnotationTest::SHARDS);
    }
}
