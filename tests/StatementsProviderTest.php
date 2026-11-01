<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use PHPUnit\Framework\MockObject\Stub;
use PhpParser\Node\Stmt;
use Psalm\Internal\Provider\ParserCacheProvider;
use Psalm\Internal\Provider\StatementsProvider;
use Psalm\IssueBuffer;

use function array_column;

final class StatementsProviderTest extends TestCase
{
    private const BROKEN = "<?php\nfunction f() {\n    echo 1\n}\n";

    // the parser recovers no statement from it
    private const UNPARSABLE = '<?php function f( {';

    private const FIXED = "<?php\nfunction f() {\n    echo 1;\n}\n";

    private string $file;

    private bool $miss_next_load = false;

    private ParserCacheProvider&Stub $parser_cache_provider;

    private StatementsProvider $statements_provider;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->testConfig->throw_exception = false;
        $this->file = self::$src_dir_path . 'somefile.php';

        // only the issues of the files to analyze are reported
        $this->project_analyzer->getCodebase()->addFilesToAnalyze([$this->file => $this->file]);

        $cache = new ParserCacheProvider($this->testConfig, '', false);

        // delegating to a real cache lets a test make a load miss as it does when another thread writes in between
        $this->parser_cache_provider = $this->createStub(ParserCacheProvider::class);
        $this->parser_cache_provider->method('loadStatementsFromCache')->willReturnCallback(
            function (string $file_path, ?string $file_content_hash) use ($cache): ?array {
                if ($this->miss_next_load) {
                    $this->miss_next_load = false;

                    return null;
                }

                return $cache->loadStatementsFromCache($file_path, $file_content_hash);
            },
        );
        $this->parser_cache_provider->method('saveStatementsToCache')->willReturnCallback(
            $cache->saveStatementsToCache(...),
        );
        $this->parser_cache_provider->method('getHash')->willReturnCallback($cache->getHash(...));

        $this->statements_provider = new StatementsProvider($this->file_provider, $this->parser_cache_provider);
    }

    public function testParseErrorIsReportedOnEveryRunUntilTheFileIsFixed(): void
    {
        $this->file_provider->registerFile($this->file, self::BROKEN);

        for ($run = 1; $run <= 3; $run++) {
            $this->getStatements();

            $this->assertSame(['ParseError'], $this->takeIssueTypes(), "run $run");
        }

        $this->file_provider->setContents($this->file, self::FIXED);

        for ($run = 1; $run <= 2; $run++) {
            $this->getStatements();

            $this->assertSame([], $this->takeIssueTypes(), "fixed, run $run");
            $this->assertSame([], $this->statements_provider->getErrors(), "fixed, run $run");
        }
    }

    public function testFileWithoutStatementsIsFlaggedOnEveryRun(): void
    {
        $this->file_provider->registerFile($this->file, self::UNPARSABLE);

        for ($run = 1; $run <= 3; $run++) {
            $this->assertSame([], $this->getStatements(), "run $run");

            $this->assertSame([$this->file => true], $this->statements_provider->getErrors(), "run $run");
        }
    }

    public function testParseErrorIsReportedWhenAnotherThreadCachedTheFileFirst(): void
    {
        $this->file_provider->registerFile($this->file, self::UNPARSABLE);
        $this->getStatements();
        $this->takeIssueTypes();

        $this->miss_next_load = true;
        $this->statements_provider->getStatementsForFile($this->file, 7_04_00, true);

        $this->assertSame(['ParseError'], $this->takeIssueTypes());
        $this->assertSame([$this->file => true], $this->statements_provider->getErrors());
    }

    public function testParseErrorOutsideTheEditedMethodIsStillReported(): void
    {
        $contents = static fn(string $b_body): string => "<?php\nclass A {\n"
            . "    public function a(): void {\n        echo 1\n    }\n"
            . "    public function b(): void {\n        $b_body\n    }\n}\n";

        $this->file_provider->registerFile($this->file, $contents('echo 2;'));
        $this->statements_provider->getStatementsForFile($this->file, 7_04_00, true);
        $this->assertSame(['ParseError'], $this->takeIssueTypes());

        $this->file_provider->setContents($this->file, $contents('echo 3;'));

        for ($run = 1; $run <= 2; $run++) {
            $this->statements_provider->getStatementsForFile($this->file, 7_04_00, true);

            $this->assertSame(['ParseError'], $this->takeIssueTypes(), "edited, run $run");
        }
    }

    /**
     * @return list<Stmt>
     */
    private function getStatements(): array
    {
        return $this->statements_provider->getStatementsForFile($this->file, 7_04_00, false);
    }

    /**
     * @return list<string>
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function takeIssueTypes(): array
    {
        return array_column(IssueBuffer::clear()[$this->file] ?? [], 'type');
    }
}
