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
use function hrtime;
use function putenv;
use function usleep;

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
        $this->assertSame([[271, 30, 1_200_000], [5, 1, 1_500]], array_map(
            static fn(array $r): array => [$r['line'], $r['passes'], $r['self_us']],
            $result['hot']['statements'],
        ));
        // each pass of the constructor is an entry of its own
        $this->assertSame(['body', 'initializations'], array_column($result['hot']['functions'], 'pass'));
        $this->assertSame([2, 2], array_column($result['hot']['functions'], 'passes'));
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
        $wall = array_column($result['hot']['statements'], 'wall_us', 'line');
        $self = array_column($result['hot']['statements'], 'self_us', 'line');

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
