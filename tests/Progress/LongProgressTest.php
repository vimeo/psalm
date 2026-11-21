<?php

declare(strict_types=1);

namespace Psalm\Tests\Progress;

use PHPUnit\Framework\TestCase;
use Psalm\Progress\Phase;

use function ob_get_clean;
use function ob_start;

final class LongProgressTest extends TestCase
{
    public function testPreloadingPrintsASingleLine(): void
    {
        $progress = new EchoProgress();

        ob_start();
        $progress->startPhase(Phase::PRELOADING);
        $progress->finish();
        $output = (string) ob_get_clean();

        $this->assertMatchesRegularExpression('/^Preloading took \d+(\.\d+)? seconds\.\n\n$/', $output);
    }
}
