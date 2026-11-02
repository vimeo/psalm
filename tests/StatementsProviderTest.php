<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use PhpParser\Node\Stmt;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\Provider\ParserCacheProvider;
use Psalm\Internal\Provider\StatementsProvider;
use Psalm\IssueBuffer;

use function array_column;
use function array_map;
use function str_replace;

final class StatementsProviderTest extends TestCase
{
    private const BROKEN = "<?php\nfunction f() {\n    echo 1\n}\n";

    // the parser recovers no statement from it
    private const UNPARSABLE = '<?php function f( {';

    private const FIXED = "<?php\nfunction f() {\n    echo 1;\n}\n";

    private const INVALID_PARTIAL_APPLICATION = "<?php\nfunction f(int \$a, int \$b): void {}\n\$c = f(..., ?);\n";

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

    public function testPartialApplicationCompileErrorIsReportedOnEveryRun(): void
    {
        $this->file_provider->registerFile($this->file, self::INVALID_PARTIAL_APPLICATION);

        for ($run = 1; $run <= 3; $run++) {
            $this->getStatements();

            $this->assertSame(['Variadic placeholder must be last on line 3'], $this->takeIssueMessages(), "run $run");
        }
    }

    public function testPartialReparseReportsTheCompileErrorsOfTheStatementsItKeeps(): void
    {
        $before = <<<'PHP'
            <?php
            final class A {
                public function a(): void
                {
                    echo 1;
                }

                public function b(): void
                {
                    $c = strlen(..., ?);
                }
            }
            PHP;
        $this->file_provider->registerFile($this->file, $before);
        $this->statements_provider->getStatementsForFile($this->file, 8_06_00, true);
        IssueBuffer::clear();

        // only a() is parsed again, and b() moves down a line
        $this->file_provider->setContents($this->file, str_replace('echo 1;', "echo 1;\n        echo 2;", $before));
        $this->statements_provider->getStatementsForFile($this->file, 8_06_00, true);

        $this->assertSame(
            [[11, 14, 'Variadic placeholder must be last on line 11']],
            array_map(
                static fn(IssueData $issue): array => [$issue->line_from, $issue->column_from, $issue->message],
                IssueBuffer::clear()[$this->file] ?? [],
            ),
        );
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

    /**
     * @return list<string>
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function takeIssueMessages(): array
    {
        return array_column(IssueBuffer::clear()[$this->file] ?? [], 'message');
    }
}
