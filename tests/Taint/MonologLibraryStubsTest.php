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
                trait LoggerTrait {
                    public function emergency(string|\Stringable $message, array $context = []): void {
                        $this->log(LogLevel::EMERGENCY, $message, $context);
                    }
                    public function alert(string|\Stringable $message, array $context = []): void {
                        $this->log(LogLevel::ALERT, $message, $context);
                    }
                    public function critical(string|\Stringable $message, array $context = []): void {
                        $this->log(LogLevel::CRITICAL, $message, $context);
                    }
                    public function error(string|\Stringable $message, array $context = []): void {
                        $this->log(LogLevel::ERROR, $message, $context);
                    }
                    public function warning(string|\Stringable $message, array $context = []): void {
                        $this->log(LogLevel::WARNING, $message, $context);
                    }
                    public function notice(string|\Stringable $message, array $context = []): void {
                        $this->log(LogLevel::NOTICE, $message, $context);
                    }
                    public function info(string|\Stringable $message, array $context = []): void {
                        $this->log(LogLevel::INFO, $message, $context);
                    }
                    public function debug(string|\Stringable $message, array $context = []): void {
                        $this->log(LogLevel::DEBUG, $message, $context);
                    }
                    abstract public function log($level, string|\Stringable $message, array $context = []): void;
                }
                abstract class AbstractLogger implements LoggerInterface {
                    use LoggerTrait;
                }
                class NullLogger extends AbstractLogger {
                    public function log($level, string|\Stringable $message, array $context = []): void {}
                }
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
        return ['Tainted', 'Impure', 'ImmutableDependency'];
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
            'psrLoggerImplementationCapabilities' => [
                '<?php
                    final class CountingLogger extends Psr\Log\AbstractLogger {
                        public int $count = 0;

                        /** @psalm-capabilities read-props|write-this-props */
                        public function log($level, string|\Stringable $message, array $context = []): void {
                            ++$this->count;
                        }
                    }

                    final class CountingTraitLogger implements Psr\Log\LoggerInterface {
                        use Psr\Log\LoggerTrait;

                        public int $count = 0;

                        /** @psalm-capabilities read-props|write-this-props */
                        public function log($level, string|\Stringable $message, array $context = []): void {
                            ++$this->count;
                        }
                    }

                    final class SilentLogger extends Psr\Log\NullLogger {}

                    final class UnannotatedLogger extends Psr\Log\AbstractLogger {
                        public function log($level, string|\Stringable $message, array $context = []): void {}
                    }

                    /** @psalm-capabilities read-props */
                    function logInfo(
                        Psr\Log\AbstractLogger $logger,
                        CountingTraitLogger $trait_logger,
                        Psr\Log\NullLogger $null_logger
                    ): void {
                        $logger->info("message");
                        $trait_logger->info("message");
                        $null_logger->log("info", "message");
                    }',
                [
                    'ImmutableDependency on line 24',
                    'ImmutableDependency on line 25',
                    'ImpureMethodCall on line 34',
                    'ImpureMethodCall on line 35',
                    'ImpureMethodCall on line 36',
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
