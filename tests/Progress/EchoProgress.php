<?php
namespace Psalm\Tests\Progress;

use Psalm\Progress\Progress;

class EchoProgress extends Progress
{
    public function startScanningFiles(): void
    {
        echo 'Scanning files...' . "\n";
    }

    public function startAnalyzingFiles(): void
    {
        echo 'Analyzing files...' . "\n";
    }
}
