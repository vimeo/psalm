<?php

declare(strict_types=1);

namespace Psalm\Tests\EndToEnd;

use Override;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Symfony\Component\Process\Process;

use function array_column;
use function array_diff;
use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function array_sum;
use function array_unique;
use function array_values;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function glob;
use function implode;
use function json_decode;
use function ksort;
use function mkdir;
use function preg_match;
use function preg_match_all;
use function realpath;
use function rtrim;
use function sort;
use function str_repeat;
use function str_replace;
use function str_starts_with;
use function strpos;
use function trim;
use function unlink;

use const JSON_THROW_ON_ERROR;
use const PHP_BINARY;

/**
 * Tests `--debug-performance[=PATH]` by launching a new process as if invoked by a real user.
 *
 * @psalm-type DumpFile = array{op: string, path: string, pid: int, start_us: int, wall_us: int, size_bytes?: int}
 * @psalm-type Dump = array{
 *     schema: int,
 *     totals: array{wall_us: int, cpu_us: int, children_cpu_us: int},
 *     stages: list<array{name: string, start_us: int, wall_us: int, cpu_us: int}>,
 *     steps: list<array{name: string, stage: string, start_us: int, wall_us: int}>,
 *     pools: list<array{role: string, cpu_us: int, payload_bytes?: int, workers: list<array{pid: int, start_us: int, end_us: int, tasks: int, busy_us: int, cpu_us: int}>}>,
 *     parser: array{parses: int, cache_hits: int},
 *     files: list<DumpFile>,
 *     hooks: list<array{handler: string, hook: string, origin: string, calls: int, wall_us: int}>,
 *     counts: array<string, int>,
 *     plugins: list<array{class: string, init_us: int}>,
 * }
 */
final class PsalmDebugPerformanceEndToEndTest extends PsalmEndToEndTestCase
{
    #[Override]
    public function tearDown(): void
    {
        // a profile left by this test would let assertFileExists() of the next one pass
        @unlink(self::$tmpDir . '/perf.json');
        parent::tearDown();
    }

