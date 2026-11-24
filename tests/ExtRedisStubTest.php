<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class ExtRedisStubTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'keys returns a list of strings' => [
                'code' => <<<'PHP'
                    <?php
                    function f(Redis $redis): void {
                        $keys = $redis->keys('user:*');
                        /** @psalm-check-type-exact $keys = Redis|false|list<string> */
                    }
                    PHP,
            ],
            'hKeys returns a list of strings' => [
                'code' => <<<'PHP'
                    <?php
                    function f(Redis $redis): void {
                        $fields = $redis->hKeys('user:1');
                        /** @psalm-check-type-exact $fields = Redis|false|list<string> */
                    }
                    PHP,
            ],
            'hVals returns a list' => [
                'code' => <<<'PHP'
                    <?php
                    function f(Redis $redis): void {
                        $values = $redis->hVals('user:1');
                        /** @psalm-check-type-exact $values = Redis|false|list<mixed> */
                    }
                    PHP,
            ],
            'auth accepts a password or ACL credentials' => [
                'code' => <<<'PHP'
                    <?php
                    function f(Redis $redis): bool {
                        return $redis->auth('password')
                            && $redis->auth(['password'])
                            && $redis->auth(['user', 'password'])
                            && $redis->auth(['pass' => 'password'])
                            && $redis->auth(['user' => 'user', 'pass' => 'password']);
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'RedisCluster accepts a password or ACL credentials' => [
                'code' => <<<'PHP'
                    <?php
                    new RedisCluster(null, ['localhost:7000'], 1, 1, false, null);
                    new RedisCluster(null, ['localhost:7000'], 1, 1, false, 'password');
                    new RedisCluster(null, ['localhost:7000'], 1, 1, false, ['user', 'password']);
                    new RedisCluster(null, ['localhost:7000'], 1, 1, false, ['user' => 'user', 'pass' => 'password']);
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'RedisException and RedisClusterException are RuntimeExceptions' => [
                'code' => <<<'PHP'
                    <?php
                    function f(RedisException $e): RuntimeException {
                        return $e;
                    }
                    function g(RedisClusterException $e): RuntimeException {
                        return $e;
                    }
                    PHP,
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return [
            'RedisCluster rejects a third positional credential' => [
                'code' => <<<'PHP'
                    <?php
                    new RedisCluster(null, ['localhost:7000'], 1, 1, false, ['user', 'password', 'extra']);
                    PHP,
                'error_message' => 'InvalidArgument',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
        ];
    }
}
