<?php

declare(strict_types=1);

namespace Psalm\Tests\EndToEnd;

use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Symfony\Component\Process\Process;

use function array_column;
use function array_filter;
use function array_keys;
use function array_map;
use function array_sum;
use function array_unique;
use function array_values;
use function assert;
use function file_get_contents;
use function file_put_contents;
use function glob;
use function intdiv;
use function json_decode;
use function ksort;
use function max;
use function mkdir;
use function preg_match_all;
use function preg_replace;
use function rmdir;
use function rsort;
use function sort;
use function str_replace;
use function symlink;
use function unlink;

use const DIRECTORY_SEPARATOR;
use const JSON_THROW_ON_ERROR;
use const PHP_BINARY;

/**
 * Tests `--debug-performance[=PATH]` by launching a new process as if invoked by a real user.
 *
 * @psalm-type DumpFile = array{op: string, path: string, pid: int, start_us: int, wall_us: int}
 * @psalm-type Dump = array{
 *     schema: int,
 *     env: array{cache: string, options: list<string>},
 *     totals: array{wall_us: int, cpu_us: int, children_cpu_us: int, files_analyzed: int, php_peak_bytes: int},
 *     stages: list<array{name: string, wall_us: int, cpu_us: int}>,
 *     pools: list<array{role: string, workers: int, payload_bytes: array{sum: ?int}}>,
 *     workers: list<array{
 *         pool: int, pid: int, role: string, start_us: int, end_us: int, tasks: int, busy_us: int, cpu_us: int
 *     }>,
 *     files: list<DumpFile>,
 *     top: array{functions: list<array{id: string, path: string}>},
 *     hooks: list<array{handler: string, hook: string, origin: string, calls: int, wall_us: int}>,
 *     counts: array<string, int>,
 * }
 */
final class PsalmDebugPerformanceEndToEndTest extends PsalmEndToEndTestCase
{
    public function testThreadsOneDumpIsValidAndItsStagesAndFilesAddUp(): void
    {
        $this->setUpProject(3);
        $dump_path = self::$tmpDir . '/perf.json';

        $result = $this->runPsalm(
            ['--no-cache', '--no-progress', '--threads=1', '--debug-performance=' . $dump_path],
            self::$tmpDir,
            true,
        );
        $dump = $this->readDump($dump_path);

        $this->assertSame(2, $result['CODE']);
        $this->assertSame(1, $dump['schema']);
        $this->assertSame('none', $dump['env']['cache']);
        $this->assertContains('debug-performance', $dump['env']['options']);

        $this->assertSame(
            ['src/A0.php', 'src/A1.php', 'src/A2.php', 'src/FileWithErrors.php'],
            self::recordedPaths($dump, 'analysis'),
        );

        // invariant 3
        $this->assertSame($dump['totals']['wall_us'], array_sum(array_column($dump['stages'], 'wall_us')));
        $this->assertSame($dump['totals']['cpu_us'], array_sum(array_column($dump['stages'], 'cpu_us')));

        // invariant 5: one process, so the files take what the stage takes, bar the loop around them
        $analysis_us = array_sum(array_column(self::recordsOf($dump, 'analysis'), 'wall_us'));
        $stage_us = array_sum(array_map(
            static fn(array $stage): int => $stage['name'] === 'analysis.files' ? $stage['wall_us'] : 0,
            $dump['stages'],
        ));
        $this->assertLessThanOrEqual($stage_us, $analysis_us);
        $this->assertLessThan(intdiv($stage_us, 4) + 50_000, $stage_us - $analysis_us);

        $this->assertSame([], $dump['workers']);
        $this->assertSame(4, $dump['totals']['files_analyzed']);
        $this->assertGreaterThan(0, $dump['totals']['php_peak_bytes']);
    }

