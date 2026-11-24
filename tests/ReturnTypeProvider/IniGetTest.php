<?php

declare(strict_types=1);

namespace Psalm\Tests\ReturnTypeProvider;

use Override;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class IniGetTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        yield 'coreSettingIsNeverFalse' => [
            'code' => '<?php
                final class MemoryLimit {
                    private static ?string $boot = null;

                    public static function boot(): string {
                        return self::$boot ??= ini_get("memory_limit");
                    }
                }

                $timeout = ini_get("max_execution_time");
            ',
            'assertions' => [
                '$timeout===' => 'string',
            ],
        ];

        yield 'eitherOfTwoCoreSettings' => [
            'code' => '<?php
                $value = ini_get(rand(0, 1) ? "precision" : "date.timezone");
            ',
            'assertions' => [
                '$value===' => 'string',
            ],
        ];

        yield 'unknownSettingCanBeFalse' => [
            'code' => '<?php
                $value = ini_get("xdebug.mode");
            ',
            'assertions' => [
                '$value===' => 'false|string',
            ],
        ];

        yield 'coreAndUnknownSettingCanBeFalse' => [
            'code' => '<?php
                $value = ini_get(rand(0, 1) ? "memory_limit" : "xdebug.mode");
            ',
            'assertions' => [
                '$value===' => 'false|string',
            ],
        ];

        yield 'maxMemoryLimitIsACoreSettingSincePhp85' => [
            'code' => '<?php
                $value = ini_get("max_memory_limit");
            ',
            'assertions' => [
                '$value===' => 'string',
            ],
            'ignored_issues' => [],
            'php_version' => '8.5',
        ];

        yield 'maxMemoryLimitBeforePhp85CanBeFalse' => [
            'code' => '<?php
                $value = ini_get("max_memory_limit");
            ',
            'assertions' => [
                '$value===' => 'false|string',
            ],
            'ignored_issues' => [],
            'php_version' => '8.4',
        ];
    }
}
