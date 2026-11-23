<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Perf;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PhpParser\Node\Stmt\Nop;
use Psalm\Internal\Perf\Collector;
use Psalm\StatementsSource;
use stdClass;

use function array_column;
use function array_map;
use function array_sum;
use function file_put_contents;
use function getenv;
use function hrtime;
use function json_encode;
use function ksort;
use function link;
use function putenv;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;
use function usleep;

use const DIRECTORY_SEPARATOR;

/**
 * @psalm-import-type WorkerPerf from Collector
 * @psalm-import-type FileRecord from Collector
 * @psalm-import-type HookRecord from Collector
 */
final class CollectorTest extends TestCase
{
    #[Override]
    protected function tearDown(): void
    {
        Collector::reset();
        parent::tearDown();
    }

    public function testStagesAreContiguousAndSumToTheTotals(): void
    {
        Collector::start(null, []);
        $this->assertSame('bootstrap', Collector::stage('scan.setup'));
        usleep(1_000);
        Collector::stage('scan.files');
        Collector::stage('scan.setup');

        $result = Collector::finish(3, 1);

        $this->assertNotNull($result);
        $stages = $result['stages'];
        $this->assertSame(['bootstrap', 'scan.setup', 'scan.files', 'scan.setup'], array_column($stages, 'name'));
        $this->assertSame($stages[1]['start_us'] + $stages[1]['wall_us'], $stages[2]['start_us']);
        $this->assertSame($result['totals']['wall_us'], array_sum(array_column($stages, 'wall_us')));
        $this->assertSame($result['totals']['cpu_us'], array_sum(array_column($stages, 'cpu_us')));
        $this->assertSame(['errors' => 3, 'infos' => 1], $result['totals']['issues']);
    }

    public function testAddWorkerDataMergesRecordsAndComputesBusyTime(): void
    {
        Collector::start(null, []);
        Collector::poolStart('analysis', 2, 3);
        $files = [self::file('analysis', 'src/B.php', 101, 1_000), self::file('scan', 'src/Lazy.php', 101, 400)];

        Collector::addWorkerData(self::workerPerf(101, $files));
        Collector::addWorkerData(self::workerPerf(102, [self::file('analysis', 'src/A.php', 102, 500)]));
        Collector::payload(300);
        Collector::payload(700);
        Collector::poolEnd();

        $result = Collector::finish(0, 0);

        $this->assertNotNull($result);
        $this->assertSame(['src/A.php', 'src/B.php', 'src/Lazy.php'], array_column($result['files'], 'path'));
        $this->assertSame([[101, 1, 1_000], [102, 1, 500]], array_map(
            static fn(array $worker): array => [$worker['pid'], $worker['tasks'], $worker['busy_us']],
            $result['workers'],
        ));
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
        $this->assertSame([[271, 30, 1_200_000]], array_map(
            static fn(array $r): array => [$r['line'], $r['passes'], $r['self_us']],
            $result['hot']['statements'],
        ));
        $this->assertSame([271, 5], array_column($result['top']['statements'], 'line'));
        // each pass of the constructor is an entry of its own, 40 ms in all: not hot
        $this->assertSame(['body', 'initializations'], array_column($result['top']['functions'], 'pass'));
        $this->assertSame([2, 2], array_column($result['top']['functions'], 'passes'));
        $this->assertSame([], $result['hot']['functions']);
    }

    public function testNestedStatementsAreTimedByTheirOwnTime(): void
    {
        Collector::start(null, []);
        $source = $this->createStub(StatementsSource::class);
        $source->method('getFilePath')->willReturn('/p/src/N.php');
        $source->method('getFileName')->willReturn('src/N.php');

        // as StatementsAnalyzer does: the outer statement starts, and its child is analysed inside it
        $outer_start = (int) hrtime(true);
        Collector::$child_ns = 0;
        $inner_start = (int) hrtime(true);
        usleep(5_000);
        Collector::statementEnd($source, new Nop(['startLine' => 11]), $inner_start, 0);
        usleep(5_000);
        Collector::statementEnd($source, new Nop(['startLine' => 10]), $outer_start, 0);

        $result = Collector::finish(0, 0);

        $this->assertNotNull($result);
        $wall = array_column($result['top']['statements'], 'wall_us', 'line');
        $self = array_column($result['top']['statements'], 'self_us', 'line');

        // the outer one took about 10 ms with the inner one, and about 5 ms of its own
        $this->assertGreaterThanOrEqual(10_000, $wall[10]);
        $this->assertLessThan($wall[10] - 4_000, $self[10]);
        $this->assertGreaterThanOrEqual(5_000, $self[11]);
    }