    #[RequiresPhpExtension('pcntl')]
    #[RequiresPhpExtension('posix')]
    public function testThreadsTwoRecordsEachWorkerAndEachFileOnce(): void
    {
        $this->setUpProject(6);
        $dump_path = self::$tmpDir . '/perf.json';

        $this->runPsalm(
            ['--no-cache', '--no-progress', '--threads=2', '--debug-performance=' . $dump_path],
            self::$tmpDir,
            true,
        );
        $dump = $this->readDump($dump_path);

        $analysis_paths = self::recordedPaths($dump, 'analysis');
        $this->assertCount(7, $analysis_paths);
        $this->assertSame($analysis_paths, array_values(array_unique($analysis_paths)));

        $workers = array_values(array_filter(
            $dump['workers'],
            static fn(array $worker): bool => $worker['role'] === 'analysis',
        ));
        $pids = array_column($workers, 'pid');
        $this->assertCount(2, $workers);
        $this->assertCount(2, array_unique($pids));
        $this->assertSame(7, array_sum(array_column($workers, 'tasks')));

        // each file was analysed in exactly one of the workers
        foreach (self::recordsOf($dump, 'analysis') as $record) {
            $this->assertContains($record['pid'], $pids);
        }

        // invariant 6
        foreach ($workers as $worker) {
            $this->assertLessThanOrEqual($worker['end_us'] - $worker['start_us'], $worker['busy_us']);

            // a worker ends with its last file, not when its result is asked for
            $own_ends = array_map(
                static fn(array $file): int => $file['start_us'] + $file['wall_us'],
                array_filter(self::recordsOf($dump, 'analysis'), static fn(array $file): bool => $file['pid'] === $worker['pid']),
            );
            $this->assertSame(max(0, ...$own_ends), $worker['end_us']);
        }

        $this->assertLessThanOrEqual(
            $dump['totals']['children_cpu_us'],
            array_sum(array_column($dump['workers'], 'cpu_us')),
        );
        $this->assertSame('analysis', $dump['pools'][0]['role']);
        $this->assertSame(2, $dump['pools'][0]['workers']);
        $this->assertGreaterThan(0, $dump['pools'][0]['payload_bytes']['sum']);
    }

    #[RequiresPhpExtension('pcntl')]
    #[RequiresPhpExtension('posix')]
    public function testStdoutAndExitCodeDoNotChangeWithTheOption(): void
    {
        $this->setUpProject(6);
        $dump_path = self::$tmpDir . '/perf.json';

        foreach (['--threads=1', '--threads=2'] as $threads) {
            $args = ['--no-cache', '--no-progress', '--output-format=json', $threads];

            $without = self::newProcess(
                [PHP_BINARY, $this->psalm, '-c=' . self::$tmpDir . '/psalm.xml', ...$args],
                null,
                self::agentEnvVarsToUnset(),
            );
            $without->run();
            $with = self::newProcess(
                [
                    PHP_BINARY,
                    $this->psalm,
                    '-c=' . self::$tmpDir . '/psalm.xml',
                    ...$args,
                    '--debug-performance=' . $dump_path,
                ],
                null,
                self::agentEnvVarsToUnset(),
            );
            $with->run();

            // an exit with errors: the dump is written before Psalm exits
            $this->assertSame(2, $without->getExitCode());
            $this->assertSame(2, $with->getExitCode());
            $this->assertStringContainsString('InvalidReturnType', $without->getOutput());
            $this->assertSame($without->getOutput(), $with->getOutput());
            $this->assertFileExists($dump_path);
            unlink($dump_path);
        }

        // an exit without errors
        unlink(self::$tmpDir . '/src/FileWithErrors.php');
        $clean_without = self::newProcess(
            [PHP_BINARY, $this->psalm, '-c=' . self::$tmpDir . '/psalm.xml', '--no-cache', '--output-format=json'],
            null,
            self::agentEnvVarsToUnset(),
        );
        $clean_without->run();
        $clean_with = self::newProcess(
            [
                PHP_BINARY,
                $this->psalm,
                '-c=' . self::$tmpDir . '/psalm.xml',
                '--no-cache',
                '--output-format=json',
                '--debug-performance=' . $dump_path,
            ],
            null,
            self::agentEnvVarsToUnset(),
        );
        $clean_with->run();

        $this->assertSame(0, $clean_without->getExitCode());
        $this->assertSame(0, $clean_with->getExitCode());
        $this->assertSame($clean_without->getOutput(), $clean_with->getOutput());
        $this->assertFileExists($dump_path);
    }

