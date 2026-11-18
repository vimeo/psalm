<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Cli;

use PHPUnit\Framework\TestCase;
use Psalm\Internal\Cli\ProjectBootstrap;
use Psalm\Internal\Cli\ProjectBootstrapException;

use function sys_get_temp_dir;
use function uniqid;

use const DIRECTORY_SEPARATOR;

final class ProjectBootstrapTest extends TestCase
{
    public function testAMissingBaselineThrowsWhatToDo(): void
    {
        $missing = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('psalm-missing-baseline-', true) . '.xml';

        $this->expectException(ProjectBootstrapException::class);
        $this->expectExceptionMessage(
            'Could not read the baseline: ' . $missing . ' does not exist or is not readable. '
                . 'Create it with --set-baseline=' . $missing,
        );

        ProjectBootstrap::readBaselineFile($missing);
    }
}
