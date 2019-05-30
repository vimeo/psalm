<?php
namespace Psalm\Progress;

class DebugProgress extends Progress
{
    public function setErrorReporting(): void
    {
        error_reporting(E_ALL);
    }

    public function debug(string $message): void
    {
        $this->write($message);
    }

    public function startScanningFiles(): void
    {
        $this->write('Scanning files...' . "\n");
    }

    public function startAnalyzingFiles(): void
    {
        $this->write('Analyzing files...' . "\n");
    }
}