    public function testThreadsOneDumpIsValidAndItsStagesAndFilesAddUp(): void
    {
        $this->setUpProject(3);
        // a method that takes 1 ms to analyse is recorded; the name of an anonymous class holds the path of its file
        file_put_contents(
            self::$tmpDir . '/src/Anon.php',
            "<?php\nnamespace Acme\\Extra;\n\nfunction make(): object\n{\n    return new class {\n"
            . "        public function go(): int\n        {\n            \$a = 1;\n"
            . str_repeat("            \$a = \$a + 1;\n", 800)
            . "            return \$a;\n        }\n    };\n}\n",
        );
        $dump_path = self::$tmpDir . '/perf.json';

        $result = $this->runPsalm(
            ['--no-cache', '--no-progress', '--threads=1', '--debug-performance=' . $dump_path],
            self::$tmpDir,
            true,
        );
        $dump = $this->readDump($dump_path);
        $json = str_replace('\\/', '/', (string) file_get_contents($dump_path));

        $this->assertSame(2, $result['CODE']);
        $this->assertSame('', $result['STDERR']);
        $this->assertSame(1, $dump['schema']);
        // the number of scan rounds depends on the PHP version (stubs are preloaded or not), so only the shape is pinned
        $this->assertMatchesRegularExpression(
            '/^bootstrap( scan\.setup scan\.files scan\.populate)+ scan\.setup'
            . ' analysis\.setup analysis\.files analysis\.finish post report$/',
            implode(' ', array_column($dump['stages'], 'name')),
        );
        $this->assertSame(
            ['src/A0.php', 'src/A1.php', 'src/A2.php', 'src/Anon.php', 'src/FileWithErrors.php'],
            self::recordedPaths($dump, 'analysis'),
        );
        // a scan record has the size of the file it read
        $scanned = array_column(self::recordsOf($dump, 'scan'), 'size_bytes', 'path');
        $this->assertGreaterThan(0, $scanned['src/A0.php'] ?? 0);

        // the stages add up to the run
        $this->assertSame($dump['totals']['wall_us'], array_sum(array_column($dump['stages'], 'wall_us')));
        $this->assertSame($dump['totals']['cpu_us'], array_sum(array_column($dump['stages'], 'cpu_us')));
        $this->assertSame([], array_merge(...array_column($dump['pools'], 'workers')));

        // the steps are named, lie in their stage and do not overlap
        $this->assertSame([], array_diff(
            ['config', 'restart.check', 'preload', 'providers', 'files.list', 'scan.stubs', 'scan.project',
                'populate.classlikes', 'populate.files', 'mutation_levels', 'output'],
            array_column($dump['steps'], 'name'),
        ));
        // each file was analysed inside the stage that analyses files
        $analysis_stage = array_values(array_filter(
            $dump['stages'],
            static fn(array $stage): bool => $stage['name'] === 'analysis.files',
        ))[0] ?? ['start_us' => 0, 'wall_us' => 0];

        foreach (self::recordsOf($dump, 'analysis') as $record) {
            $this->assertGreaterThanOrEqual($analysis_stage['start_us'], $record['start_us']);
            $this->assertLessThanOrEqual(
                $analysis_stage['start_us'] + $analysis_stage['wall_us'],
                $record['start_us'] + $record['wall_us'],
            );
        }

        $step_end = 0;

        foreach ($dump['steps'] as $step) {
            $this->assertGreaterThanOrEqual($step_end, $step['start_us']);
            $step_end = $step['start_us'] + $step['wall_us'];

            $in_stage = array_filter(
                $dump['stages'],
                static fn(array $stage): bool => $stage['name'] === $step['stage']
                    && $stage['start_us'] <= $step['start_us']
                    && $step_end <= $stage['start_us'] + $stage['wall_us'],
            );
            $this->assertNotSame([], $in_stage, $step['name']);
        }

        // no record names the directory of the project, nor the way up to Psalm, nor the file of an anonymous class
        $this->assertStringNotContainsString(self::$tmpDir, $json);
        $this->assertStringNotContainsString((string) realpath(self::$tmpDir), $json);
        $this->assertStringNotContainsString(str_replace(['/', '.', ':'], '_', self::$tmpDir), $json);
        $this->assertStringContainsString('{anonymous-class}::go', $json);
        $this->assertStringNotContainsString('"path":"..', $json);
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

        $pool = array_values(array_filter($dump['pools'], static fn(array $p): bool => $p['role'] === 'analysis'))[0];
        $workers = $pool['workers'];
        $pids = array_column($workers, 'pid');
        $this->assertCount(2, array_unique($pids));
        $this->assertSame(7, array_sum(array_column($workers, 'tasks')));

        foreach ($workers as $worker) {
            $this->assertLessThanOrEqual($worker['end_us'] - $worker['start_us'], $worker['busy_us']);
        }

        foreach (self::recordsOf($dump, 'analysis') as $record) {
            $this->assertContains($record['pid'], $pids);
        }

        $this->assertGreaterThan(0, $pool['cpu_us']);
        $this->assertGreaterThan(0, $pool['payload_bytes'] ?? 0);
        // every file was asked of the parser by the process that analysed it
        $this->assertGreaterThanOrEqual(7, $dump['parser']['parses'] + $dump['parser']['cache_hits']);
    }

