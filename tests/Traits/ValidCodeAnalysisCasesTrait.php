<?php

declare(strict_types=1);

namespace Psalm\Tests\Traits;

/**
 * The valid cases of a test class, apart from the tests that run them (ValidCodeAnalysisTestTrait).
 *
 * A class whose cases other classes run (see Psalm\Tests\Shards) holds just this.
 *
 * @psalm-mutable
 */
trait ValidCodeAnalysisCasesTrait
{
    /**
     * @return iterable<
     *     string,
     *     array{
     *         code: string,
     *         assertions?: array<string, string>,
     *         ignored_issues?: list<string>,
     *         php_version?: string,
     *     }
     * >
     */
    abstract public static function providerValidCodeParse(): iterable;
}
