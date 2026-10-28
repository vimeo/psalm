<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Cli\DefaultThreadCount;

use function count;

final class DefaultThreadCountTest extends TestCase
{
    private const SYSCTL = '/usr/sbin/sysctl hw.nperflevels hw.perflevel0.name hw.perflevel0.logicalcpu hw.perflevel1.name hw.perflevel1.logicalcpu hw.perflevel2.name hw.perflevel2.logicalcpu hw.perflevel3.name hw.perflevel3.logicalcpu 2>/dev/null || true';

    /**
     * Mimics `sysctl` output: one "key: value" line per existing key, nothing for missing levels.
     *
     * @param list<array{string,int}> $levels name and logical CPU count of each perf level
     * @psalm-pure
     */
    private static function sysctlOutput(array $levels): string
    {
        $output = 'hw.nperflevels: ' . count($levels) . "\n";
        foreach ($levels as $index => [$name, $cpus]) {
            $output .= "hw.perflevel$index.name: $name\nhw.perflevel$index.logicalcpu: $cpus\n";
        }

        return $output;
    }
    /**
     * @return iterable<string,array{?int,string}>
     * @psalm-pure
     */
    public static function provideCpuLists(): iterable
    {
        yield 'single cpu' => [1, '3'];
        yield 'range' => [16, '0-15'];
        yield 'two-cpu range' => [2, '4-5'];
        yield 'mixed' => [7, '0-3,8,10-11'];
        yield 'trailing newline' => [16, "0-15\n"];
        yield 'surrounding whitespace' => [4, " 0-3 \n"];
        yield 'empty' => [null, ''];
        yield 'only whitespace' => [null, " \n"];
        yield 'reversed range' => [null, '5-2'];
        yield 'garbage' => [null, 'abc'];
        yield 'partial garbage' => [null, '0-3,x'];
        yield 'trailing comma' => [null, '0-3,'];
        yield 'leading comma' => [null, ',0-3'];
        yield 'open range' => [null, '0-'];
        yield 'negative' => [null, '-3'];
        yield 'inner whitespace' => [null, '0, 1'];
        yield 'too many bounds' => [null, '0-1-2'];
        yield 'bad range bound' => [null, '0-x'];
        yield 'newline inside list' => [null, "0-3\n,5"];
        yield 'trailing garbage after valid' => [null, "0-3\nfoo"];
    }

    #[DataProvider('provideCpuLists')]
    public function testParseCpuList(?int $expected, string $list): void
    {
        $this->assertSame($expected, DefaultThreadCount::parseCpuList($list));
    }

    /**
     * @return iterable<string,array{int,int,?int}>
     * @psalm-pure
     */
    public static function provideSelection(): iterable
    {
        yield 'hybrid skips efficiency cores' => [12, 16, 12];
        yield 'not hybrid' => [16, 16, null];
        yield 'zero non-efficiency cores is ignored' => [16, 16, 0];
        yield 'negative non-efficiency cores is ignored' => [16, 16, -1];
        yield 'one non-efficiency core' => [1, 16, 1];
        yield 'non-efficiency cores capped by affinity-limited total' => [4, 4, 12];
        yield 'all cores non-efficiency' => [8, 8, 8];
        yield 'no cores detected' => [1, 0, null];
        yield 'no cores detected, hybrid' => [1, 0, 12];
    }

    #[DataProvider('provideSelection')]
    public function testSelect(int $expected, int $all_cores, ?int $non_efficiency_cores): void
    {
        $this->assertSame($expected, DefaultThreadCount::select($all_cores, $non_efficiency_cores));
    }

