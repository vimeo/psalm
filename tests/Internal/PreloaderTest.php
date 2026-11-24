<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Preloader;
use Psalm\Tests\Progress\EchoProgress;

use function ob_get_clean;
use function ob_start;

final class PreloaderTest extends TestCase
{
    // Preloader remembers that it ran, in a static property
    #[RunInSeparateProcess]
    public function testPreloadingPrintsASingleLine(): void
    {
        ob_start();
        Preloader::preload(new EchoProgress());
        $output = (string) ob_get_clean();

        $this->assertMatchesRegularExpression('/^Preloading took \d+(\.\d+)? seconds\.\n\n$/', $output);
    }
}
