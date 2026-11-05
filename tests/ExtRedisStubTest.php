<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class ExtRedisStubTest extends TestCase
{
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
}
