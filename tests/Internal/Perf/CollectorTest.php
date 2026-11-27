<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Perf;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PhpParser\Node\Stmt\Nop;
use Psalm\Internal\Perf\Collector;
use Psalm\StatementsSource;

use function array_column;
use function array_map;
use function getenv;
use function putenv;

/**
 * @psalm-import-type WorkerPerf from Collector
 * @psalm-import-type FileRecord from Collector
 * @psalm-import-type StatementRecord from Collector
 * @psalm-import-type FunctionRecord from Collector
 */
final class CollectorTest extends TestCase
{
    /** The nanoseconds Collector reads as the time; a test moves it with advance() instead of waiting */
    private int $now_ns = 1_000_000_000;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        Collector::setClock(fn(): int => $this->now_ns);
    }

    #[Override]
    protected function tearDown(): void
    {
        Collector::reset();
        Collector::setClock(null);
        parent::tearDown();
    }

    /**
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function advance(int $microseconds): void
    {
        $this->now_ns += $microseconds * 1_000;
    }

    public function testExecutionsOfOneStatementAreOneHotItemWithTheirPasses(): void
    {
        Collector::start(null, []);

        for ($i = 0; $i < 30; ++$i) {
            Collector::recordStatement('src/F.php', 271, 'Stmt_If', 40_000, 41_000);
        }

        Collector::recordStatement('src/F.php', 5, 'Stmt_Echo', 1_500, 1_500);

        for ($i = 0; $i < 2; ++$i) {
            Collector::recordFunction('src/F.php', 20, 'A::__construct', 'initializations', 20_000, 20_000);
            Collector::recordFunction('src/F.php', 20, 'A::__construct', 'body', 20_000, 20_000);
        }

        $result = Collector::finish(0, 0);

        $this->assertNotNull($result);
        $this->assertSame([[271, 30, 1_200_000], [5, 1, 1_500]], array_map(
            static fn(array $r): array => [$r['line'], $r['passes'], $r['self_us']],
            $result['hot']['statements'],
        ));
        // each pass of the constructor is an entry of its own
        $this->assertSame(['body', 'initializations'], array_column($result['hot']['functions'], 'pass'));
        $this->assertSame([2, 2], array_column($result['hot']['functions'], 'passes'));
    }

    public function testAddWorkerDataMergesWhatTheWorkersRecorded(): void
    {
        Collector::start(null, []);
        Collector::poolStart('analysis', 2);
        $statement = ['path' => 'src/F.php', 'line' => 3, 'node' => 'Stmt_If', 'passes' => 2, 'self_us' => 5, 'wall_us' => 7];
        $function = ['path' => 'src/F.php', 'line' => 1, 'id' => 'f', 'pass' => 'body', 'passes' => 1, 'self_us' => 4,
            'wall_us' => 9];

        Collector::addWorkerData(self::workerPerf(
            101,
            [self::file('analysis', 'src/A.php', 101, 1_000), self::file('scan', 'src/Lazy.php', 101, 400)],
            [$statement],
            [$function],
        ));
        Collector::addWorkerData(self::workerPerf(102, [self::file('analysis', 'src/B.php', 102, 500)], [$statement], [$function]));
        Collector::poolEnd();

        $result = Collector::finish(0, 0);

        $this->assertNotNull($result);
        $this->assertSame([[4, 10, 14]], array_map(
            static fn(array $r): array => [$r['passes'], $r['self_us'], $r['wall_us']],
            $result['hot']['statements'],
        ));
        $this->assertSame([[2, 8, 18]], array_map(
            static fn(array $r): array => [$r['passes'], $r['self_us'], $r['wall_us']],
            $result['hot']['functions'],
        ));
        // the scan inside the analysis of a file is part of it, so it is not busy time of its own
        $this->assertSame([[101, 1, 1_000], [102, 1, 500]], array_map(
            static fn(array $worker): array => [$worker['pid'], $worker['tasks'], $worker['busy_us']],
            $result['pools'][0]['workers'],
        ));
        $this->assertSame(
            ['parses' => 4, 'cache_hits' => 6],
            $result['parser'],
        );
    }

    public function testNestedStatementsAreTimedByTheirOwnTime(): void
    {
        Collector::start(null, []);
        $source = $this->createStub(StatementsSource::class);
        $source->method('getFilePath')->willReturn('/p/src/N.php');
        $source->method('getFileName')->willReturn('src/N.php');

        // as StatementsAnalyzer does: the outer statement starts, and its child is analysed inside it
        $outer_start = Collector::nowNs();
        Collector::$child_ns = 0;
        $inner_start = Collector::nowNs();
        $this->advance(5_000);
        Collector::statementEnd($source, new Nop(['startLine' => 11]), $inner_start, 0);
        $this->advance(5_000);
        Collector::statementEnd($source, new Nop(['startLine' => 10]), $outer_start, 0);

        $result = Collector::finish(0, 0);

        $this->assertNotNull($result);
        $wall = array_column($result['hot']['statements'], 'wall_us', 'line');
        $self = array_column($result['hot']['statements'], 'self_us', 'line');

        // the outer one took 10 ms with the inner one, and 5 ms of its own
        $this->assertSame(10_000, $wall[10]);
        $this->assertSame(5_000, $self[10]);
        $this->assertSame(5_000, $wall[11]);
        $this->assertSame(5_000, $self[11]);
    }

    public function testFunctionLikePassesAreRankedByTheirOwnTime(): void
    {
        Collector::start(null, []);

        // an outer pass of 100 ms, with an inner pass of 60 ms in it
        $outer = Collector::functionStart();
        $this->advance(40_000);
        $inner = Collector::functionStart();
        $this->advance(60_000);
        Collector::functionEnd('/p/src/F.php', 'src/F.php', 20, 'inner', false, $inner);
        Collector::functionEnd('/p/src/F.php', 'src/F.php', 10, 'outer', false, $outer);

        $result = Collector::finish(0, 0);

        $this->assertNotNull($result);
        // the inner pass took more of its own time than the outer one, though the outer one took longer in all
        $this->assertSame(['inner', 'outer'], array_column($result['hot']['functions'], 'id'));
        $this->assertSame(
            ['inner' => 60_000, 'outer' => 40_000],
            array_column($result['hot']['functions'], 'self_us', 'id'),
        );
        $this->assertSame(
            ['inner' => 60_000, 'outer' => 100_000],
            array_column($result['hot']['functions'], 'wall_us', 'id'),
        );
    }

    public function testDisabledCollectorRecordsNothing(): void
    {
        $this->assertFalse(Collector::$enabled);
        $this->assertSame('', Collector::stage('scan.files'));
        $this->assertNull(Collector::workerData());

        Collector::poolStart('scan', 10);
        Collector::addWorkerData(self::workerPerf(1, [self::file('scan', 'src/A.php', 1, 10)]));
        Collector::poolEnd();

        $this->assertNull(Collector::finish(0, 0));
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
            'absolute path of a missing file' => ['/home/me/src/A.php', '/home/me/src/A.php', null, '<external>/A.php'],
            'file of another phar' => [
                'phar:///x/other.phar/a/B.php',
                '../../phar:///x/other.phar/a/B.php',
                '/opt/psalm',
                '<external>/other.phar/a/B.php',
            ],
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
            'malformed' => ['00-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331', null],
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

    /**
     * @return FileRecord
     * @psalm-pure
     */
    private static function file(string $op, string $path, int $pid, int $wall_us): array
    {
        return [
            'op' => $op, 'path' => $path, 'pid' => $pid, 'start_us' => 0, 'wall_us' => $wall_us,
            'cpu_us' => $wall_us, 'peak_growth_bytes' => 0,
        ];
    }

    /**
     * @param list<FileRecord> $files
     * @param list<StatementRecord> $statements
     * @param list<FunctionRecord> $functions
     * @return WorkerPerf
     * @psalm-pure
     */
    private static function workerPerf(int $pid, array $files, array $statements = [], array $functions = []): array
    {
        return [
            'pid' => $pid, 'role' => 'analysis', 'start_us' => 10, 'end_us' => 10_000, 'cpu_us' => 5_000,
            'cpu_sys_us' => 100, 'php_start_bytes' => 1_000, 'peak_growth_bytes' => 2_000, 'max_rss_bytes' => 3_000,
            'files' => $files, 'statements' => $statements, 'functions' => $functions, 'hooks' => [],
            'parser' => ['parses' => 2, 'cache_hits' => 3],
        ];
    }
}
