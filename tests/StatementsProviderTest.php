<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use PhpParser\Node\Stmt;
use PhpParser\NodeDumper;
use Psalm\Config;
use Psalm\Internal\Provider\ParserCacheProvider;
use Psalm\Internal\Provider\StatementsProvider;
use Psalm\IssueBuffer;

use function array_column;
use function str_replace;
use function sys_get_temp_dir;
use function uniqid;

use const DIRECTORY_SEPARATOR;

final class StatementsProviderTest extends TestCase
{
    private const BROKEN = "<?php\nfunction f() {\n    echo 1\n}\n";

    // the parser recovers no statement from it
    private const UNPARSABLE = '<?php function f( {';

    private const FIXED = "<?php\nfunction f() {\n    echo 1;\n}\n";

    private string $file;

    private StatementsProvider $statements_provider;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->testConfig->throw_exception = false;
        $this->file = self::$src_dir_path . 'somefile.php';

        // only the issues of the files to analyze are reported
        $this->project_analyzer->getCodebase()->addFilesToAnalyze([$this->file => $this->file]);

        $this->statements_provider = new StatementsProvider(
            $this->file_provider,
            new ParserCacheProvider($this->testConfig, '', false),
        );
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

    /**
     * A warm persistent cache holds the previous contents of the file as its hash, so a small edit
     * is parsed partially: only the changed method is reparsed and the result is that of a cold parse.
     */
    public function testEditedMethodBodyIsReparsedPartiallyFromAWarmPersistentCache(): void
    {
        $before = "<?php\nclass A {\n    public function f(): int {\n        return 1;\n    }\n\n"
            . "    public function g(): int {\n        return 2;\n    }\n}\n";
        $after = str_replace('return 1;', 'return 11 + 1;', $before);

        $cache_directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('psalm-statements-provider-test-', true);

        $config = new TestConfig();
        $config->cache_directory = $cache_directory;

        try {
            $this->file_provider->registerFile($this->file, $before);
            $this->parseInANewRun($config);

            // the previous contents come from the cache directory, nothing is kept in memory between runs
            $this->file_provider->setContents($this->file, $after);
            [$warm, $unchanged_signature_members] = $this->parseInANewRun($config);
        } finally {
            Config::removeCacheDirectory($cache_directory);
        }

        // the diff only runs when the previous contents were found: f() was reparsed, g() was reused
        $this->assertSame(['a::f' => true], $unchanged_signature_members);

        $dumper = new NodeDumper();
        $this->assertSame(
            $dumper->dump(StatementsProvider::parseStatements($after, 7_04_00)),
            $dumper->dump($warm),
        );
    }

    /**
     * The providers are released on return: their caches hold a shared lock on the cache directory until then,
     * which removing the directory would wait for forever
     *
     * @return array{list<Stmt>, array<string, bool>|null}
     */
    private function parseInANewRun(TestConfig $config): array
    {
        $provider = new StatementsProvider($this->file_provider, new ParserCacheProvider($config, ''));
        $stmts = $provider->getStatementsForFile($this->file, 7_04_00, true);

        return [$stmts, $provider->getUnchangedSignatureMembers()[$this->file] ?? null];
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
