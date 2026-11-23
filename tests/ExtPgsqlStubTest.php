<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class ExtPgsqlStubTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'PGSQL constants are defined' => [
                'code' => <<<'PHP'
                    <?php
                    function f(string $dsn): ?string {
                        $connection = pg_connect($dsn, PGSQL_CONNECT_FORCE_NEW);
                        if ($connection === false) {
                            return null;
                        }
                        $result = pg_query($connection, 'SELECT 1');
                        if ($result === false || pg_result_status($result) !== PGSQL_TUPLES_OK) {
                            return null;
                        }
                        pg_fetch_all($result, PGSQL_NUM);
                        $sqlstate = pg_result_error_field($result, PGSQL_DIAG_SQLSTATE);
                        /** @psalm-check-type-exact $sqlstate = false|null|string */
                        return $sqlstate === false ? null : $sqlstate;
                    }
                    $version = PGSQL_LIBPQ_VERSION;
                    PHP,
                'assertions' => [
                    '$version===' => 'non-empty-string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
        ];
    }
}
