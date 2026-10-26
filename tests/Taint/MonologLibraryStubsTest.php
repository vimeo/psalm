<?php

declare(strict_types=1);

namespace Psalm\Tests\Taint;

use Override;

/**
 * The stubs of stubs/libraries for monolog/monolog and psr/log, which Psalm loads because the code below declares
 * classes of the packages, as their sources do: their taints and capabilities
 */
final class MonologLibraryStubsTest extends LibraryStubsTestCase
{
    /**
     * @psalm-pure
     */
    #[Override]
    protected static function getLibraries(): string
    {
        return '<?php
            namespace Psr\Log {
                interface LoggerInterface {}
                class LogLevel {}
            }

            namespace Monolog {
                enum Level: int {
                    case Debug = 100;
                }
                interface ResettableInterface {}
                class LogRecord {}
                class JsonSerializableDateTimeImmutable {}
                class Logger implements \Psr\Log\LoggerInterface, ResettableInterface {}
            }

            namespace Monolog\Formatter {
                interface FormatterInterface {}
                class NormalizerFormatter implements FormatterInterface {}
                class LineFormatter extends NormalizerFormatter {}
                class JsonFormatter extends NormalizerFormatter {
                    public const BATCH_MODE_JSON = 1;
                }
            }

            namespace Monolog\Processor {
                interface ProcessorInterface {}
                class WebProcessor implements ProcessorInterface {}
            }

            namespace Monolog\Handler {
                interface HandlerInterface {}
                interface ProcessableHandlerInterface {}
                trait ProcessableHandlerTrait {}
                interface FormattableHandlerInterface {}
                trait FormattableHandlerTrait {}
                abstract class Handler implements HandlerInterface {}
                abstract class AbstractHandler extends Handler implements \Monolog\ResettableInterface {}
                abstract class AbstractProcessingHandler extends AbstractHandler implements
                    ProcessableHandlerInterface,
                    FormattableHandlerInterface
                {
                    use ProcessableHandlerTrait;
                    use FormattableHandlerTrait;
                }
                class StreamHandler extends AbstractProcessingHandler {}
                class SocketHandler extends AbstractProcessingHandler {}
                class ErrorLogHandler extends AbstractProcessingHandler {
                    public const OPERATING_SYSTEM = 0;
                }
                class GroupHandler extends Handler implements
                    ProcessableHandlerInterface,
                    \Monolog\ResettableInterface
                {
                    use ProcessableHandlerTrait;
                }
            }';
    }

    /**
     * @psalm-pure
     */
    #[Override]
    protected static function getReportedIssueTypePrefixes(): array
    {
        return ['Tainted', 'Impure'];
    }

    /**
     * @return array<string, array{string, list<string>}>
     * @psalm-pure
     */
    #[Override]
    public static function providerTaints(): array
    {
        return [
            'handlerTargets' => [
                '<?php
                    $input = (string) $_GET["input"];
                    new Monolog\Handler\StreamHandler($input);
                    new Monolog\Handler\SocketHandler($input);
                    new Monolog\Handler\StreamHandler("php://stderr");',
                [
                    'TaintedFile on line 3',
                    'TaintedSSRF on line 4',
                ],
            ],
            'loggerCapabilities' => [
                '<?php
                    /** @psalm-capabilities read-props */
                    function getChannel(Monolog\Logger $logger): string {
                        return $logger->getName();
                    }

                    /** @psalm-capabilities read-props */
                    function logInfo(Monolog\Logger $logger): void {
                        $logger->info("message");
                    }

                    /** @psalm-capabilities read-props|write-this-props|write-props|read-globals|write-globals|io|time */
                    function logWithoutWritingReferences(Monolog\Logger $logger): void {
                        $logger->info("message");
                    }

                    /** @psalm-pure */
                    function getLevel(): Monolog\Level {
                        return Monolog\Logger::toMonologLevel("debug");
                    }',
                [
                    'ImpureMethodCall on line 9',
                ],
            ],
            'psrLoggerCapabilities' => [
                '<?php
                    /** @psalm-capabilities read-props */
                    function logInfo(Psr\Log\LoggerInterface $logger): void {
                        $logger->info("message");
                    }

                    /** @psalm-capabilities read-props|write-this-props|write-props|read-globals|write-globals|io|time */
                    function logWithoutWritingReferences(Psr\Log\LoggerInterface $logger): void {
                        $logger->info("message");
                    }',
                [
                    'ImpureMethodCall on line 4',
                ],
            ],
            'handlerSetupCapabilities' => [
                '<?php
                    /** @psalm-capabilities write-this-props|read-globals|io */
                    function makeHandler(): Monolog\Handler\StreamHandler {
                        $handler = new Monolog\Handler\StreamHandler("php://stderr");
                        $handler->setFormatter(new Monolog\Formatter\LineFormatter());

                        return $handler;
                    }',
                [],
            ],
        ];
    }
}
