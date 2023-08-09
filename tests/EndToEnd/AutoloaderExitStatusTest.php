<?php

namespace Psalm\Tests\EndToEnd;

use PHPUnit\Framework\TestCase;

class AutoloaderExitStatusTest extends TestCase
{
    use PsalmRunnerTrait;

    public function testExitStatusWithAutoloaderZeroStatus(): void
    {
        $output = $this->runPsalm(['--no-cache'], __DIR__ . '/' . '../fixtures/AutoloaderExitStatus', true);

        $this->assertSame('Example output from failing autoloader', $output['STDOUT']);
        $this->assertStringEndsWith('The autoloader failed with the above output and a die() or exit() call' . PHP_EOL, $output['STDERR']);
        $this->assertSame(2, $output['CODE']);
    }
}
