<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class TraceTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return [
            'traceVariable' => [
                'code' => '<?php
                    /** @psalm-trace $a */
                    $a = getmypid();',
                'error_message' => 'Trace',
            ],
            'traceVariables' => [
                'code' => '<?php
                    /** @psalm-trace $a $b */
                    $a = getmypid();
                    $b = getmypid();',
                'error_message' => 'Trace',
            ],
            'traceVariablesComma' => [
                'code' => '<?php
                    /** @psalm-trace $a, $b */
                    $a = getmypid();
                    $b = getmypid();',
                'error_message' => 'Trace',
            ],
            'traceStaticPropertyOfSelf' => [
                'code' => '<?php
                    final class Config {
                        public static string $name = "app";

                        public static function show(): void {
                            /** @psalm-trace self::$name */;
                        }
                    }',
                'error_message' => 'Trace - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:60 - self::$name: string',
            ],
            'traceStaticPropertyOfParentExpandsSelfInItsDeclaredType' => [
                'code' => '<?php
                    class Box {
                        /** @var array<self> */
                        public static array $items = [];
                    }

                    final class Child extends Box {
                        public static function show(): void {
                            /** @psalm-trace parent::$items */;
                        }
                    }',
                'error_message' => 'parent::$items: array<array-key, Box',
            ],
            'traceSelfPropertyIgnoresWhatWasWrittenThroughStatic' => [
                'code' => '<?php
                    class Registry {
                        /** @var list<int> */
                        public static array $ids = [];

                        public static function reset(): void {
                            static::$ids = [1];
                            /** @psalm-trace self::$ids */;
                        }
                    }',
                'error_message' => 'self::$ids: list<int',
            ],
            'traceStaticPropertyIgnoresWhatWasWrittenThroughSelf' => [
                'code' => '<?php
                    class Registry {
                        /** @var list<int> */
                        public static array $ids = [];

                        public static function reset(): void {
                            self::$ids = [1];
                            /** @psalm-trace static::$ids */;
                        }
                    }',
                'error_message' => 'static::$ids: list<int',
            ],
            'traceInstancePropertyNamedStaticallyIsUndefined' => [
                'code' => '<?php
                    final class Config {
                        public string $name = "app";

                        public static function show(): void {
                            /** @psalm-trace self::$name */;
                        }
                    }',
                'error_message' => 'UndefinedTrace',
            ],
            'traceUndefinedStaticProperty' => [
                'code' => '<?php
                    final class Config {
                        public static function show(): void {
                            /** @psalm-trace self::$missing */;
                        }
                    }',
                'error_message' => 'UndefinedTrace',
            ],
            'undefinedTraceVariable' => [
                'code' => '<?php
                    /** @psalm-trace $b */
                    echo 1;',
                'error_message' => 'UndefinedTrace',
            ],
        ];
    }
}