    public function testUnwritablePathWarnsAndKeepsTheExitCode(): void
    {
        $this->setUpProject(0);
        // a directory cannot be overwritten with a file
        $dump_path = self::$tmpDir . '/perf-dir';
        mkdir($dump_path);

        try {
            $result = $this->runPsalm(
                ['--no-cache', '--debug-performance=' . $dump_path],
                self::$tmpDir,
                true,
            );
        } finally {
            rmdir($dump_path);
        }

        $this->assertSame(2, $result['CODE']);
        $this->assertStringContainsString('Warning: Could not write the performance dump ', $result['STDERR']);
        $this->assertStringContainsString('perf-dir', $result['STDERR']);
        $this->assertStringNotContainsString('Performance dump written', $result['STDERR']);
        // the summary is there, but it doesn't point at a file that doesn't hold the data
        $this->assertStringContainsString("\nSlowest files by CPU\n", $result['STDOUT']);
        $this->assertStringNotContainsString('The full data is in', $result['STDOUT']);
    }

    public function testPathEqualToAReportPathExitsWithOne(): void
    {
        $this->setUpProject(0);
        $path = self::$tmpDir . '/report.json';

        $process = self::newProcess(
            [
                PHP_BINARY,
                $this->psalm,
                '-c=' . self::$tmpDir . '/psalm.xml',
                '--no-cache',
                '--report=' . $path,
                '--debug-performance=' . self::$tmpDir . '/src/../report.json',
            ],
            null,
            self::agentEnvVarsToUnset(),
        );
        $process->run();

        $this->assertSame(1, $process->getExitCode());
        $this->assertStringContainsString('would overwrite ' . $path, $process->getErrorOutput());
        $this->assertFileDoesNotExist($path);
    }

    public function testPathEqualToAReportPathThroughASymlinkedDirectoryExitsWithOne(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Creating symlinks needs a privilege on Windows');
        }

        $this->setUpProject(0);
        mkdir(self::$tmpDir . '/real');
        symlink(self::$tmpDir . '/real', self::$tmpDir . '/link');
        $report_path = self::$tmpDir . '/real/report.json';

