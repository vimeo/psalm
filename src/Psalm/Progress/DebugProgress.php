<?php

declare(strict_types=1);

namespace Psalm\Progress;

use Override;

use function error_reporting;

use const E_ALL;

final class DebugProgress extends Progress
{
    #[Override]
    public function setErrorReporting(): void
    {
        error_reporting(E_ALL);
    }

    #[Override]
    public function debug(string $message): void
    {
        $this->write($message);
    }

    #[Override]
    public function startScanningFiles(): void
    {
        $this->write("\n" . 'Scanning files...' . "\n\n");
    }

    #[Override]
    public function startAnalyzingFiles(): void
    {
        $this->write("\n" . 'Analyzing files...' . "\n");
    }

    #[Override]
    public function startAlteringFiles(): void
    {
        $this->write('Updating files...' . "\n");
    }

    #[Override]
    public function alterFileDone(string $file_name): void
    {
        $this->write('Altered ' . $file_name . "\n");
    }
}
