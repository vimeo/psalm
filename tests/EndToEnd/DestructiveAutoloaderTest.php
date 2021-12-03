<?php
namespace Psalm\Tests\EndToEnd;

use PHPUnit\Framework\TestCase;

use function version_compare;

class DestructiveAutoloaderTest extends TestCase
{
    use PsalmRunnerTrait;

    public function testSucceedsWithEmptyFile(): void
    {
        if (version_compare(\PHP_VERSION, '7.2.0', '<')) {
            $this->markTestSkipped('Test case requires PHP 7.2.');
        }

        $this->runPsalm(['--no-cache'], __DIR__ . '/' . '../fixtures/DestructiveAutoloader/', true);
    }
}
