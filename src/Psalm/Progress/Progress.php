<?php
namespace Psalm\Progress;

abstract class Progress
{
    public function setErrorReporting(): void
    {
        error_reporting(E_ERROR);
    }

    public function debug(string $message): void
    {
    }

    public function startScanningFiles(): void
    {
    }

    public function startAnalyzingFiles(): void
    {
    }

    public function start(int $number_of_tasks): void
    {
    }

    public function taskDone(bool $successful): void
    {
    }

    public function finish(): void
    {
    }

    protected function write(string $message): void
    {
        fwrite(STDERR, $message);
    }
}
