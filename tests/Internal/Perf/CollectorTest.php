<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Perf;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Nop;
use Psalm\Internal\Perf\Collector;
use Psalm\StatementsSource;
use stdClass;

use function array_column;
use function array_map;
use function array_slice;
use function array_sum;
use function count;
use function dirname;
use function file_put_contents;
use function getenv;
use function hrtime;
use function json_encode;
use function ksort;
use function link;
use function mkdir;
use function putenv;
use function range;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;
use function usleep;

use const DIRECTORY_SEPARATOR;

/**
 * @psalm-import-type WorkerPerf from Collector
 * @psalm-import-type FileRecord from Collector
 * @psalm-import-type StatementRecord from Collector
 * @psalm-import-type HookRecord from Collector
 */
final class CollectorTest extends TestCase
{
    private string|false $traceparent = false;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->traceparent = getenv('TRACEPARENT');
    }

    #[Override]
    protected function tearDown(): void
    {
        Collector::reset();
        putenv($this->traceparent === false ? 'TRACEPARENT' : 'TRACEPARENT=' . $this->traceparent);
        parent::tearDown();
    }

    public function testStagesAreContiguousAndSumToTheTotals(): void
    {
        Collector::start(null, ['no-cache']);
        $this->assertSame('bootstrap', Collector::stage('scan.setup'));
        usleep(2_000);
        $this->assertSame('scan.setup', Collector::stage('scan.files'));
        usleep(2_000);
        Collector::stage('scan.setup');

        $result = Collector::finish(3, 1);

        $this->assertNotNull($result);
        $stages = $result['stages'];
        $this->assertSame(['bootstrap', 'scan.setup', 'scan.files', 'scan.setup'], array_column($stages, 'name'));

        for ($i = 1; $i < count($stages); ++$i) {
            $this->assertSame($stages[$i - 1]['start_us'] + $stages[$i - 1]['wall_us'], $stages[$i]['start_us']);
        }

        $this->assertSame($result['totals']['wall_us'], array_sum(array_column($stages, 'wall_us')));
        $this->assertSame($result['totals']['cpu_us'], array_sum(array_column($stages, 'cpu_us')));
        $this->assertGreaterThanOrEqual(4_000, $result['totals']['wall_us']);
        $this->assertSame(['errors' => 3, 'infos' => 1], $result['totals']['issues']);
        $this->assertSame(['no-cache'], Collector::optionNames());
    }

    public function testAddWorkerDataMergesRecordsAndComputesBusyTime(): void
    {
        Collector::start(null, []);
        Collector::poolStart('analysis', 2, 3);

        Collector::addWorkerData(self::workerPerf(
            101,
            'analysis',
            [
                self::file('analysis', 'src/B.php', 101, 1_000),
                self::file('scan', 'src/Lazy.php', 101, 400),
                self::file('analysis', 'src/A.php', 101, 2_000),
            ],
        ));
        Collector::mergeDone();
        Collector::addWorkerData(self::workerPerf(102, 'analysis', [self::file('analysis', 'src/C.php', 102, 500)]));
        Collector::mergeDone();
        Collector::payload(300);
        Collector::payload(700);
        Collector::poolEnd();

        $result = Collector::finish(0, 0);

        $this->assertNotNull($result);
        $this->assertSame(
            ['src/A.php', 'src/B.php', 'src/C.php', 'src/Lazy.php'],
            array_column($result['files'], 'path'),
        );
        $this->assertSame(3, $result['totals']['files_analyzed']);
        $this->assertSame(1, $result['totals']['files_scanned']);

        $this->assertCount(2, $result['workers']);
        // the scan inside the analysis of a file is part of it, so it is not busy time of its own
        $this->assertSame([1, 101, 2, 3_000], [
            $result['workers'][0]['pool'],
            $result['workers'][0]['pid'],
            $result['workers'][0]['tasks'],
            $result['workers'][0]['busy_us'],
        ]);
        $this->assertSame([102, 1, 500], [
            $result['workers'][1]['pid'],
            $result['workers'][1]['tasks'],
            $result['workers'][1]['busy_us'],
        ]);

        $this->assertCount(1, $result['pools']);
        $this->assertSame(
            ['id' => 1, 'role' => 'analysis', 'workers' => 2, 'tasks' => 3],
            [
                'id' => $result['pools'][0]['id'],
                'role' => $result['pools'][0]['role'],
                'workers' => $result['pools'][0]['workers'],
                'tasks' => $result['pools'][0]['tasks'],
            ],
        );
        $this->assertSame(['sum' => 1_000, 'max' => 700], $result['pools'][0]['payload_bytes']);
    }

    public function testExecutionsOfOneStatementAreOneHotItemWithTheirPasses(): void
    {
        Collector::start(null, []);

        for ($i = 0; $i < 30; ++$i) {
            Collector::recordStatement('src/F.php', 271, 'Stmt_If', 40_000, 41_000);
        }

        Collector::recordStatement('src/F.php', 5, 'Stmt_Echo', 1_500, 1_500);

        for ($i = 0; $i < 2; ++$i) {
            Collector::recordFunction('src/F.php', 20, 'A::__construct', 'initializations', 20_000);
            Collector::recordFunction('src/F.php', 20, 'A::__construct', 'body', 20_000);
        }

        $result = Collector::finish(0, 0);

        $this->assertNotNull($result);
        // the statement that took 1.5 ms in all is not hot, but it is in the top list
        $this->assertSame(
            [['src/F.php', 271, 30, 1_200_000, 1_230_000]],
            array_map(
                static fn(array $r): array => [$r['path'], $r['line'], $r['passes'], $r['self_us'], $r['wall_us']],
                $result['hot']['statements'],
            ),
        );
        $this->assertSame([271, 5], array_column($result['top']['statements'], 'line'));
        $this->assertSame(
            [['A::__construct', 'body', 2, 40_000], ['A::__construct', 'initializations', 2, 40_000]],
            array_map(
                static fn(array $r): array => [$r['id'], $r['pass'], $r['passes'], $r['wall_us']],
                $result['top']['functions'],
            ),
        );
        // 40 ms in all: below the threshold
        $this->assertSame([], $result['hot']['functions']);
    }

    public function testTheRecordsOfWorkersAreMergedByPlace(): void
    {
        Collector::start(null, []);

        $records = [];
        foreach (range(1, 30) as $i) {
            $records[] = [
                'path' => 'src/F.php',
                'line' => $i,
                'node' => 'Stmt_If',
                'passes' => 1,
                'self_us' => $i * 1_000,
                'wall_us' => $i * 1_000,
            ];
        }

        Collector::addWorkerData(self::workerPerf(1, 'analysis', [], array_slice($records, 0, 20)));
        Collector::addWorkerData(self::workerPerf(2, 'analysis', [], array_slice($records, 10)));

        $result = Collector::finish(0, 0);

        $this->assertNotNull($result);
        $top = $result['top']['statements'];
        $this->assertCount(20, $top);
        // lines 11 to 20 are in both workers: 2 passes, twice the time
        $this->assertSame(
            [20, 19, 18, 17, 16, 15, 30, 29, 14, 28, 27, 13, 26, 25, 12, 24, 23, 11, 22, 21],
            array_column($top, 'line'),
        );
        $this->assertSame([2, 40_000], [$top[0]['passes'], $top[0]['self_us']]);
    }

    public function testNestedStatementsAreTimedByTheirOwnTime(): void
    {
        Collector::start(null, []);
        $outer = self::statement(10);
        $inner = self::statement(11);
        $source = $this->createStub(StatementsSource::class);
        $source->method('getFilePath')->willReturn('/p/src/N.php');
        $source->method('getFileName')->willReturn('src/N.php');

        $saved_child_ns = Collector::$child_ns;
        Collector::$child_ns = 0;
        $outer_start = (int) hrtime(true);

        $inner_saved = Collector::$child_ns;
        Collector::$child_ns = 0;
        $inner_start = (int) hrtime(true);
        usleep(5_000);
        Collector::statementEnd($source, $inner, $inner_start, $inner_saved);

        usleep(5_000);
        Collector::statementEnd($source, $outer, $outer_start, $saved_child_ns);

        $result = Collector::finish(0, 0);

        $this->assertNotNull($result);
        $by_line = [];
        foreach ($result['top']['statements'] as $record) {
            $by_line[$record['line']] = $record;
        }

        // the outer one took about 10 ms with the inner one, and about 5 ms of its own
        $this->assertGreaterThanOrEqual(10_000, $by_line[10]['wall_us']);
        $this->assertGreaterThanOrEqual(5_000, $by_line[10]['self_us']);
        $this->assertLessThan($by_line[10]['wall_us'] - 4_000, $by_line[10]['self_us']);
        $this->assertGreaterThanOrEqual(5_000, $by_line[11]['self_us']);
    }

    public function testAnAnonymousHandlerIsNamedWithoutTheFileOfItsClass(): void
    {
        Collector::start(null, []);
        $first = (new class {
            /**
             * @psalm-capabilities read-props
             */
            public static function handle(object $event): void
            {
            }
        })::class;
        $second = (new class {
            /**
             * @psalm-capabilities read-props
             */
            public static function handle(object $event): void
            {
            }
        })::class;
        $object = new class {
            /**
             * @psalm-pure
             */
            public function handle(object $event): int
            {
                return 1;
            }
        };

        Collector::timedVoid($first, 'handle', new stdClass(), 'AfterFileAnalysis');
        Collector::timedVoid($second, 'handle', new stdClass(), 'AfterFileAnalysis');
        Collector::wrap($object->handle(...), 'FunctionReturnType')(new stdClass());

        $result = Collector::finish(0, 0);

        $this->assertNotNull($result);
        $by_handler = [];
        foreach ($result['hooks'] as $hook) {
            $by_handler[$hook['handler'] . ' ' . $hook['hook']] = [$hook['origin'], $hook['calls']];
        }

        ksort($by_handler);
        $this->assertSame(
            [
                '{anonymous-class} AfterFileAnalysis' => ['plugin', 2],
                '{anonymous-class} FunctionReturnType' => ['plugin', 1],
            ],
            $by_handler,
        );
        $this->assertStringNotContainsString(__DIR__, (string) json_encode($result['hooks']));
    }

    public function testOnlyClassesUnderPsalmInternalAreOfPsalm(): void
    {
        Collector::start(null, []);
        $hooks = [];
        foreach (['Psalm\\Internal\\Provider\\X', 'Psalm\\LaravelPlugin\\Handler', 'Acme\\Handler'] as $handler) {
            $hooks[] = ['handler' => $handler, 'hook' => 'AfterFileAnalysis', 'calls' => 1, 'wall_ns' => 5_000];
        }

        Collector::addWorkerData(self::workerPerf(1, 'analysis', [], [], $hooks));

        $result = Collector::finish(0, 0);

        $this->assertNotNull($result);
        $origins = [];
        foreach ($result['hooks'] as $hook) {
            $origins[$hook['handler']] = $hook['origin'];
        }

        ksort($origins);
        $this->assertSame(
            ['Acme\\Handler' => 'plugin', 'Psalm\\Internal\\Provider\\X' => 'psalm', 'Psalm\\LaravelPlugin\\Handler' => 'plugin'],
            $origins,
        );
    }

    public function testClosuresOfAMethodTheClassesShareAreCountedForEachClass(): void
    {
        Collector::start(null, []);
        $event = new stdClass();

        Collector::wrap(FirstHookHandler::handle(...), 'FunctionReturnType')($event);
        Collector::wrap(SecondHookHandler::handle(...), 'FunctionReturnType')($event);
        Collector::wrap(SecondHookHandler::handle(...), 'FunctionReturnType')($event);

        $result = Collector::finish(0, 0);

        $this->assertNotNull($result);
        $calls = [];
        foreach ($result['hooks'] as $hook) {
            $calls[$hook['handler']] = $hook['calls'];
        }

        ksort($calls);
        $this->assertSame([FirstHookHandler::class => 1, SecondHookHandler::class => 2], $calls);
    }

    public function testTheTimedCallsGiveBackWhatTheHandlerReturns(): void
    {
        Collector::start(null, []);
        $handler = (new class {
            /**
             * @psalm-pure
             */
            public static function handle(object $event): ?bool
            {
                return false;
            }

            /**
             * @psalm-pure
             */
            public static function bits(object $event): int
            {
                return 6;
            }
        })::class;

        $this->assertFalse(Collector::timedBool($handler, 'handle', new stdClass(), 'X'));
        $this->assertSame(6, Collector::timedInt($handler, 'bits', new stdClass(), 'X'));
    }

    public function testNoFileIsWrittenOverAReportOrTheBaseline(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Hard links need a privilege on some Windows setups');
        }

        $base = sys_get_temp_dir() . '/psalm-collector-guard-' . uniqid();
        mkdir($base);
        file_put_contents($base . '/report.json', '{}');
        file_put_contents($base . '/baseline.xml', '<files/>');
        file_put_contents($base . '/other.json', '{}');
        link($base . '/report.json', $base . '/dump.json');

        try {
            $this->assertSame(
                $base . '/report.json',
                Collector::sameFileAs($base . '/dump.json', [$base . '/baseline.xml', $base . '/report.json']),
            );
            $this->assertNull(
                Collector::sameFileAs($base . '/dump.json', [$base . '/baseline.xml', $base . '/other.json', '']),
            );
            // a dump that doesn't exist yet is nobody's file
            $this->assertNull(Collector::sameFileAs($base . '/new.json', [$base . '/report.json']));
        } finally {
            unlink($base . '/dump.json');
            unlink($base . '/report.json');
            unlink($base . '/baseline.xml');
            unlink($base . '/other.json');
            rmdir($base);
        }
    }

    public function testDisabledCollectorRecordsNothing(): void
    {
        $this->assertFalse(Collector::$enabled);
        $this->assertSame('', Collector::stage('scan.files'));
        $this->assertNull(Collector::workerData());

        Collector::poolStart('scan', 2, 10);
        Collector::payload(100);
        Collector::addWorkerData(self::workerPerf(1, 'scan', [self::file('scan', 'src/A.php', 1, 10)]));
        Collector::mergeDone();
        Collector::poolEnd();
        Collector::taint(1, 2, 3, 4);

        $this->assertNull(Collector::finish(0, 0));

        Collector::start(null, []);
        $result = Collector::finish(0, 0);
        $this->assertNotNull($result);
        $this->assertSame([], $result['files']);
        $this->assertSame([], $result['pools']);
        $this->assertSame([], $result['workers']);
        $this->assertSame(0, $result['taint']['nodes']);
    }

    public function testWorkerStartDropsWhatTheWorkerInheritedAndWorkerDataShipsTheRest(): void
    {
        Collector::start(null, []);
        Collector::analysisEnd('/p/src/Parent.php', 'src/Parent.php', Collector::fileStart(), 0);

        Collector::workerStart('analysis');
        // a worker does not record stages
        $this->assertSame('', Collector::stage('analysis.files'));
        Collector::analysisEnd('/p/src/Own.php', 'src/Own.php', Collector::fileStart(), 2);

        $data = Collector::workerData();

        $this->assertNotNull($data);
        $this->assertSame('analysis', $data['role']);
        $this->assertSame(['src/Own.php'], array_column($data['files'], 'path'));
        $this->assertSame(2, $data['files'][0]['issues'] ?? null);
        $this->assertGreaterThanOrEqual($data['start_us'], $data['end_us']);
        // the worker ended when its last file did, not when its result was asked for
        $this->assertSame($data['files'][0]['start_us'] + $data['files'][0]['wall_us'], $data['end_us']);
    }

    public function testTheHeapPeakOfTheTotalsIsTheOneTheRunPrintedIfItPrintedOne(): void
    {
        Collector::start(null, []);
        $result = Collector::finish(0, 0, 123_456);
        $this->assertNotNull($result);
        $this->assertSame(123_456, $result['totals']['php_peak_bytes']);

        Collector::start(null, []);
        $result = Collector::finish(0, 0);
        $this->assertNotNull($result);
        $this->assertGreaterThan(0, $result['totals']['php_peak_bytes']);
    }

    /**
     * @return array<string, array{string, string, ?string, string}> the path on disk, the path Psalm prints,
     *     the root of Psalm, what the dump shows
     * @psalm-pure
     */
    public static function displayPaths(): array
    {
        return [
            'project file stays' => ['/proj/src/A.php', 'src/A.php', null, 'src/A.php'],
            'path above the project stays' => ['/x/vendor/y.php', '../vendor/y.php', null, '../vendor/y.php'],
            'absolute path of a missing file' => [
                '/home/me/project/src/A.php',
                '/home/me/project/src/A.php',
                null,
                '<external>/A.php',
            ],
            'file of another phar' => [
                'phar:///usr/bin/other.phar/x/Core.phpstub',
                '../../phar:///usr/bin/other.phar/x/Core.phpstub',
                '/opt/psalm',
                '<external>/Core.phpstub',
            ],
            'file in the phar of Psalm' => [
                'phar:///usr/bin/psalm.phar/stubs/Core.phpstub',
                '../../phar:///usr/bin/psalm.phar/stubs/Core.phpstub',
                'phar:///usr/bin/psalm.phar',
                '<psalm>/stubs/Core.phpstub',
            ],
            'Psalm installed outside the project' => [
                '/opt/psalm/stubs/Core.phpstub',
                '../../opt/psalm/stubs/Core.phpstub',
                '/opt/psalm',
                '<psalm>/stubs/Core.phpstub',
            ],
            'Psalm installed in the project' => [
                '/proj/vendor/vimeo/psalm/stubs/Core.phpstub',
                'vendor/vimeo/psalm/stubs/Core.phpstub',
                '/proj/vendor/vimeo/psalm',
                'vendor/vimeo/psalm/stubs/Core.phpstub',
            ],
            'Psalm as the project' => [
                '/work/psalm/src/Psalm/Config.php',
                'src/Psalm/Config.php',
                '/work/psalm',
                'src/Psalm/Config.php',
            ],
            'windows path' => ['C:\\Users\\me\\A.php', 'C:\\Users\\me\\A.php', null, '<external>/A.php'],
        ];
    }

    #[DataProvider('displayPaths')]
    public function testDisplayPathNeverShowsADirectoryPsalmDidNotPrint(
        string $file_path,
        string $file_name,
        ?string $psalm_root,
        string $expected,
    ): void {
        $this->assertSame($expected, Collector::displayPath($file_path, $file_name, $psalm_root));
    }

    public function testDisplayPathKnowsThePsalmThatIsRunning(): void
    {
        $stub = dirname(__DIR__, 3) . '/stubs/CoreGenericFunctions.phpstub';

        $this->assertSame(
            '<psalm>/stubs/CoreGenericFunctions.phpstub',
            Collector::displayPath($stub, '../../../elsewhere/stubs/CoreGenericFunctions.phpstub'),
        );
    }

    public function testAbsolutePathJoinsTheBaseAndKeepsDotDot(): void
    {
        $this->assertSame('/work/out/perf.json', Collector::absolutePath('out/perf.json', '/work'));
        // after a symlink `..` is not the parent of what the path names so far
        $this->assertSame('/work/link/../perf.json', Collector::absolutePath('link/../perf.json', '/work'));
        $this->assertSame('/tmp/a/perf.json', Collector::absolutePath('/tmp/a/perf.json', '/work'));
    }

    public function testAbsolutePathKeepsTheRootOfAWindowsPath(): void
    {
        $this->assertSame('//server/share/a', Collector::absolutePath('\\\\server\\share\\a', 'C:\\work', true));
        $this->assertSame('C:/work/out', Collector::absolutePath('out', 'C:\\work', true));
        $this->assertSame('D:/out', Collector::absolutePath('D:\\out', 'C:\\work', true));
    }

    /**
     * @return array<string, array{?string, ?string}>
     * @psalm-pure
     */
    public static function traceparents(): array
    {
        $valid = '00-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-01';

        return [
            'valid' => [$valid, $valid],
            'unset' => [null, null],
            'malformed' => ['00-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331', null],
            'zero trace id' => ['00-00000000000000000000000000000000-b7ad6b7169203331-01', null],
            'zero parent id' => ['00-0af7651916cd43dd8448eb211c80319c-0000000000000000-01', null],
        ];
    }

    #[DataProvider('traceparents')]
    public function testTraceparentIsOnlyAcceptedWhenItIsAValidW3cHeader(?string $value, ?string $expected): void
    {
        putenv($value === null ? 'TRACEPARENT' : 'TRACEPARENT=' . $value);

        $this->assertSame($expected, Collector::traceparent());
    }

    /**
     * @psalm-pure
     */
    private static function statement(int $line): Stmt
    {
        return new Nop(['startLine' => $line]);
    }

    /**
     * @return FileRecord
     * @psalm-pure
     */
    private static function file(string $op, string $path, int $pid, int $wall_us): array
    {
        return [
            'op' => $op,
            'path' => $path,
            'pid' => $pid,
            'start_us' => 0,
            'wall_us' => $wall_us,
            'cpu_us' => $wall_us,
            'mem_bytes' => 0,
            'peak_growth_bytes' => 0,
        ];
    }

    /**
     * @param list<FileRecord> $files
     * @param list<StatementRecord> $statements
     * @param list<HookRecord> $hooks
     * @return WorkerPerf
     * @psalm-pure
     */
    private static function workerPerf(
        int $pid,
        string $role,
        array $files,
        array $statements = [],
        array $hooks = [],
    ): array {
        return [
            'pid' => $pid,
            'role' => $role,
            'start_us' => 10,
            'end_us' => 10_000,
            'cpu_us' => 5_000,
            'cpu_sys_us' => 100,
            'php_start_bytes' => 1_000,
            'php_peak_bytes' => 2_000,
            'max_rss_bytes' => 3_000,
            'files' => $files,
            'statements' => $statements,
            'functions' => [],
            'hooks' => $hooks,
            'parser' => ['files_parsed' => 0, 'parser_cache_hits' => 0, 'parser_cache_misses' => 0],
        ];
    }
}
