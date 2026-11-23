<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Perf;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Perf\Collector;
use stdClass;

use function array_column;
use function array_sum;
use function array_unique;
use function count;
use function dirname;
use function file_put_contents;
use function getenv;
use function json_encode;
use function ksort;
use function link;
use function mkdir;
use function putenv;
use function range;
use function rmdir;
use function symlink;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;
use function usleep;

use const DIRECTORY_SEPARATOR;
use const PHP_OS_FAMILY;

/**
 * @psalm-import-type WorkerPerf from Collector
 * @psalm-import-type FileRecord from Collector
 * @psalm-import-type StatementRecord from Collector
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

    public function testTopListsKeepTheExactTopTwentyAcrossWorkers(): void
    {
        Collector::start(null, []);

        $first = [];
        $second = [];
        foreach (range(1, 30) as $i) {
            $record = ['path' => 'src/F.php', 'line' => $i, 'node' => 'Stmt_If', 'self_us' => $i * 10, 'wall_us' => $i * 10];

            if ($i % 2 === 0) {
                $first[] = $record;
            } else {
                $second[] = $record;
            }
        }

        Collector::addWorkerData(self::workerPerf(1, 'analysis', [], $first));
        Collector::addWorkerData(self::workerPerf(2, 'analysis', [], $second));

        $result = Collector::finish(0, 0);

        $this->assertNotNull($result);
        $this->assertSame(range(300, 110, -10), array_column($result['top']['statements'], 'self_us'));
        $this->assertCount(20, array_unique(array_column($result['top']['statements'], 'line')));
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

        Collector::dispatchVoid([$first, $second], 'handle', new stdClass(), 'AfterFileAnalysis');
        Collector::dispatchVoid([FirstHookHandler::class], 'handle', new stdClass(), 'AfterFileAnalysis');
        Collector::call($object->handle(...), new stdClass(), 'FunctionReturnType');

        $result = Collector::finish(0, 0);

        $this->assertNotNull($result);
        $by_handler = [];
        foreach ($result['hooks'] as $hook) {
            $by_handler[$hook['handler'] . ' ' . $hook['hook']] = [$hook['origin'], $hook['calls']];
        }

        ksort($by_handler);
        $this->assertSame(
            [
                FirstHookHandler::class . ' AfterFileAnalysis' => ['psalm', 1],
                '{anonymous-class} AfterFileAnalysis' => ['plugin', 2],
                '{anonymous-class} FunctionReturnType' => ['plugin', 1],
            ],
            $by_handler,
        );
        $this->assertStringNotContainsString(__DIR__, (string) json_encode($result['hooks']));
    }

    public function testClosuresOfAMethodTheClassesShareAreCountedForEachClass(): void
    {
        Collector::start(null, []);
        $event = new stdClass();

        Collector::call(FirstHookHandler::handle(...), $event, 'FunctionReturnType');
        Collector::call(SecondHookHandler::handle(...), $event, 'FunctionReturnType');
        Collector::call(SecondHookHandler::handle(...), $event, 'FunctionReturnType');

        $result = Collector::finish(0, 0);

        $this->assertNotNull($result);
        $calls = [];
        foreach ($result['hooks'] as $hook) {
            $calls[$hook['handler']] = $hook['calls'];
        }

        ksort($calls);
        $this->assertSame([FirstHookHandler::class => 1, SecondHookHandler::class => 2], $calls);
    }

    public function testTheDispatchHelpersEndWhereTheDispatcherDoes(): void
    {
        Collector::start(null, []);
        $null = new class {
            public static int $calls = 0;

            public static function handle(object $event): ?bool
            {
                ++self::$calls;

                return null;
            }
        };
        $false = new class {
            public static int $calls = 0;

            public static function handle(object $event): ?bool
            {
                ++self::$calls;

                return false;
            }
        };
        $true = new class {
            public static int $calls = 0;

            public static function handle(object $event): ?bool
            {
                ++self::$calls;

                return true;
            }
        };
        $bits = new class {
            /**
             * @psalm-pure
             */
            public static function handle(object $event): int
            {
                return 5;
            }
        };
        $more_bits = new class {
            /**
             * @psalm-pure
             */
            public static function handle(object $event): int
            {
                return 2;
            }
        };
        $event = new stdClass();

        $this->assertFalse(
            Collector::dispatchUntilFalse([$null::class, $false::class, $true::class], 'handle', $event, 'X'),
        );
        $this->assertSame([1, 1, 0], [$null::$calls, $false::$calls, $true::$calls]);
        $this->assertNull(Collector::dispatchUntilFalse([$null::class, $true::class], 'handle', $event, 'X'));
        $this->assertTrue(Collector::dispatchFirst([$null::class, $true::class, $false::class], 'handle', $event, 'X'));
        $this->assertSame([3, 1, 2], [$null::$calls, $false::$calls, $true::$calls]);
        $this->assertFalse(Collector::dispatchFirst([$false::class, $true::class], 'handle', $event, 'X'));
        $this->assertNull(Collector::dispatchFirst([$null::class], 'handle', $event, 'X'));
        $this->assertSame(7, Collector::dispatchOr([$bits::class, $more_bits::class], 'handle', $event, 'Y'));
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

    public function testDestinationsAreTheSameThroughASymlinkedDirectory(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Creating symlinks needs a privilege on Windows');
        }

        $base = sys_get_temp_dir() . '/psalm-collector-' . uniqid();
        mkdir($base . '/real', 0777, true);
        symlink($base . '/real', $base . '/link');

        try {
            // the file does not exist yet, nor does the directory under the link
            $this->assertTrue(Collector::isSameDestination($base . '/real/r.json', $base . '/link/r.json', '/'));
            $this->assertTrue(Collector::isSameDestination('link/new/r.json', 'real/new/r.json', $base));
            $this->assertFalse(Collector::isSameDestination($base . '/real/r.json', $base . '/link/other.json', '/'));
            $this->assertFalse(Collector::isSameDestination('r.json', 'real/r.json', $base));
        } finally {
            unlink($base . '/link');
            rmdir($base . '/real');
            rmdir($base);
        }
    }

    public function testDestinationsAreTheSameThroughADanglingSymlinkToTheFile(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Creating symlinks needs a privilege on Windows');
        }

        $base = sys_get_temp_dir() . '/psalm-collector-dangling-' . uniqid();
        mkdir($base . '/real', 0777, true);
        symlink($base . '/real/report.json', $base . '/absolute.json');
        symlink('real/report.json', $base . '/relative.json');
        symlink('absolute.json', $base . '/chain.json');

        try {
            $this->assertTrue(Collector::isSameDestination($base . '/absolute.json', $base . '/real/report.json', '/'));
            $this->assertTrue(Collector::isSameDestination('relative.json', 'real/report.json', $base));
            $this->assertTrue(Collector::isSameDestination($base . '/chain.json', $base . '/real/report.json', '/'));
            $this->assertFalse(Collector::isSameDestination($base . '/relative.json', $base . '/real/other.json', '/'));
        } finally {
            unlink($base . '/absolute.json');
            unlink($base . '/relative.json');
            unlink($base . '/chain.json');
            rmdir($base . '/real');
            rmdir($base);
        }
    }

    public function testDestinationsAreTheSameWhenDotDotFollowsASymlink(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Creating symlinks needs a privilege on Windows');
        }

        $base = sys_get_temp_dir() . '/psalm-collector-dotdot-' . uniqid();
        mkdir($base . '/deep/inner', 0777, true);
        symlink($base . '/deep/inner', $base . '/link');

        try {
            // `link/..` is deep, not the base
            $this->assertTrue(Collector::isSameDestination($base . '/link/../r.json', $base . '/deep/r.json', '/'));
            $this->assertFalse(Collector::isSameDestination($base . '/link/../r.json', $base . '/r.json', '/'));
            $this->assertTrue(Collector::isSameDestination('link/../r.json', 'deep/r.json', $base));
        } finally {
            unlink($base . '/link');
            rmdir($base . '/deep/inner');
            rmdir($base . '/deep');
            rmdir($base);
        }
    }

    public function testDestinationsAreTheSameForAHardLinkAndWhereTheFileSystemIgnoresCase(): void
    {
        $base = sys_get_temp_dir() . '/psalm-collector-links-' . uniqid();
        mkdir($base);
        file_put_contents($base . '/report.json', '{}');

        try {
            if (DIRECTORY_SEPARATOR !== '\\') {
                link($base . '/report.json', $base . '/hard.json');
                $this->assertTrue(Collector::isSameDestination($base . '/report.json', $base . '/hard.json', '/'));
                unlink($base . '/hard.json');
            }

            $ignores_case = PHP_OS_FAMILY === 'Darwin' || PHP_OS_FAMILY === 'Windows';
            $this->assertSame(
                $ignores_case,
                Collector::isSameDestination($base . '/report.json', $base . '/REPORT.json', '/'),
            );
        } finally {
            unlink($base . '/report.json');
            rmdir($base);
        }
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
            'uppercase' => ['00-0AF7651916CD43DD8448EB211C80319C-B7AD6B7169203331-01', null],
            'other version' => ['01-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-01', null],
            'zero trace id' => ['00-00000000000000000000000000000000-b7ad6b7169203331-01', null],
            'zero parent id' => ['00-0af7651916cd43dd8448eb211c80319c-0000000000000000-01', null],
            'trailing newline' => ["00-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-01\n", null],
        ];
    }

    #[DataProvider('traceparents')]
    public function testTraceparentIsOnlyAcceptedWhenItIsAValidW3cHeader(?string $value, ?string $expected): void
    {
        putenv($value === null ? 'TRACEPARENT' : 'TRACEPARENT=' . $value);

        $this->assertSame($expected, Collector::traceparent());
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
     * @param list<StatementRecord> $top_statements
     * @return WorkerPerf
     * @psalm-pure
     */
    private static function workerPerf(int $pid, string $role, array $files, array $top_statements = []): array
    {
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
            'statements' => [],
            'functions' => [],
            'top_statements' => $top_statements,
            'top_functions' => [],
            'hooks' => [],
            'parser' => ['files_parsed' => 0, 'parser_cache_hits' => 0, 'parser_cache_misses' => 0],
        ];
    }
}
