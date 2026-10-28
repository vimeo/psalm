<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal;

use Override;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Cli\Psalm;
use Psalm\Tests\TestConfig;

use function extension_loaded;
use function max;

final class CliThreadsTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('pcntl')) {
            $this->markTestSkipped('getThreads() always returns 1 without pcntl');
        }
    }

    /** @return iterable<string,array{int,array<string,mixed>,?int,bool}> */
    public function provideScanThreads(): iterable
    {
        yield '--threads applies to scan' => [3, ['threads' => '3'], null, false];
        yield '--scan-threads beats --threads' => [2, ['threads' => '3', 'scan-threads' => '2'], null, false];
        yield '--threads beats CI default' => [3, ['threads' => '3'], null, true];
        yield '--threads beats --debug' => [3, ['threads' => '3', 'debug' => false], null, false];
        yield '--threads beats config scanThreads' => [3, ['threads' => '3'], 5, false];
        yield 'config scanThreads' => [5, [], 5, false];
        yield '--debug beats config scanThreads' => [1, ['debug' => false], 5, false];
    }

    /**
     * @dataProvider provideScanThreads
     * @param array<string,mixed> $options
     */
    public function testGetThreadsForScan(int $expected, array $options, ?int $config_scan_threads, bool $in_ci): void
    {
        $config = new TestConfig();
        $config->scan_threads = $config_scan_threads !== null ? max(1, $config_scan_threads) : null;

        $this->assertSame($expected, Psalm::getThreads($options, $config, $in_ci, true));
    }
}