        try {
            $process = self::newProcess(
                [
                    PHP_BINARY,
                    $this->psalm,
                    '-c=' . self::$tmpDir . '/psalm.xml',
                    '--no-cache',
                    '--report=' . $report_path,
                    '--debug-performance=' . self::$tmpDir . '/link/report.json',
                ],
                null,
                self::agentEnvVarsToUnset(),
            );
            $process->run();

            $this->assertSame(1, $process->getExitCode());
            $this->assertStringContainsString('would overwrite ' . $report_path, $process->getErrorOutput());
            $this->assertFileDoesNotExist($report_path);
        } finally {
            unlink(self::$tmpDir . '/link');
            rmdir(self::$tmpDir . '/real');
        }
    }

    public function testTheHandlersOfAPluginAreTimedAtEveryThreadCount(): void
    {
        $this->setUpProject(3);
        mkdir(self::$tmpDir . '/plugins');
        file_put_contents(
            self::$tmpDir . '/plugins/Plug.php',
            <<<'PHP'
            <?php
            namespace Acme\Plug;

            use Psalm\Plugin\EventHandler\AfterFileAnalysisInterface;
            use Psalm\Plugin\EventHandler\Event\AfterFileAnalysisEvent;
            use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
            use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
            use Psalm\Type\Union;

            final class TimedPlugin implements AfterFileAnalysisInterface, FunctionReturnTypeProviderInterface
            {
                public static function afterAnalyzeFile(AfterFileAnalysisEvent $event): void
                {
                }

                public static function getFunctionIds(): array
                {
                    return ['strlen'];
                }

                public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
                {
                    return null;
                }
            }
            PHP,
        );
        file_put_contents(self::$tmpDir . '/src/Calls.php', "<?php\nnamespace Acme\\Extra;\n\nfunction calls(): int\n{\n    return strlen('a') + strlen('b');\n}\n");
        $psalm_xml = (string) file_get_contents(self::$tmpDir . '/psalm.xml');
        file_put_contents(
            self::$tmpDir . '/psalm.xml',
            str_replace('</psalm>', '<plugins><plugin filename="plugins/Plug.php"/></plugins></psalm>', $psalm_xml),
        );
        $dump_path = self::$tmpDir . '/perf.json';

        foreach (['--threads=1', '--threads=2'] as $threads) {
            $this->runPsalm(['--no-cache', '--no-progress', $threads, '--debug-performance=' . $dump_path], self::$tmpDir, true);
            $dump = $this->readDump($dump_path);

            $calls = [];
            foreach ($dump['hooks'] as $hook) {
                if ($hook['handler'] === 'Acme\\Plug\\TimedPlugin') {
                    $this->assertSame('plugin', $hook['origin']);
                    $calls[$hook['hook']] = $hook['calls'];
                }
            }

            ksort($calls);
            $this->assertSame(['AfterFileAnalysis', 'FunctionReturnType'], array_keys($calls), $threads);
            // once for each file analysed, and once for each call of strlen() that the project analyses
            $this->assertCount($calls['AfterFileAnalysis'], self::recordsOf($dump, 'analysis'), $threads);
            $this->assertGreaterThanOrEqual(2, $calls['FunctionReturnType'], $threads);

            $origins = array_column($dump['hooks'], 'origin');
            $this->assertContains('psalm', $origins);
            // sorted by time, the longest first
            $times = array_column($dump['hooks'], 'wall_us');
            $sorted = $times;
            rsort($sorted);
            $this->assertSame($sorted, $times);
        }
    }

    public function testCountsGrowByWhatTheProjectDefines(): void
    {
        $this->setUpProject(0);
        $dump_path = self::$tmpDir . '/perf.json';

        $this->runPsalm(['--no-cache', '--no-progress', '--threads=1', '--debug-performance=' . $dump_path], self::$tmpDir, true);
        $before = $this->readDump($dump_path)['counts'];

        file_put_contents(
            self::$tmpDir . '/src/Defs.php',
            "<?php\nnamespace Acme\\Extra;\n\nclass Defs\n{\n    public function a(): void\n    {\n    }\n\n    public function b(): void\n    {\n    }\n\n"
            . "    public function c(): void\n    {\n    }\n}\n\nfunction one(): void\n{\n    \$a = function (): void {\n    };\n    \$b = fn(): int => 1;\n    \$a();\n    \$b();\n}\n\nfunction two(): void\n{\n}\n",
        );
        $this->runPsalm(['--no-cache', '--no-progress', '--threads=1', '--debug-performance=' . $dump_path], self::$tmpDir, true);
        $after = $this->readDump($dump_path)['counts'];

        $this->assertSame(
            ['classlikes' => 1, 'methods' => 3, 'functions' => 2, 'file_storages' => 1],
            [
                'classlikes' => $after['classlikes'] - $before['classlikes'],
                'methods' => $after['methods'] - $before['methods'],
                'functions' => $after['functions'] - $before['functions'],
                'file_storages' => $after['file_storages'] - $before['file_storages'],
            ],
        );
        $this->assertGreaterThan($before['files_parsed'], $after['files_parsed']);
    }

    public function testParserCountsFollowTheParserCache(): void
    {
        $this->setUpProject(3);
        $dump_path = self::$tmpDir . '/perf.json';

        $this->runPsalm(['--no-progress', '--threads=1', '--debug-performance=' . $dump_path], self::$tmpDir, true);
        $cold = $this->readDump($dump_path)['counts'];
        $this->runPsalm(['--no-progress', '--threads=1', '--debug-performance=' . $dump_path], self::$tmpDir, true);
        $warm = $this->readDump($dump_path)['counts'];

        $this->assertGreaterThan(0, $cold['parser_cache_misses']);
        $this->assertLessThan($cold['parser_cache_misses'], $warm['parser_cache_misses']);
        $this->assertLessThan($cold['files_parsed'], $warm['files_parsed']);
        $this->assertGreaterThan(0, $warm['parser_cache_hits']);
    }

    public function testNoRecordNamesTheDirectoryTheProjectIsIn(): void
    {
        $this->setUpProject(0);
        file_put_contents(
            self::$tmpDir . '/src/Anon.php',
            "<?php\nnamespace Acme\\Extra;\n\nfunction make(): object\n{\n"
            . "    return new class {\n        public function go(): int\n        {\n            return 1;\n        }\n    };\n}\n",
        );
        $dump_path = self::$tmpDir . '/perf.json';

        $this->runPsalm(['--no-cache', '--no-progress', '--threads=1', '--debug-performance=' . $dump_path], self::$tmpDir, true);
        $dump = $this->readDump($dump_path);

        $ids = array_column($dump['top']['functions'], 'id');
        $this->assertContains('{anonymous-class}::go', $ids);

        // the name of an anonymous class is made of the path of its file
        $mangled_root = str_replace(['/', '\\', '.', ':'], '_', self::$tmpDir);
        $json = (string) file_get_contents($dump_path);
        $this->assertStringNotContainsString($mangled_root, $json);
        $this->assertStringNotContainsString(self::$tmpDir, str_replace('\\/', '/', $json));

        // the stubs of Psalm are not named by the way up from the project to Psalm
        foreach ($dump['files'] as $file) {
            $this->assertStringStartsNotWith('..', $file['path']);
        }
    }

    public function testPathEqualToTheBaselinePathExitsWithOne(): void
    {
        $this->setUpProject(0);
        $path = self::$tmpDir . '/baseline.xml';

        $process = self::newProcess(
            [
                PHP_BINARY,
                $this->psalm,
                '-c=' . self::$tmpDir . '/psalm.xml',
                '--no-cache',
                '--set-baseline=' . $path,
                '--debug-performance=' . $path,
            ],
            null,
            self::agentEnvVarsToUnset(),
        );
        $process->run();

        $this->assertSame(1, $process->getExitCode());
        $this->assertStringContainsString('would overwrite ' . $path, $process->getErrorOutput());
        $this->assertFileDoesNotExist($path);
    }

    public function testWithoutAValuePrintsTheSummaryAndWritesNoFile(): void
    {
        $this->setUpProject(2);
        $files_before = glob(self::$tmpDir . '/*');
        assert($files_before !== false);

        $result = $this->runPsalm(['--no-cache', '--debug-performance'], self::$tmpDir, true);

        $this->assertStringContainsString("\nPerformance\n", $result['STDOUT']);
        $this->assertStringContainsString("\nSlowest files by CPU\n", $result['STDOUT']);
        $this->assertStringContainsString("\nHottest statements (own time)\n", $result['STDOUT']);
        $this->assertStringContainsString('Run with --debug-performance=PATH', $result['STDOUT']);
        $this->assertStringContainsString("\nPlugins and providers (time in handlers", $result['STDOUT']);
        $this->assertMatchesRegularExpression('/^Peak memory [\d.]+ MB\. Counts: \d+ class-likes, /m', $result['STDOUT']);
        // a stage that comes back is one row
        $this->assertSame(1, preg_match_all('/^  scan\.files /m', $result['STDOUT']));
        $this->assertSame(1, preg_match_all('/^  scan\.setup /m', $result['STDOUT']));
        $this->assertStringNotContainsString('Slow-to-analyze functions', $result['STDOUT']);
        $this->assertStringNotContainsString('Performance dump written', $result['STDERR']);
        $this->assertSame($files_before, glob(self::$tmpDir . '/*'));
    }

    public function testStatisticsOfANonConsoleFormatGoToStderr(): void
    {
        $this->setUpProject(2);
        $psalm_args = ['--no-cache', '--output-format=json', '--threads=1'];

        $plain = $this->runPsalmWithoutTheDefaultFormat($psalm_args);
        $with = $this->runPsalmWithoutTheDefaultFormat([...$psalm_args, '--stats', '--debug-performance']);

        $this->assertJson($with->getOutput());
        $this->assertSame($plain->getOutput(), $with->getOutput());
        $this->assertStringContainsString('Checks took', $with->getErrorOutput());
        $this->assertStringContainsString('Psalm was able to infer types for', $with->getErrorOutput());
        $this->assertStringContainsString("-----------------\n", $with->getErrorOutput());
        $this->assertStringContainsString("\nPerformance\n", $with->getErrorOutput());
        $this->assertStringContainsString("\nSlowest files by CPU\n", $with->getErrorOutput());
        $this->assertStringNotContainsString("\e[", $with->getErrorOutput());

        // any run prints the lines, with or without the options
        $this->assertStringContainsString('Checks took', $plain->getErrorOutput());
        $this->assertStringNotContainsString('Slowest files by CPU', $plain->getErrorOutput());

        $quiet = $this->runPsalmWithoutTheDefaultFormat([...$psalm_args, '--no-progress', '--stats', '--debug-performance']);

        $this->assertSame($plain->getOutput(), $quiet->getOutput());
        $this->assertSame('', $quiet->getErrorOutput());
    }

    /**
     * @param list<string> $args
     */
    private function runPsalmWithoutTheDefaultFormat(array $args): Process
    {
        $process = self::newProcess(
            [PHP_BINARY, $this->psalm, '-c=' . self::$tmpDir . '/psalm.xml', ...$args],
            null,
            self::agentEnvVarsToUnset(),
        );
        $process->run();
        $this->assertSame(2, $process->getExitCode(), $process->getErrorOutput());

        return $process;
    }

    public function testNoProgressKeepsStderrEmpty(): void
    {
        $this->runPsalmInit();

        $psalm_xml = file_get_contents(self::$tmpDir . '/psalm.xml');
        assert($psalm_xml !== false);
        $psalm_xml = (string) preg_replace('/findUnusedCode="(true|false)"/', 'runTaintAnalysis="false"', $psalm_xml, 1);
        file_put_contents(self::$tmpDir . '/psalm.xml', $psalm_xml);
        $dump_path = self::$tmpDir . '/perf.json';

        $result = $this->runPsalm(['--no-progress', '--debug-performance=' . $dump_path], self::$tmpDir);

        $this->assertSame('', $result['STDERR']);
        $this->assertFileExists($dump_path);
    }

    private function setUpProject(int $extra_files): void
    {
        $this->runPsalmInit(1);

        $psalm_xml = file_get_contents(self::$tmpDir . '/psalm.xml');
        file_put_contents(
            self::$tmpDir . '/psalm.xml',
            str_replace('<psalm', '<psalm runTaintAnalysis="false"', (string) $psalm_xml),
        );

        for ($i = 0; $i < $extra_files; ++$i) {
            file_put_contents(
                self::$tmpDir . '/src/A' . $i . '.php',
                "<?php\nnamespace Acme\\Extra;\n\nfunction a" . $i . "(int \$x): int\n{\n    return \$x + " . $i . ";\n}\n",
            );
        }
    }

    /**
     * @return Dump
     */
    private function readDump(string $path): array
    {
        /** @var Dump */
        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param Dump $dump
     * @return list<DumpFile>
     * @psalm-pure
     */
    private static function recordsOf(array $dump, string $op): array
    {
        return array_values(array_filter(
            $dump['files'],
            static fn(array $file): bool => $file['op'] === $op,
        ));
    }

    /**
     * @param Dump $dump
     * @return list<string>
     * @psalm-pure
     */
    private static function recordedPaths(array $dump, string $op): array
    {
        $paths = array_column(self::recordsOf($dump, $op), 'path');
        sort($paths);

        return $paths;
    }
}
