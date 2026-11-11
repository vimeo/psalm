<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Cli\Psalm;
use Psalm\Tests\TestConfig;

use function extension_loaded;
use function function_exists;
use function max;

final class CliThreadsTest extends TestCase
{
    /**
     * @return iterable<string,array{int,array<string,mixed>,?int}>
     * @psalm-pure
     */
    public static function provideScanThreads(): iterable
    {
        yield '--threads applies to scan' => [3, ['threads' => '3'], null];
        yield '--scan-threads beats --threads' => [2, ['threads' => '3', 'scan-threads' => '2'], null];
        yield '--threads beats --debug' => [3, ['threads' => '3', 'debug' => false], null];
        yield '--threads beats config scanThreads' => [3, ['threads' => '3'], 5];
        yield 'config scanThreads' => [5, [], 5];
        yield '--debug beats config scanThreads' => [1, ['debug' => false], 5];
    }

    /**
     * @param array<string,mixed> $options
     */
    #[DataProvider('provideScanThreads')]
    public function testGetThreadsForScan(int $expected, array $options, ?int $config_scan_threads): void
    {
        $config = new TestConfig();
        $config->scan_threads = $config_scan_threads !== null ? max(1, $config_scan_threads) : null;

        $can_fork = extension_loaded('pcntl') && extension_loaded('posix') && function_exists('pcntl_fork');

        // without forking support Psalm runs single-threaded whatever the options say
        $this->assertSame($can_fork ? $expected : 1, Psalm::getThreads($options, $config, true));
    }
}
