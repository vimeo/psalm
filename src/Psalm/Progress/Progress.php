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
}
