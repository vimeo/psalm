<?php
namespace Psalm\Tests\EndToEnd;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class SuicidalAutoloaderTest extends TestCase
{
    use PsalmRunnerTrait;

    public function testSucceedsWithEmptyFile(): void
    {
        $this->runPsalm(['--no-cache'], __DIR__ . '/' . '../fixtures/SuicidalAutoloader/');
    }
}
