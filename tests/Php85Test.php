<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function str_contains;
use function strlen;
use function strpos;

use const DIRECTORY_SEPARATOR;

final class Php85Test extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    // the expected message is matched with a trailing word boundary, hence no closing "()"
    private const WAKEUP_REASON = 'this method is obsolete, as serialization hooks are provided by __unserialize()'
        . ' and __serialize';

    #[Override]
    public function providerValidCodeParse(): iterable
    {
        return [
            'deprecatedInPhp85AreSilentBeforePhp85' => [
                'code' => '<?php
                    function callDeprecatedInPhp85(
                        CurlHandle $curl,
                        CurlShareHandle $share,
                        finfo $finfo,
                        GdImage $image,
                        XMLParser $parser,
                        mysqli_stmt $statement,
                        ReflectionProperty $property,
                        ReflectionMethod $method,
                        SplObjectStorage $storage,
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
                    /** @var finfo $finfo */
                    $finfo = new finfo();
                    /** @var GdImage $image */
                    $image = imagecreate(1, 1);
                    /** @var XMLParser $parser */
                    $parser = xml_parser_create();

                    $finfoClosed = finfo_close($finfo);
                    $imageDestroyed = imagedestroy($image);
                    $parserFreed = xml_parser_free($parser);
                    $timeoutSet = socket_set_timeout(STDIN, 1, 2);',
                'assertions' => [
                    '$finfoClosed' => 'true',
                    '$imageDestroyed' => 'true',
                    '$parserFreed' => 'bool',
                    '$timeoutSet' => 'bool',
                ],
                'ignored_issues' => ['DeprecatedFunction'],
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
        $cases = [];

        $symbols = [
            // symbol => [parameter type, call, call-site token, reason, PHP version (default 8.5)]
            'curl_close' => ['CurlHandle', 'curl_close($value);', 'curl_close', 'as it has no effect since PHP 8.0'],
            'curl_share_close' => [
                'CurlShareHandle',
                'curl_share_close($value);',
                'curl_share_close',
                'as it has no effect since PHP 8.0',
            ],
            'finfo_close' => ['finfo', 'finfo_close($value);', 'finfo_close', 'as finfo objects are freed automatically'],
            'imagedestroy' => ['GdImage', 'imagedestroy($value);', 'imagedestroy', 'as it has no effect since PHP 8.0'],
            'xml_parser_free' => [
                'XMLParser',
                'xml_parser_free($value);',
                'xml_parser_free',
                'as it has no effect since PHP 8.0',
            ],
            'socket_set_timeout' => [
                'mixed',
                'socket_set_timeout(STDIN, 1);',
                'socket_set_timeout',
                'use stream_set_timeout() instead',
            ],
            'mysqli_execute' => [
                'mysqli_stmt',
                'mysqli_execute($value);',
                'mysqli_execute',
                'use mysqli_stmt_execute() instead',
            ],
            'date_sunrise' => ['mixed', 'date_sunrise(0);', 'date_sunrise', 'use date_sun_info() instead', '8.1'],
            'date_sunset' => ['mixed', 'date_sunset(0);', 'date_sunset', 'use date_sun_info() instead', '8.1'],
            'ReflectionProperty::setAccessible' => [
                'ReflectionProperty',
                '$value->setAccessible(true);',
                'setAccessible',
                'as it has no effect since PHP 8.1',
            ],
            'ReflectionMethod::setAccessible' => [
                'ReflectionMethod',
                '$value->setAccessible(true);',
                'setAccessible',
                'as it has no effect since PHP 8.1',
            ],
            'SplObjectStorage::attach' => [
                'SplObjectStorage',
                '$value->attach(new stdClass());',
                'attach',
                'use method SplObjectStorage::offsetSet() instead',
            ],
            'SplObjectStorage::detach' => [
                'SplObjectStorage',
                '$value->detach(new stdClass());',
                'detach',
                'use method SplObjectStorage::offsetUnset() instead',
            ],
            'SplObjectStorage::contains' => [
                'SplObjectStorage',
                '$value->contains(new stdClass());',
                'contains',
                'use method SplObjectStorage::offsetExists() instead',
            ],
        ];

        foreach (['DateTime', 'DateTimeImmutable', 'DateTimeZone', 'DateInterval', 'DatePeriod'] as $class) {
            $symbols[$class . '::__wakeup'] = [$class, '$value->__wakeup();', '__wakeup', self::WAKEUP_REASON];
        }

        foreach ($symbols as $symbol => $entry) {
            [$type, $call, $token, $reason] = $entry;
            $is_method = str_contains($symbol, '::');
            $indent = '                        ';
            $line = $indent . $call;

            $cases[$symbol] = [
                'code' => '<?php
                    function check(' . $type . ' $value): void {
' . $line . '
                    }',
                'error_message' => ($is_method ? 'DeprecatedMethod' : 'DeprecatedFunction')
                    . ' - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:'
                    . (($is_method ? (int) strpos($call, $token) : 0) + strlen($indent) + 1)
                    . ' - The ' . ($is_method ? 'method ' : 'function ') . $symbol
                    . ' has been marked as deprecated (' . $reason,
                'error_levels' => ['MixedArgument', 'UnusedMethodCall', 'UnusedFunctionCall'],
                'php_version' => $entry[4] ?? '8.5',
            ];
        }

        return $cases;
    }
}
