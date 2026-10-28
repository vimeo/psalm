<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Cli\DefaultThreadCount;

final class DefaultThreadCountTest extends TestCase
{
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
        yield 'hybrid uses performance cores' => [12, 16, 12];
        yield 'not hybrid' => [16, 16, null];
        yield 'zero performance cores is ignored' => [16, 16, 0];
        yield 'negative performance cores is ignored' => [16, 16, -1];
        yield 'one performance core' => [1, 16, 1];
        yield 'performance cores capped by affinity-limited total' => [4, 4, 12];
        yield 'all cores performance' => [8, 8, 8];
        yield 'no cores detected' => [1, 0, null];
        yield 'no cores detected, hybrid' => [1, 0, 12];
    }

    #[DataProvider('provideSelection')]
    public function testSelect(int $expected, int $all_cores, ?int $performance_cores): void
    {
        $this->assertSame($expected, DefaultThreadCount::select($all_cores, $performance_cores));
    }

    /**
     * @return iterable<string,array{?int,string,array<string,string>,array<string,string>}>
     * @psalm-pure
     */
    public static function providePerformanceCores(): iterable
    {
        $sysctl = '/usr/sbin/sysctl -n hw.nperflevels hw.perflevel0.logicalcpu 2>/dev/null';

        yield 'apple silicon hybrid' => [12, 'Darwin', [$sysctl => "2\n12\n"], []];
        yield 'apple silicon without newline' => [10, 'Darwin', [$sysctl => '2' . "\n" . '10'], []];
        yield 'apple silicon single level' => [null, 'Darwin', [$sysctl => "1\n8\n"], []];
        yield 'apple silicon three levels' => [6, 'Darwin', [$sysctl => "3\n6\n"], []];
        yield 'intel mac, keys missing' => [null, 'Darwin', [$sysctl => ''], []];
        yield 'intel mac, only one key printed' => [null, 'Darwin', [$sysctl => "8\n"], []];
        yield 'darwin bad first line' => [null, 'Darwin', [$sysctl => "x\n12\n"], []];
        yield 'darwin bad second line' => [null, 'Darwin', [$sysctl => "2\ny\n"], []];
        yield 'darwin garbage output' => [null, 'Darwin', [$sysctl => "x\ny\n"], []];
        yield 'darwin extra output' => [null, 'Darwin', [$sysctl => "2\n12\n1\n"], []];
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
        yield 'linux ignores sysctl' => [null, 'Linux', [$sysctl => "2\n12\n"], []];
        yield 'other os' => [
            null,
            'Windows',
            [$sysctl => "2\n12\n"],
            ['/sys/devices/cpu_core/cpus' => '0-15', '/sys/devices/cpu_atom/cpus' => '16-23'],
        ];
    }

    /**
     * @param array<string,string> $commands
     * @param array<string,string> $files
     */
    #[DataProvider('providePerformanceCores')]
    public function testGetPerformanceCoreCount(?int $expected, string $os_family, array $commands, array $files): void
    {
        $this->assertSame(
            $expected,
            DefaultThreadCount::getPerformanceCoreCount(
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
        $sysctl = '/usr/sbin/sysctl -n hw.nperflevels hw.perflevel0.logicalcpu 2>/dev/null';
        $hybrid_sysfs = ['/sys/devices/cpu_core/cpus' => '0-15', '/sys/devices/cpu_atom/cpus' => '16-23'];

        yield 'apple silicon hybrid' => [12, 16, 'Darwin', [$sysctl => "2\n12\n"], []];
        yield 'apple silicon, sysctl unavailable' => [16, 16, 'Darwin', [], []];
        yield 'apple silicon, non-hybrid' => [8, 8, 'Darwin', [$sysctl => "1\n8\n"], []];
        yield 'linux hybrid' => [16, 24, 'Linux', [], $hybrid_sysfs];
        yield 'linux hybrid limited by affinity' => [4, 4, 'Linux', [], $hybrid_sysfs];
        yield 'linux, sysfs unreadable' => [24, 24, 'Linux', [], []];
        yield 'unknown os' => [6, 6, 'Windows', [$sysctl => "2\n12\n"], $hybrid_sysfs];
        yield 'no cores detected' => [1, 0, 'Darwin', [$sysctl => "2\n12\n"], []];
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
