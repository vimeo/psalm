<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\Providers;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function dirname;

final class NewRelicStubTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    #[Override]
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // hack to isolate Psalm from PHPUnit cli arguments
        global $argv;
        $argv = [];
    }

    public function testFunctionsAreUndefinedWhenExtensionIsDisabled(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessageMatches('/UndefinedFunction.*newrelic_name_transaction/');

        $this->analyzeWithExtensionDisabled('<?php
            function f(): void {
                newrelic_name_transaction("name");
            }
        ');
    }

    public function testFunctionExistsGuardWorksWhenExtensionIsDisabled(): void
    {
        $this->analyzeWithExtensionDisabled('<?php
            function f(): void {
                if (function_exists("newrelic_name_transaction")) {
                    newrelic_name_transaction("name");
                }
            }
        ');

        $this->addToAssertionCount(1);
    }

    private function analyzeWithExtensionDisabled(string $code): void
    {
        $this->project_analyzer = new ProjectAnalyzer(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm>
                    <disableExtensions>
                        <extension name="newrelic"/>
                    </disableExtensions>
                </psalm>',
            ),
            new Providers(
                $this->file_provider,
                new FakeParserCacheProvider(),
            ),
        );
        $this->project_analyzer->setPhpVersion('7.4', 'tests');

        $codebase = $this->project_analyzer->getCodebase();
        $codebase->enterServerMode();
        $codebase->config->visitPreloadedStubFiles($codebase);

        $file_path = self::$src_dir_path . 'somefile.php';
        $this->addFile($file_path, $code);
        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'noticeErrorForms' => [
                'code' => '<?php
                    function f(Throwable $e, int $errno, string $file, int $line): void {
                        newrelic_notice_error("message");
                        newrelic_notice_error($e);
                        newrelic_notice_error("ignored", $e);
                        newrelic_notice_error($errno, "message", $file, $line);
                        newrelic_notice_error($errno, "message", $file, $line, []);
                        newrelic_notice_error(exception: $e);
                        newrelic_notice_error(exception: $errno, errstr: "message", fname: $file, line_nr: $line);
                    }
                ',
            ],
            'namedArguments' => [
                'code' => '<?php
                    function f(): void {
                        newrelic_set_appname(appname: "app", license_key: "", xmit: true);
                        newrelic_start_transaction(appname: "app", license_key: "key");
                        newrelic_accept_distributed_trace_headers(transport_type: "Queue", headers: []);
                        newrelic_custom_metric(value: 1.5, metric: "Custom/metric");
                        newrelic_record_custom_event(parameters: ["a" => 1, "b" => null], event_type: "Event");
                        newrelic_add_custom_parameter(value: 1, parameter: "name");
                        newrelic_get_browser_timing_header(with_tags: false);
                    }
                ',
            ],
            'recordDatastoreSegmentReturnsCallbackResult' => [
                'code' => '<?php
                    /** @return non-empty-string */
                    function callback(): string {
                        return "result";
                    }

                    $result = newrelic_record_datastore_segment(
                        "callback",
                        ["product" => "MySQL", "collection" => "users", "operation" => "select", "extra" => []],
                    );
                ',
                'assertions' => [
                    '$result===' => 'non-empty-string',
                ],
            ],
            'insertDistributedTraceHeadersByReference' => [
                'code' => '<?php
                    $headers = [];
                    $inserted = newrelic_insert_distributed_trace_headers($headers);
                ',
                'assertions' => [
                    '$headers' => 'array<array-key, mixed>',
                    '$inserted' => 'bool',
                ],
            ],
            'distributedTracePayloadMethods' => [
                'code' => '<?php
                    $payload = newrelic_create_distributed_trace_payload();
                    $text = $payload->text();
                    $httpSafe = $payload->httpSafe();
                ',
                'assertions' => [
                    '$payload' => 'newrelic\DistributedTracePayload',
                    '$text' => 'string',
                    '$httpSafe' => 'string',
                ],
                'ignored_issues' => ['DeprecatedFunction'],
            ],
            'discardedResultsAreNotUnused' => [
                'code' => '<?php
                    function f(): void {
                        newrelic_name_transaction("name");
                        newrelic_add_custom_parameter("key", "value");
                        newrelic_get_linking_metadata();
                        newrelic_get_trace_metadata();
                        newrelic_is_sampled();
                        newrelic_get_browser_timing_footer();
                        newrelic_disable_autorum();
                        newrelic_record_datastore_segment(static fn(): int => 1, ["product" => "MySQL"]);
                    }
                ',
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
            'pureCallerNeedsIo' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function f(string $name): int {
                        newrelic_name_transaction($name);
                        return 1;
                    }
                ',
                'error_message' => 'requires io',
            ],
            'pureCallerNeedsIoForDatastoreSegmentWithPureCallback' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function f(): int {
                        return newrelic_record_datastore_segment(static fn(): int => 1, ["product" => "MySQL"]);
                    }
                ',
                'error_message' => 'requires io',
            ],
            'pureCallerCannotNoticeError' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function f(string $message): int {
                        newrelic_notice_error($message);
                        return 1;
                    }
                ',
                'error_message' => 'ImpureFunctionCall',
            ],
            'insertDistributedTraceHeadersIntoReferenceParamNeedsWriteRefs' => [
                'code' => '<?php
                    /** @psalm-capabilities io */
                    function f(array &$headers): void {
                        newrelic_insert_distributed_trace_headers($headers);
                    }
                ',
                'error_message' => 'requires write-refs|io',
            ],
            'datastoreSegmentRequiresProduct' => [
                'code' => '<?php
                    newrelic_record_datastore_segment(static fn(): int => 1, ["collection" => "users"]);
                ',
                'error_message' => 'InvalidArgument',
            ],
            'datastoreSegmentCallbackPurityIsKept' => [
                'code' => '<?php
                    /** @psalm-capabilities io */
                    function f(): int {
                        return newrelic_record_datastore_segment(
                            static function (): int {
                                $GLOBALS["counter"] = 1;
                                return 1;
                            },
                            ["product" => "MySQL"],
                        );
                    }
                ',
                'error_message' => 'ImpureFunctionCall',
            ],
            'deprecatedPayloadHelper' => [
                'code' => '<?php
                    newrelic_create_distributed_trace_payload();
                ',
                'error_message' => 'DeprecatedFunction',
            ],
            'deprecatedAcceptPayload' => [
                'code' => '<?php
                    newrelic_accept_distributed_trace_payload("payload");
                ',
                'error_message' => 'DeprecatedFunction',
            ],
            'deprecatedAcceptPayloadHttpSafe' => [
                'code' => '<?php
                    newrelic_accept_distributed_trace_payload_httpsafe("payload");
                ',
                'error_message' => 'DeprecatedFunction',
            ],
            'deprecatedEnableParams' => [
                'code' => '<?php
                    newrelic_enable_params(true);
                ',
                'error_message' => 'DeprecatedFunction',
            ],
            'missingArgument' => [
                'code' => '<?php
                    newrelic_name_transaction();
                ',
                'error_message' => 'TooFewArguments',
            ],
        ];
    }
}
