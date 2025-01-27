<?php

declare(strict_types=1);

namespace Psalm\Progress;

use function error_reporting;

use const E_ALL;

final class DebugProgress extends Progress
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
        $this->write("\n" . 'Scanning files...' . "\n\n");
    }

    public function startAnalyzingFiles(): void
    {
        $this->write("\n" . 'Analyzing files...' . "\n");
    }

    public function startAlteringFiles(): void
    {
        $this->write('Updating files...' . "\n");
    }

    public function alterFileDone(string $file_name): void
    {
        $this->write('Altered ' . $file_name . "\n");
    }
}
