<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;

/**
 * Provides the file fixture for tests that construct an analyzer per scenario.
 */
abstract class DeferredAnalyzerTestCase extends TestCase
{
    /**
     * @psalm-capabilities read-props
     */
    #[Override]
    protected function setUpProjectAnalyzer(): void
    {
    }
}
