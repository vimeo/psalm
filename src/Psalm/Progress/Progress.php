<?php

declare(strict_types=1);

namespace Psalm\Progress;

use function error_reporting;
use function function_exists;
use function fwrite;
use function sapi_windows_cp_is_utf8;
use function stripos;

use const E_ERROR;
use const PHP_EOL;
use const PHP_OS;
use const STDERR;

abstract class Progress
{
    public function setErrorReporting(): void
    {
        error_reporting(E_ERROR);
    }

    abstract public function debug(string $message): void;


    abstract public function startPhase(Phase $phase, int $threads = 1): void;

    abstract public function expand(int $number_of_tasks): void;

    abstract public function taskDone(int $level): void;

    abstract public function finish(): void;


    abstract public function alterFileDone(string $file_name): void;

    public function write(string $message): void
    {
        fwrite(STDERR, $message);
    }

    public function warning(string $message): void
    {
        $this->write('Warning: ' . $message . PHP_EOL);
    }

    final protected static function doesTerminalSupportUtf8(): bool
    {
        if (stripos(PHP_OS, 'WIN') === 0) {
            if (!function_exists('sapi_windows_cp_is_utf8') || !sapi_windows_cp_is_utf8()) {
                return false;
            }
        }

        return true;
    }
}
