<?php

declare(strict_types=1);

namespace Psalm\Tests\EndToEnd;

use PHPUnit\Framework\TestCase;

final class DestructiveAutoloaderTest extends TestCase
{
    use PsalmRunnerTrait;

    public function testSucceedsWithEmptyFile(): void
    {
        $this->runPsalm(['--no-cache'], __DIR__ . '/' . '../fixtures/DestructiveAutoloader/', true);
    }
}