    /**
     * @return iterable<string,array{?int,string,array<string,string>,array<string,string>}>
     * @psalm-pure
     */
    public static function provideNonEfficiencyCores(): iterable
    {
        $sysctl = self::SYSCTL;

        yield 'apple silicon performance and efficiency' => [
            12,
            'Darwin',
            [$sysctl => self::sysctlOutput([['Performance', 12], ['Efficiency', 4]])],
            [],
        ];
        yield 'apple silicon super and performance, no efficiency cores' => [
            null,
            'Darwin',
            [$sysctl => self::sysctlOutput([['Super', 6], ['Performance', 12]])],
            [],
        ];
        yield 'apple silicon three levels, efficiency last' => [
            10,
            'Darwin',
            [$sysctl => self::sysctlOutput([['Super', 4], ['Performance', 6], ['Efficiency', 4]])],
            [],
        ];
        yield 'apple silicon efficiency name is case-insensitive' => [
            12,
            'Darwin',
            [$sysctl => self::sysctlOutput([['Performance', 12], ['efficiency', 4]])],
            [],
        ];
        yield 'apple silicon efficiency level listed first' => [
            12,
            'Darwin',
            [$sysctl => self::sysctlOutput([['Efficiency', 4], ['Performance', 12]])],
            [],
        ];
        yield 'apple silicon unknown names' => [
            null,
            'Darwin',
            [$sysctl => self::sysctlOutput([['Foo', 6], ['Bar', 12]])],
            [],
        ];
        yield 'apple silicon unknown names next to efficiency' => [
            6,
            'Darwin',
            [$sysctl => self::sysctlOutput([['Foo', 6], ['Efficiency', 4]])],
            [],
        ];
        yield 'apple silicon single level' => [
            null,
            'Darwin',
            [$sysctl => self::sysctlOutput([['Performance', 8]])],
            [],
        ];
        yield 'apple silicon only efficiency cores' => [
            null,
            'Darwin',
            [$sysctl => self::sysctlOutput([['Efficiency', 8]])],
            [],
        ];
        yield 'apple silicon 4P+4E keeps all cores' => [
            null,
            'Darwin',
            [$sysctl => self::sysctlOutput([['Performance', 4], ['Efficiency', 4]])],
            [],
        ];
        yield 'apple silicon 4P+6E keeps all cores' => [
            null,
            'Darwin',
            [$sysctl => self::sysctlOutput([['Performance', 4], ['Efficiency', 6]])],
            [],
        ];
        yield 'apple silicon one efficiency core fewer than the rest' => [
            5,
            'Darwin',
            [$sysctl => self::sysctlOutput([['Performance', 5], ['Efficiency', 4]])],
            [],
        ];
        yield 'apple silicon zero-cpu efficiency level' => [
            null,
            'Darwin',
            [$sysctl => self::sysctlOutput([['Performance', 8], ['Efficiency', 0]])],
            [],
        ];
        yield 'apple silicon more levels than queried' => [
            null,
            'Darwin',
            [$sysctl => "hw.nperflevels: 5\nhw.perflevel0.name: Performance\nhw.perflevel0.logicalcpu: 12\n"],
            [],
        ];
        yield 'apple silicon missing level name' => [
            null,
            'Darwin',
            [$sysctl => "hw.nperflevels: 2\nhw.perflevel0.logicalcpu: 12\nhw.perflevel1.name: Efficiency\n"
                . "hw.perflevel1.logicalcpu: 4\n"],
            [],
        ];
        yield 'apple silicon missing level cpu count' => [
            null,
            'Darwin',
            [$sysctl => "hw.nperflevels: 2\nhw.perflevel0.name: Performance\nhw.perflevel1.name: Efficiency\n"
                . "hw.perflevel1.logicalcpu: 4\n"],
            [],
        ];
        yield 'apple silicon non-numeric cpu count' => [
            null,
            'Darwin',
            [$sysctl => "hw.nperflevels: 2\nhw.perflevel0.name: Performance\nhw.perflevel0.logicalcpu: x\n"
                . "hw.perflevel1.name: Efficiency\nhw.perflevel1.logicalcpu: 4\n"],
            [],
        ];
        yield 'apple silicon non-numeric level count' => [
            null,
            'Darwin',
            [$sysctl => "hw.nperflevels: two\nhw.perflevel0.name: Performance\nhw.perflevel0.logicalcpu: 12\n"],
            [],
        ];
        yield 'intel mac, keys missing' => [null, 'Darwin', [$sysctl => ''], []];
        yield 'darwin garbage output' => [null, 'Darwin', [$sysctl => "x\ny\n"], []];
        yield 'darwin command fails' => [null, 'Darwin', [], []];
        yield 'darwin ignores sysfs' => [
            null,
            'Darwin',
            [],
            ['/sys/devices/cpu_core/cpus' => '0-15', '/sys/devices/cpu_atom/cpus' => '16-23'],
        ];
        yield 'linux intel hybrid' => [
            16,
            'Linux',
            [],
            ['/sys/devices/cpu_core/cpus' => "0-15\n", '/sys/devices/cpu_atom/cpus' => "16-23\n"],
        ];
        yield 'linux 2P(HT)+8E keeps all cores' => [
            null,
            'Linux',
            [],
            ['/sys/devices/cpu_core/cpus' => '0-3', '/sys/devices/cpu_atom/cpus' => '4-11'],
        ];
        yield 'linux 8P(HT)+16E keeps all cores' => [
            null,
            'Linux',
            [],
            ['/sys/devices/cpu_core/cpus' => '0-15', '/sys/devices/cpu_atom/cpus' => '16-31'],
        ];
        yield 'linux efficiency cores one fewer than the rest' => [
            16,
            'Linux',
            [],
            ['/sys/devices/cpu_core/cpus' => '0-15', '/sys/devices/cpu_atom/cpus' => '16-30'],
        ];
        yield 'linux cpu_core without cpu_atom' => [
            null,
            'Linux',
            [],
            ['/sys/devices/cpu_core/cpus' => '0-15'],
        ];
        yield 'linux empty cpu_atom' => [
            null,
            'Linux',
            [],
            ['/sys/devices/cpu_core/cpus' => '0-15', '/sys/devices/cpu_atom/cpus' => "\n"],
        ];
        yield 'linux cpu_atom without cpu_core' => [
            null,
            'Linux',
            [],
            ['/sys/devices/cpu_atom/cpus' => '16-23'],
        ];
        yield 'linux malformed cpu_core' => [
            null,
            'Linux',
            [],
            ['/sys/devices/cpu_core/cpus' => 'nope', '/sys/devices/cpu_atom/cpus' => '16-23'],
        ];
        yield 'linux ignores sysctl' => [
            null,
            'Linux',
            [$sysctl => self::sysctlOutput([['Performance', 12], ['Efficiency', 4]])],
            [],
        ];
        yield 'other os' => [
            null,
            'Windows',
            [$sysctl => self::sysctlOutput([['Performance', 12], ['Efficiency', 4]])],
            ['/sys/devices/cpu_core/cpus' => '0-15', '/sys/devices/cpu_atom/cpus' => '16-23'],
        ];
    }