    #[RequiresPhpExtension('pcntl')]
    #[RequiresPhpExtension('posix')]
    public function testStdoutAndExitCodeDoNotChangeWithTheOption(): void
    {
        $this->setUpProject(6);
        $dump_path = self::$tmpDir . '/perf.json';

        foreach (['--threads=1', '--threads=2'] as $threads) {
            $args = ['--no-cache', '--no-progress', '--output-format=json', $threads];
            $without = $this->runPsalmProcess($args);
            $with = $this->runPsalmProcess([...$args, '--debug-performance=' . $dump_path]);

            // an exit with errors: the dump is written before Psalm exits
            $this->assertSame(2, $without->getExitCode());
            $this->assertSame(2, $with->getExitCode());
            $this->assertStringContainsString('InvalidReturnType', $without->getOutput());
            $this->assertSame($without->getOutput(), $with->getOutput());
            $this->assertFileExists($dump_path);
            unlink($dump_path);
        }
    }

    public function testProfileIsAnAliasOfDebugPerformance(): void
    {
        $this->setUpProject(2);
        $dump_path = self::$tmpDir . '/perf.json';

        $result = $this->runPsalm(['--no-cache', '--profile=' . $dump_path], self::$tmpDir, true);

        $this->assertSame(1, $this->readDump($dump_path)['schema']);
        $this->assertStringContainsString("\nSlowest files by CPU\n", $result['STDOUT']);

        $both = $this->runPsalmProcess(['--no-cache', '--profile', '--debug-performance=' . $dump_path]);

        $this->assertSame(1, $both->getExitCode());
        $this->assertStringContainsString('Only one of --debug-performance and --profile', $both->getErrorOutput());
    }

    public function testWithoutAValuePrintsTheSummaryAndWritesNoFile(): void
    {
        $this->setUpProject(2);
        $files_before = glob(self::$tmpDir . '/*');

        $result = $this->runPsalm(['--no-cache', '--debug-performance'], self::$tmpDir, true);

        foreach (['Performance', 'Slowest files by CPU'] as $header) {
            $this->assertStringContainsString("\n" . $header . "\n", $result['STDOUT']);
        }

        $this->assertStringContainsString('Run with --debug-performance=PATH', $result['STDOUT']);
        $this->assertMatchesRegularExpression('/^Peak memory [\d.]+ MB\. Counts: \d+ class-likes, /m', $result['STDOUT']);
        // a stage that comes back is one row
        $this->assertSame(1, preg_match_all('/^  scan\.files /m', $result['STDOUT']));

        // stages and their steps share the columns: wall, cpu and the share start at the same offsets
        $this->assertSame(1, preg_match('/^Stages\n((?:  .*\n)+)/m', $result['STDOUT'], $stages_block));
        $rows = explode("\n", rtrim($stages_block[1], "\n"));
        $this->assertNotEmpty(array_filter($rows, static fn(string $row): bool => str_starts_with($row, '    ')));

        foreach ($rows as $row) {
            $this->assertMatchesRegularExpression('/^ +\S+ +[\d.]+ s wall +[\d.]+ s cpu +[\d.]+%$/', $row);
        }

        foreach ([' wall ', ' cpu ', '%'] as $needle) {
            $this->assertCount(
                1,
                array_unique(array_map(static fn(string $row): int => (int) strpos($row, $needle), $rows)),
                'The "' . trim($needle) . '" column is not aligned in: ' . "\n" . $stages_block[1],
            );
        }

        $this->assertStringNotContainsString('Performance profile written', $result['STDERR']);
        $this->assertSame($files_before, glob(self::$tmpDir . '/*'));
    }

