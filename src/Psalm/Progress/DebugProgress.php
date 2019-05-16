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
        fwrite(STDERR, $message);
    }

    public function startScanningFiles(): void
    {
        fwrite(STDERR, 'Scanning files...' . "\n");
    }

    public function startAnalyzingFiles(): void
    {
        fwrite(STDERR, 'Analyzing files...' . "\n");
    }
}
