<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class ExtProtobufStubTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'protobufSettersReturnTheMessage' => [
                'code' => <<<'PHP'
                    <?php
                    $timestamp = (new \Google\Protobuf\Timestamp())->setSeconds(0)->setNanos(0);
                    $duration = (new \Google\Protobuf\Duration())->setSeconds("1")->setNanos(5);
                    PHP,
                'assertions' => [
                    '$timestamp===' => 'Google\Protobuf\Timestamp&static',
                    '$duration===' => 'Google\Protobuf\Duration&static',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'protobufGettersOfTheWellKnownTypes' => [
                'code' => <<<'PHP'
                    <?php
                    $timestamp = new \Google\Protobuf\Timestamp(['seconds' => 1, 'nanos' => 2]);
                    $seconds = $timestamp->getSeconds();
                    $nanos = $timestamp->getNanos();
                    $duration = new \Google\Protobuf\Duration();
                    $durationSeconds = $duration->getSeconds();
                    $durationNanos = $duration->getNanos();
                    PHP,
                'assertions' => [
                    '$seconds===' => 'int|string',
                    '$nanos===' => 'int',
                    '$durationSeconds===' => 'int|string',
                    '$durationNanos===' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'protobufTimestampAndDateTime' => [
                'code' => <<<'PHP'
                    <?php
                    $timestamp = (new \Google\Protobuf\Timestamp())->setSeconds(1);
                    $date = $timestamp->toDateTime();
                    $timestamp->fromDateTime(new \DateTime());
                    PHP,
                'assertions' => [
                    '$date===' => 'DateTime',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'protobufMessageSerialization' => [
                'code' => <<<'PHP'
                    <?php
                    final class Greeting extends \Google\Protobuf\Internal\Message
                    {
                    }

                    $greeting = new Greeting();
                    $binary = $greeting->serializeToString();
                    $json = $greeting->serializeToJsonString();
                    $greeting->mergeFromString($binary);
                    $greeting->mergeFromJsonString($json, true);
                    $greeting->mergeFrom(new Greeting());
                    $greeting->clear();
                    $greeting->discardUnknownFields();
                    PHP,
                'assertions' => [
                    '$binary===' => 'string',
                    '$json===' => 'string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
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
            'protobufNanosOfTheWrongType' => [
                'code' => <<<'PHP'
                    <?php
                    (new \Google\Protobuf\Timestamp())->setNanos([]);
                    PHP,
                'error_message' => 'InvalidArgument',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'protobufUnknownMethod' => [
                'code' => <<<'PHP'
                    <?php
                    (new \Google\Protobuf\Duration())->setMinutes(1);
                    PHP,
                'error_message' => 'UndefinedMethod',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
        ];
    }
}
