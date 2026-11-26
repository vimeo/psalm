<?php

declare(strict_types=1);

namespace Psalm\Tests\Shards;

use Override;
use Psalm\Tests\Template\FunctionTemplateTest;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

/**
 * Shard 2 of the cases of FunctionTemplateTest (see ProviderShard).
 */
final class FunctionTemplateCases2Test extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return ProviderShard::of(FunctionTemplateTest::providerValidCodeParse(), 1, FunctionTemplateTest::SHARDS);
    }

    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return ProviderShard::of(FunctionTemplateTest::providerInvalidCodeParse(), 1, FunctionTemplateTest::SHARDS);
    }
}
