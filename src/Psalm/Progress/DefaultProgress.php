<?php

namespace Psalm\Progress;

class DefaultProgress extends Progress
{
    public function startScanningFiles(): void
    {
        fwrite(STDERR, 'Scanning files...' . "\n");
    }

    public function startAnalyzingFiles(): void
    {
        fwrite(STDERR, 'Analyzing files...' . "\n");
    }
}
