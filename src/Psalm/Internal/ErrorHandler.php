<?php

declare(strict_types=1);

namespace Psalm\Internal;

use Closure;
use RuntimeException;
use Throwable;

use function defined;
use function error_reporting;
use function fwrite;
use function implode;
use function ini_set;
use function set_error_handler;
use function set_exception_handler;

use const E_ALL;
use const STDERR;

/**
 * @internal
 */
final class ErrorHandler
{
    private static bool $exceptions_enabled = true;

    private static string $args = '';

    /** @var (Closure():void)|null */
    private static ?Closure $before_fatal_error = null;

    /**
     * @param array<int,string> $argv
     */
    public static function install(array $argv = []): void
    {
        self::$args = implode(' ', $argv);
        self::setErrorReporting();
        self::installErrorHandler();
        self::installExceptionHandler();
    }

    /**
     * @template T
     * @param callable():T $f
     * @return T
     */
    public static function runWithExceptionsSuppressed(callable $f)
    {
        try {
            self::$exceptions_enabled = false;
            return $f();
        } finally {
            self::$exceptions_enabled = true;
        }
    }

    /**
     * Registers what to do before an uncaught exception is written to STDERR, e.g. clearing a status line
     * that the message would otherwise land on. Pass null to unregister.
     *
     * @param (Closure():void)|null $callback
     */
    public static function setBeforeFatalError(?Closure $callback): void
    {
        self::$before_fatal_error = $callback;
    }

    /**
     * @psalm-suppress UnusedConstructor added to prevent instantiations
     * @psalm-mutation-free
     */
    private function __construct()
    {
    }

    private static function setErrorReporting(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
    }

    private static function installErrorHandler(): void
    {
        set_error_handler(static function (
            int $error_code,
            string $error_message,
            string $error_filename = 'unknown',
            int $error_line = -1,
        ): bool {
            if (ErrorHandler::$exceptions_enabled && ($error_code & error_reporting())) {
                throw new RuntimeException(
                    'PHP Error: ' . $error_message
                    . ' in ' . $error_filename . ':' . $error_line
                    . ' for command with CLI args "' . ErrorHandler::$args . '"',
                    $error_code,
                );
            }
            // let PHP handle suppressed errors how it sees fit
            return false;
        });
    }

    private static function installExceptionHandler(): void
    {
        /**
         * If there is an uncaught exception,
         * then print more of the backtrace than is done by default to stderr,
         * then exit with a non-zero exit code to indicate failure.
         */
        set_exception_handler(static function (Throwable $throwable): never {
            if (ErrorHandler::$before_fatal_error !== null) {
                (ErrorHandler::$before_fatal_error)();
            }

            fwrite(STDERR, "Uncaught $throwable\n");
            $version = defined('PSALM_VERSION') ? PSALM_VERSION : '(unknown version)';
            fwrite(STDERR, "(Psalm $version crashed due to an uncaught Throwable)\n");
            exit(1);
        });
    }
}
