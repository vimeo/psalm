<?php

declare(strict_types=1);

namespace Psalm\Internal\Cli;

use Closure;
use Fidry\CpuCoreCounter\CpuCoreCounter;
use Psalm\Internal\ExecutionEnvironment\SystemCommandExecutor;
use RuntimeException;

use function array_filter;
use function array_map;
use function count;
use function ctype_digit;
use function explode;
use function file_get_contents;
use function implode;
use function intval;
use function max;
use function min;
use function trim;

use const PHP_OS_FAMILY;

/**
 * Picks the thread count used when the user did not ask for one.
 *
 * On hybrid CPUs (Apple Silicon, Intel Alder Lake and newer) only the performance cores are counted:
 * the efficiency cores are slower, so a worker that ends up on one of them drags out the tail of the run,
 * while the extra threads add CPU time without finishing earlier.
 *
 * @internal
 */
final class DefaultThreadCount
{
    /**
     * @return int<1, max>
     */
    public static function get(): int
    {
        return self::resolve(
            (new CpuCoreCounter())->getCountWithFallback(1),
            self::shellCommandReader(),
            self::fileReader(),
            PHP_OS_FAMILY,
        );
    }

    /**
     * @param Closure(string): ?string $read_command runs a shell command, returns its stdout or null on failure
     * @param Closure(string): ?string $read_file returns the contents of a file or null if it can't be read
     * @return int<1, max>
     * @psalm-impure
     */
    public static function resolve(
        int $all_cores,
        Closure $read_command,
        Closure $read_file,
        string $os_family,
    ): int {
        return self::select($all_cores, self::getPerformanceCoreCount($read_command, $read_file, $os_family));
    }

    /**
     * @param ?int $performance_cores null when the CPU is not hybrid or the topology could not be read
     * @return int<1, max>
     * @psalm-pure
     */
    public static function select(int $all_cores, ?int $performance_cores): int
    {
        $all_cores = max(1, $all_cores);

        if ($performance_cores === null || $performance_cores < 1) {
            return $all_cores;
        }

        return min($all_cores, $performance_cores);
    }

    /**
     * @param Closure(string): ?string $read_command
     * @param Closure(string): ?string $read_file
     * @psalm-impure
     */
    public static function getPerformanceCoreCount(
        Closure $read_command,
        Closure $read_file,
        string $os_family,
    ): ?int {
        if ($os_family === 'Darwin') {
            return self::getDarwinPerformanceCoreCount($read_command);
        }

        if ($os_family === 'Linux') {
            return self::getLinuxPerformanceCoreCount($read_file);
        }

        return null;
    }

    /**
     * Counts the CPUs in a kernel cpu list such as `0-7,16,18-19`.
     *
     * @return ?int null if the list is empty or malformed
     * @psalm-pure
     */
    public static function parseCpuList(string $list): ?int
    {
        $count = 0;
        foreach (explode(',', trim($list)) as $part) {
            $bounds = explode('-', $part);
            if (count($bounds) > 2 || $bounds !== array_filter($bounds, ctype_digit(...))) {
                return null;
            }

            $bounds = array_map(intval(...), $bounds);
            $first = $bounds[0];
            $last = $bounds[1] ?? $first;
            if ($last < $first) {
                return null;
            }

            $count += $last - $first + 1;
        }

        return $count;
    }

    /**
     * @param Closure(string): ?string $read_command
     * @psalm-impure
     */
    private static function getDarwinPerformanceCoreCount(Closure $read_command): ?int
    {
        $output = $read_command('/usr/sbin/sysctl -n hw.nperflevels hw.perflevel0.logicalcpu 2>/dev/null');
        if ($output === null) {
            return null;
        }

        $lines = explode("\n", trim($output));
        if (count($lines) !== 2 || $lines !== array_filter($lines, ctype_digit(...))) {
            return null;
        }

        [$levels, $performance_cores] = array_map(intval(...), $lines);

        // perflevel0 is the highest-performance level; a single level means the CPU is not hybrid
        return $levels > 1 ? $performance_cores : null;
    }

    /**
     * @param Closure(string): ?string $read_file
     * @psalm-impure
     */
    private static function getLinuxPerformanceCoreCount(Closure $read_file): ?int
    {
        $performance = $read_file('/sys/devices/cpu_core/cpus');
        $efficiency = $read_file('/sys/devices/cpu_atom/cpus');
        if ($performance === null || $efficiency === null) {
            return null;
        }

        // cpu_core alone also exists on CPUs without efficiency cores; only a non-empty cpu_atom means hybrid
        if (self::parseCpuList($efficiency) === null) {
            return null;
        }

        return self::parseCpuList($performance);
    }

    /**
     * @return Closure(string): ?string
     */
    private static function shellCommandReader(): Closure
    {
        return static function (string $command): ?string {
            try {
                return implode("\n", (new SystemCommandExecutor())->execute($command));
            } catch (RuntimeException) {
                return null;
            }
        };
    }

    /**
     * @return Closure(string): ?string
     */
    private static function fileReader(): Closure
    {
        return static function (string $path): ?string {
            $contents = @file_get_contents($path);

            return $contents === false ? null : $contents;
        };
    }
}