    public function testTheHandlersOfAPluginAreTimedAtEveryThreadCount(): void
    {
        $this->setUpProject(3);
        mkdir(self::$tmpDir . '/plugins');
        file_put_contents(
            self::$tmpDir . '/plugins/Plug.php',
            "<?php\nnamespace Acme\\Plug;\n\n"
            . "use Psalm\\Plugin\\EventHandler\\AfterFileAnalysisInterface as A;\n"
            . "use Psalm\\Plugin\\EventHandler\\Event\\AfterFileAnalysisEvent as E;\n"
            . "use Psalm\\Plugin\\EventHandler\\Event\\FunctionReturnTypeProviderEvent as F;\n"
            . "use Psalm\\Plugin\\EventHandler\\FunctionReturnTypeProviderInterface as P;\n\n"
            . "final class TimedPlugin implements A, P\n{\n"
            . "    public static function afterAnalyzeFile(E \$e): void\n    {\n    }\n\n"
            . "    public static function getFunctionIds(): array\n    {\n        return ['strlen'];\n    }\n\n"
            . "    public static function getFunctionReturnType(F \$e): ?\\Psalm\\Type\\Union\n    {\n        return null;\n    }\n}\n",
        );
        file_put_contents(
            self::$tmpDir . '/src/Calls.php',
            "<?php\nnamespace Acme\\Extra;\n\nfunction calls(): int\n{\n    return strlen('a') + strlen('b');\n}\n",
        );
        $psalm_xml = (string) file_get_contents(self::$tmpDir . '/psalm.xml');
        file_put_contents(
            self::$tmpDir . '/psalm.xml',
            str_replace('</psalm>', '<plugins><plugin filename="plugins/Plug.php"/></plugins></psalm>', $psalm_xml),
        );
        $dump_path = self::$tmpDir . '/perf.json';
        $counts = [];

        foreach (['--threads=1', '--threads=2'] as $threads) {
            @unlink($dump_path);
            $this->runPsalm(
                ['--no-cache', '--no-progress', $threads, '--debug-performance=' . $dump_path],
                self::$tmpDir,
                true,
            );
            $dump = $this->readDump($dump_path);
            $counts[] = $dump['counts'];

            $calls = [];
            foreach ($dump['hooks'] as $hook) {
                if ($hook['handler'] === 'Acme\\Plug\\TimedPlugin') {
                    $this->assertSame('plugin', $hook['origin']);
                    $calls[$hook['hook']] = $hook['calls'];
                }
            }

            ksort($calls);
            $this->assertSame(['AfterFileAnalysis', 'FunctionReturnType'], array_keys($calls), $threads);
            $this->assertSame(['Acme\\Plug\\TimedPlugin'], array_column($dump['plugins'], 'class'), $threads);
            // once for each file analysed, and once for each call of strlen() that the project analyses
            $this->assertCount($calls['AfterFileAnalysis'], self::recordsOf($dump, 'analysis'), $threads);
            $this->assertSame(2, $calls['FunctionReturnType'], $threads);
            $this->assertContains('psalm', array_column($dump['hooks'], 'origin'));

            // what the project defines in the second run: a class with 3 methods, 2 functions and 2 closures
            file_put_contents(
                self::$tmpDir . '/src/Defs.php',
                "<?php\nnamespace Acme\\Extra;\n\nclass Defs\n{\n    public function a(): void\n    {\n    }\n\n"
                . "    public function b(): void\n    {\n    }\n\n    public function c(): void\n    {\n    }\n}\n\n"
                . "function one(): int\n{\n    \$a = function (): void {\n    };\n    \$a();\n    \$b = fn(): int => 1;\n"
                . "    return \$b();\n}\n\nfunction two(): void\n{\n}\n",
            );
        }

        $this->assertSame(
            ['classlikes' => 1, 'methods' => 3, 'functions' => 2, 'file_storages' => 1],
            [
                'classlikes' => $counts[1]['classlikes'] - $counts[0]['classlikes'],
                'methods' => $counts[1]['methods'] - $counts[0]['methods'],
                'functions' => $counts[1]['functions'] - $counts[0]['functions'],
                'file_storages' => $counts[1]['file_storages'] - $counts[0]['file_storages'],
            ],
        );
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
     * @param list<string> $args
     */
    private function runPsalmProcess(array $args): Process
    {
        $process = self::newProcess(
            [PHP_BINARY, $this->psalm, '-c=' . self::$tmpDir . '/psalm.xml', ...$args],
            null,
            self::agentEnvVarsToUnset(),
        );
        $process->run();

        return $process;
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