    public function testDisabledCollectorRecordsNothing(): void
    {
        $this->assertFalse(Collector::$enabled);
        $this->assertSame('', Collector::stage('scan.files'));
        $this->assertNull(Collector::workerData());

        Collector::poolStart('scan', 2, 10);
        Collector::addWorkerData(self::workerPerf(1, [self::file('scan', 'src/A.php', 1, 10)]));
        Collector::poolEnd();

        $this->assertNull(Collector::finish(0, 0));
    }

    public function testHandlersAreNamedWithoutTheFileOfAnonymousClassesAndByOrigin(): void
    {
        Collector::start(null, []);
        $anonymous = (new class {
            /**
             * @psalm-capabilities read-props
             */
            public static function handle(object $event): void
            {
                unset($event);
            }
        })::class;
        $hooks = array_map(
            static fn(string $handler): array => ['handler' => $handler, 'hook' => 'H', 'calls' => 1, 'wall_ns' => 5_000],
            ['Psalm\\Internal\\Provider\\X', 'Psalm\\LaravelPlugin\\Handler'],
        );

        Collector::timedVoid($anonymous, 'handle', new stdClass(), 'AfterFileAnalysis');
        Collector::addWorkerData(self::workerPerf(1, [], $hooks));

        $result = Collector::finish(0, 0);

        $this->assertNotNull($result);
        $origins = array_column($result['hooks'], 'origin', 'handler');
        ksort($origins);
        $this->assertSame(
            ['Psalm\\Internal\\Provider\\X' => 'psalm', 'Psalm\\LaravelPlugin\\Handler' => 'plugin', '{anonymous-class}' => 'plugin'],
            $origins,
        );
        $this->assertStringNotContainsString(__DIR__, (string) json_encode($result['hooks']));
    }

    /**
     * @return array<string, array{string, string, ?string, string}> the path on disk, the path Psalm prints,
     *     the root of Psalm, what the dump shows
     * @psalm-pure
     */
    public static function displayPaths(): array
    {
        return [
            'project file' => ['/proj/src/A.php', 'src/A.php', null, 'src/A.php'],
            'above the project' => ['/x/vendor/y.php', '../vendor/y.php', null, '../vendor/y.php'],
            'absolute path of a missing file' => ['/home/me/src/A.php', '/home/me/src/A.php', null, '<external>/A.php'],
            'Psalm outside the project' => [
                '/opt/psalm/stubs/Core.phpstub',
                '../../opt/psalm/stubs/Core.phpstub',
                '/opt/psalm',
                '<psalm>/stubs/Core.phpstub',
            ],
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
        $previous = getenv('TRACEPARENT');
        putenv($value === null ? 'TRACEPARENT' : 'TRACEPARENT=' . $value);

        try {
            $this->assertSame($expected, Collector::traceparent());
        } finally {
            putenv($previous === false ? 'TRACEPARENT' : 'TRACEPARENT=' . $previous);
        }
    }

    public function testNoFileIsWrittenOverAReportOrTheBaseline(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Hard links need a privilege on some Windows setups');
        }

        $report = sys_get_temp_dir() . '/psalm-guard-report-' . uniqid() . '.json';
        $dump = $report . '.dump';
        file_put_contents($report, '{}');
        link($report, $dump);

        try {
            $this->assertSame($report, Collector::sameFileAs($dump, ['/no/such/baseline.xml', $report]));
            // a dump that doesn't exist yet is nobody's file
            $this->assertNull(Collector::sameFileAs($report . '.new', [$report]));
        } finally {
            unlink($dump);
            unlink($report);
        }
    }

    /**
     * @return FileRecord
     * @psalm-pure
     */
    private static function file(string $op, string $path, int $pid, int $wall_us): array
    {
        return [
            'op' => $op, 'path' => $path, 'pid' => $pid, 'start_us' => 0, 'wall_us' => $wall_us,
            'cpu_us' => $wall_us, 'mem_bytes' => 0, 'peak_growth_bytes' => 0,
        ];
    }

    /**
     * @param list<FileRecord> $files
     * @param list<HookRecord> $hooks
     * @return WorkerPerf
     * @psalm-pure
     */
    private static function workerPerf(int $pid, array $files, array $hooks = []): array
    {
        return [
            'pid' => $pid, 'role' => 'analysis', 'start_us' => 10, 'end_us' => 10_000, 'cpu_us' => 5_000,
            'cpu_sys_us' => 100, 'php_start_bytes' => 1_000, 'php_peak_bytes' => 2_000, 'max_rss_bytes' => 3_000,
            'files' => $files, 'statements' => [], 'functions' => [], 'hooks' => $hooks,
            'parser' => ['files_parsed' => 0, 'parser_cache_hits' => 0, 'parser_cache_misses' => 0],
        ];
    }
}
