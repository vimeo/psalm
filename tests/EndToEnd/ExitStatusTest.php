<?php

namespace Psalm\Tests\EndToEnd;

use PHPUnit\Framework\TestCase;
use Psalm\Internal\Cli\Psalm;

use function array_pop;
use function array_shift;
use function explode;
use function realpath;
use function strlen;
use function substr;
use function trim;

class ExitStatusTest extends TestCase
{
    use PsalmRunnerTrait;

    public function testAutoloaderExitStatus(): void
    {
        $this->assert(
            'Example output from failing autoloader',
            Psalm::getAutoloaderExitMessage(realpath($this->getFixturePath(). 'autoloader.php')),
        );
    }

    public function testPluginHookExitStatus(): void
    {
        $this->assert(
            'Example output from failing hook',
            Psalm::getUnexpectedExitMessage(),
        );
    }

    private function assert(string $expectedSTDOUT, string $expectedSTDERR): void
    {
        $output = $this->runPsalm(['--no-cache'], $this->getFixturePath(), true);

        $this->assertSame($this->getLines($expectedSTDOUT), $this->getLines($output['STDOUT']));
        $this->assertSame($this->getLines($expectedSTDERR), $this->handleSTDERR($this->getLines($output['STDERR'])));
        $this->assertSame(2, $output['CODE']);
    }

    private function getFixture(): string
    {
        return substr($this->getName(), strlen('test'));
    }

    private function getFixturePath(): string
    {
        $fixture = $this->getFixture();
        return __DIR__ . "/../fixtures/$fixture/";
    }

    /**
     * @return array<string>
     */
    private function getLines(string $output): array
    {
        $lines = explode("\n", $output);

        $newLines = [];
        foreach ($lines as $line) {
            // Trim any carriages returns on Windows
            $newLines[] = trim($line);
        }

        return $newLines;
    }

    /**
     * @param array<string> $lines
     */
    private function handleSTDERR(array $lines): array
    {
        $this->assertStringStartsWith('Target PHP version: ', array_shift($lines));
        $this->assertStringStartsWith('Scanning files...', array_shift($lines));

        if ($lines[0] === 'Analyzing files...') {
            array_shift($lines);
        }

        $this->assertSame('', array_shift($lines));
        $this->assertSame('', array_pop($lines));

        return $lines;
    }
}