    /**
     * @param array<string,string> $commands
     * @param array<string,string> $files
     */
    #[DataProvider('provideNonEfficiencyCores')]
    public function testGetNonEfficiencyCoreCount(?int $expected, string $os_family, array $commands, array $files): void
    {
        $this->assertSame(
            $expected,
            DefaultThreadCount::getNonEfficiencyCoreCount(
                static fn(string $command): ?string => $commands[$command] ?? null,
                static fn(string $path): ?string => $files[$path] ?? null,
                $os_family,
            ),
        );
    }

    /**
     * @return iterable<string,array{int,int,string,array<string,string>,array<string,string>}>
     * @psalm-pure
     */
    public static function provideResolve(): iterable
    {
        $sysctl = self::SYSCTL;
        $hybrid_sysfs = ['/sys/devices/cpu_core/cpus' => '0-15', '/sys/devices/cpu_atom/cpus' => '16-23'];

        $p_and_e = self::sysctlOutput([['Performance', 12], ['Efficiency', 4]]);
        $super_and_p = self::sysctlOutput([['Super', 6], ['Performance', 12]]);

        yield 'apple silicon hybrid' => [12, 16, 'Darwin', [$sysctl => $p_and_e], []];
        yield 'apple silicon without efficiency cores' => [18, 18, 'Darwin', [$sysctl => $super_and_p], []];
        yield 'apple silicon 4P+4E' => [
            8,
            8,
            'Darwin',
            [$sysctl => self::sysctlOutput([['Performance', 4], ['Efficiency', 4]])],
            [],
        ];
        yield 'apple silicon 4P+6E' => [
            10,
            10,
            'Darwin',
            [$sysctl => self::sysctlOutput([['Performance', 4], ['Efficiency', 6]])],
            [],
        ];
        yield 'linux 2P(HT)+8E' => [
            12,
            12,
            'Linux',
            [],
            ['/sys/devices/cpu_core/cpus' => '0-3', '/sys/devices/cpu_atom/cpus' => '4-11'],
        ];
        yield 'apple silicon, sysctl unavailable' => [16, 16, 'Darwin', [], []];
        yield 'apple silicon, non-hybrid' => [8, 8, 'Darwin', [$sysctl => self::sysctlOutput([['Performance', 8]])], []];
        yield 'linux hybrid' => [16, 24, 'Linux', [], $hybrid_sysfs];
        yield 'linux hybrid limited by affinity' => [4, 4, 'Linux', [], $hybrid_sysfs];
        yield 'linux, sysfs unreadable' => [24, 24, 'Linux', [], []];
        yield 'unknown os' => [6, 6, 'Windows', [$sysctl => $p_and_e], $hybrid_sysfs];
        yield 'no cores detected' => [1, 0, 'Darwin', [$sysctl => $p_and_e], []];
    }

    /**
     * @param array<string,string> $commands
     * @param array<string,string> $files
     */
    #[DataProvider('provideResolve')]
    public function testResolve(int $expected, int $all_cores, string $os_family, array $commands, array $files): void
    {
        $this->assertSame(
            $expected,
            DefaultThreadCount::resolve(
                $all_cores,
                static fn(string $command): ?string => $commands[$command] ?? null,
                static fn(string $path): ?string => $files[$path] ?? null,
                $os_family,
            ),
        );
    }
}
