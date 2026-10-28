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
use function strtolower;
use function trim;

use const PHP_OS_FAMILY;

/**
 * Picks the thread count used when the user did not ask for one.
 *
 * On hybrid CPUs (Apple Silicon, Intel Alder Lake and newer) the efficiency cores are not counted:
 * they are slower, so a worker that ends up on one of them drags out the tail of the run,
 * while the extra threads add CPU time without finishing earlier.
 *
 * @internal
 */
final class DefaultThreadCount
{
    // Apple Silicon has at most three perf levels today; more than this and the topology is treated as unknown
    private const DARWIN_MAX_PERF_LEVELS = 4;

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
        return self::select($all_cores, self::getNonEfficiencyCoreCount($read_command, $read_file, $os_family));
    }

    /**
     * @param ?int $non_efficiency_cores null when the CPU is not hybrid or the topology could not be read
     * @return int<1, max>
     * @psalm-pure
     */
    public static function select(int $all_cores, ?int $non_efficiency_cores): int
    {
        $all_cores = max(1, $all_cores);

        if ($non_efficiency_cores === null || $non_efficiency_cores < 1) {
            return $all_cores;
        }

        return min($all_cores, $non_efficiency_cores);
    }

    /**
     * @param Closure(string): ?string $read_command
     * @param Closure(string): ?string $read_file
     * @psalm-impure
     */
    public static function getNonEfficiencyCoreCount(
        Closure $read_command,
        Closure $read_file,
        string $os_family,
    ): ?int {
        if ($os_family === 'Darwin') {
            return self::getDarwinNonEfficiencyCoreCount($read_command);
        }

        if ($os_family === 'Linux') {
            return self::getLinuxNonEfficiencyCoreCount($read_file);
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
     * Sums the logical CPUs of every perf level that is not named "Efficiency". Apple Silicon names its levels
     * (Performance/Efficiency, and "Super" on parts such as the M5 Pro that have no efficiency cores), so the
     * number of levels alone does not tell whether a CPU is hybrid.
     *
     * @param Closure(string): ?string $read_command
     * @psalm-impure
     */
    private static function getDarwinNonEfficiencyCoreCount(Closure $read_command): ?int
    {
        $keys = ['hw.nperflevels'];
        for ($level = 0; $level < self::DARWIN_MAX_PERF_LEVELS; $level++) {
            $keys[] = "hw.perflevel$level.name";
            $keys[] = "hw.perflevel$level.logicalcpu";
        }

        // levels that do not exist make sysctl exit non-zero while the others are still printed
        $output = $read_command('/usr/sbin/sysctl ' . implode(' ', $keys) . ' 2>/dev/null || true');
        if ($output === null) {
            return null;
        }

        $values = [];
        foreach (explode("\n", trim($output)) as $line) {
            $parts = explode(': ', $line, 2);
            if (count($parts) === 2) {
                $values[$parts[0]] = $parts[1];
            }
        }

        $levels = $values['hw.nperflevels'] ?? null;
        if ($levels === null || !ctype_digit($levels) || (int) $levels > self::DARWIN_MAX_PERF_LEVELS) {
            return null;
        }

        $has_efficiency_level = false;
        $cores = 0;
        for ($level = 0; $level < (int) $levels; $level++) {
            $name = $values["hw.perflevel$level.name"] ?? null;
            $logical_cpus = $values["hw.perflevel$level.logicalcpu"] ?? null;
            if ($name === null || $logical_cpus === null || !ctype_digit($logical_cpus)) {
                return null;
            }

            if (strtolower($name) === 'efficiency') {
                $has_efficiency_level = true;
            } else {
                $cores += (int) $logical_cpus;
            }
        }

        return $has_efficiency_level ? $cores : null;
    }

    /**
     * @param Closure(string): ?string $read_file
     * @psalm-impure
     */
    private static function getLinuxNonEfficiencyCoreCount(Closure $read_file): ?int
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
