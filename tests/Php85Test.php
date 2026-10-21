<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function str_contains;

final class Php85Test extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    #[Override]
    public function providerValidCodeParse(): iterable
    {
        return [
            'deprecatedInPhp85AreSilentBeforePhp85' => [
                'code' => '<?php
                    function callDeprecatedInPhp85(
                        CurlHandle $curl,
                        CurlShareHandle $share,
                        mixed $finfo,
                        mixed $image,
                        mixed $parser,
                        mixed $statement,
                        ReflectionProperty $property,
                        ReflectionMethod $method,
                        SplObjectStorage $storage,
                        DateTimeInterface $dateTimeInterface,
                        DateTime $dateTime,
                        DateTimeImmutable $immutable,
                        DateTimeZone $zone,
                        DateInterval $interval,
                        DatePeriod $period,
                    ): void {
                        curl_close($curl);
                        curl_share_close($share);
                        finfo_close($finfo);
                        imagedestroy($image);
                        xml_parser_free($parser);
                        socket_set_timeout(STDIN, 1);
                        mysqli_execute($statement);
                        $property->setAccessible(true);
                        $method->setAccessible(true);
                        $storage->attach(new stdClass());
                        $storage->contains(new stdClass());
                        $storage->detach(new stdClass());
                        $dateTimeInterface->__wakeup();
                        $dateTime->__wakeup();
                        $immutable->__wakeup();
                        $zone->__wakeup();
                        $interval->__wakeup();
                        $period->__wakeup();
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedArgument', 'UnusedMethodCall'],
                'php_version' => '8.4',
            ],
            'dateSunFunctionsAreSilentBeforePhp81' => [
                'code' => '<?php
                    $sunrise = date_sunrise(0);
                    $sunset = date_sunset(0);',
                'assertions' => [
                    '$sunrise' => 'false|float|int|string',
                    '$sunset' => 'false|float|int|string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'dateSunFunctionsIgnoreFalsableReturn' => [
                'code' => '<?php
                    function sunrise(int $timestamp): int|float|string {
                        return date_sunrise($timestamp);
                    }

                    function sunset(int $timestamp): int|float|string {
                        return date_sunset($timestamp);
                    }

                    /** @psalm-pure */
                    function pureSunrise(): int|float|string|false {
                        $callable = date_sunrise(...);

                        return $callable(0);
                    }',
                'assertions' => [],
                'ignored_issues' => ['DeprecatedFunction'],
                'php_version' => '8.1',
            ],
            'callMapTypesSurviveRedeclarationInPhp85' => [
                'code' => '<?php
                    /** @var mixed $handle */
                    $handle = null;

                    $finfoClosed = finfo_close($handle);
                    $imageDestroyed = imagedestroy($handle);
                    $parserFreed = xml_parser_free($handle);
                    $timeoutSet = socket_set_timeout(STDIN, 1, 2);',
                'assertions' => [
                    '$finfoClosed' => 'true',
                    '$imageDestroyed' => 'true',
                    '$parserFreed' => 'bool',
                    '$timeoutSet' => 'bool',
                ],
                'ignored_issues' => ['DeprecatedFunction', 'MixedArgument'],
                'php_version' => '8.5',
            ],
            'classStubsSurviveRedeclarationInPhp85' => [
                'code' => '<?php
                    final class Foo {}

                    /** @var SplObjectStorage<Foo, string> $storage */
                    $storage = new SplObjectStorage();
                    $storage->attach(new Foo(), "info");
                    $info = $storage[new Foo()];

                    $fromFormat = DateTime::createFromFormat("Y", "2020");
                    $immutable = DateTimeImmutable::createFromFormat("Y", "2020");
                    /** @var DateTimeZone $zone */
                    $zone = new DateTimeZone("UTC");
                    $zoneName = $zone->getName();
                    /** @var DateInterval $interval */
                    $interval = new DateInterval("P1Y");
                    $years = $interval->y;

                    /** @var DatePeriod<DateTimeImmutable> $period */
                    $period = new DatePeriod(new DateTimeImmutable(), new DateInterval("P1D"), 1);
                    $periodItem = null;
                    foreach ($period as $periodItem) {
                    }',
                'assertions' => [
                    '$info' => 'string',
                    '$fromFormat' => 'DateTime|false',
                    '$immutable' => 'DateTimeImmutable|false',
                    '$zoneName' => 'string',
                    '$years' => 'int',
                    '$periodItem' => 'DateTimeImmutable|null',
                ],
                'ignored_issues' => ['DeprecatedMethod'],
                'php_version' => '8.5',
            ],
        ];
    }

    #[Override]
    public function providerInvalidCodeParse(): iterable
    {
        $wakeup = 'this method is obsolete, as serialization hooks are provided by __unserialize() and __serialize';
        $no_effect_80 = 'as it has no effect since PHP 8.0';
        $no_effect_81 = 'as it has no effect since PHP 8.1';

        // symbol => [parameter type, call, reason, PHP version]
        $symbols = [
            'curl_close' => ['CurlHandle', 'curl_close($value)', $no_effect_80],
            'curl_share_close' => ['CurlShareHandle', 'curl_share_close($value)', $no_effect_80],
            'finfo_close' => ['mixed', 'finfo_close($value)', 'as finfo objects are freed automatically'],
            'imagedestroy' => ['mixed', 'imagedestroy($value)', $no_effect_80],
            'xml_parser_free' => ['mixed', 'xml_parser_free($value)', $no_effect_80],
            'socket_set_timeout' => ['mixed', 'socket_set_timeout(STDIN, 1)', 'use stream_set_timeout() instead'],
            'mysqli_execute' => ['mixed', 'mysqli_execute($value)', 'use mysqli_stmt_execute() instead'],
            'date_sunrise' => ['mixed', 'date_sunrise(0)', 'use date_sun_info() instead', '8.1'],
            'date_sunset' => ['mixed', 'date_sunset(0)', 'use date_sun_info() instead', '8.1'],
            'ReflectionProperty::setAccessible' => ['ReflectionProperty', '$value->setAccessible(true)', $no_effect_81],
            'ReflectionMethod::setAccessible' => ['ReflectionMethod', '$value->setAccessible(true)', $no_effect_81],
            'SplObjectStorage::attach' => [
                'SplObjectStorage',
                '$value->attach(new stdClass())',
                'use method SplObjectStorage::offsetSet() instead',
            ],
            'SplObjectStorage::detach' => [
                'SplObjectStorage',
                '$value->detach(new stdClass())',
                'use method SplObjectStorage::offsetUnset() instead',
            ],
            'SplObjectStorage::contains' => [
                'SplObjectStorage',
                '$value->contains(new stdClass())',
                'use method SplObjectStorage::offsetExists() instead',
            ],
        ];

        foreach (['DateTimeInterface', 'DateTime', 'DateTimeImmutable', 'DateTimeZone', 'DateInterval', 'DatePeriod'] as $class) {
            $symbols[$class . '::__wakeup'] = [$class, '$value->__wakeup()', $wakeup];
        }

        foreach ($symbols as $symbol => $entry) {
            [$type, $call, $reason] = $entry;
            $kind = str_contains($symbol, '::') ? 'method' : 'function';

            yield $symbol => [
                'code' => '<?php
                    function check(' . $type . ' $value): void {
                        ' . $call . ';
                    }',
                'error_message' => "The $kind $symbol has been marked as deprecated ($reason",
                'error_levels' => ['MixedArgument', 'UnusedMethodCall', 'UnusedFunctionCall'],
                'php_version' => $entry[3] ?? '8.5',
            ];
        }
    }
}
