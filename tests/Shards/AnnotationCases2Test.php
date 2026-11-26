<?php

declare(strict_types=1);

namespace Psalm\Tests\Shards;

use Override;
use Psalm\Tests\AnnotationTest;

/**
 * Shard 2 of the cases of AnnotationTest (see ProviderShard).
 */
final class AnnotationCases2Test extends UnusedVariablesReportingTestCase
{
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return ProviderShard::of(AnnotationTest::providerValidCodeParse(), 1, AnnotationTest::SHARDS);
    }

    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return ProviderShard::of(AnnotationTest::providerInvalidCodeParse(), 1, AnnotationTest::SHARDS);
    }
}
