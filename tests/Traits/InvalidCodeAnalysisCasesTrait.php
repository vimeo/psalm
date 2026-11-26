<?php

declare(strict_types=1);

namespace Psalm\Tests\Traits;

/**
 * The invalid cases of a test class, apart from the tests that run them (InvalidCodeAnalysisTestTrait and
 * InvalidCodeAnalysisWithIssuesTestTrait).
 *
 * A class whose cases other classes run (see Psalm\Tests\Shards) holds just this.
 *
 * @psalm-type NamedArgumentsDataProviderArrayNotation = array{
 *     code: string,
 *     error_message: string,
 *     error_levels?: list<string>,
 *     php_version?: string
 * }
 * @psalm-mutable
 */
trait InvalidCodeAnalysisCasesTrait
{
    /**
     * @return iterable<string, NamedArgumentsDataProviderArrayNotation>
     */
    abstract public static function providerInvalidCodeParse(): iterable;
}
