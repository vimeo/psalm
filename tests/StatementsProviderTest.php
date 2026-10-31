<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Internal\Provider\ParserCacheProvider;
use Psalm\Internal\Provider\StatementsProvider;
use Psalm\IssueBuffer;

use function array_column;

use const DIRECTORY_SEPARATOR;

final class StatementsProviderTest extends TestCase
{
    private const BROKEN_CODE = '<?php function f() { echo 1 }';

    public function testAFileWithoutParseErrorsHasNoCachedParseErrors(): void
    {
        $file_path = $this->registerFile('<?php function f(): void {}');
        $cache = new ParserCacheProvider($this->testConfig, '', false);

        (new StatementsProvider($this->file_provider, $cache))
            ->getStatementsForFile($file_path, 80_400, false);

        $this->assertNotNull($cache->loadStatementsFromCache($file_path, '<?php function f(): void {}'));
        $this->assertNull($cache->loadParseErrorsFromCache($file_path, '<?php function f(): void {}'));
    }

    public function testParseErrorsAreCachedWithTheStatements(): void
    {
        $file_path = $this->registerFile(self::BROKEN_CODE);
        $cache = new ParserCacheProvider($this->testConfig, '', false);

        (new StatementsProvider($this->file_provider, $cache))
            ->getStatementsForFile($file_path, 80_400, false);

        $this->assertNotNull($cache->loadStatementsFromCache($file_path, self::BROKEN_CODE));

        $errors = $cache->loadParseErrorsFromCache($file_path, self::BROKEN_CODE);
        $this->assertNotNull($errors);
        $this->assertCount(1, $errors);
        $this->assertStringContainsString('Syntax error', $errors[0][0]);
        $this->assertNull($cache->loadParseErrorsFromCache($file_path, '<?php function f() { echo 1; }'));
    }

    public function testParseErrorIsReportedAgainOnACacheHit(): void
    {
        $file_path = $this->registerFile(self::BROKEN_CODE);
        $provider = new StatementsProvider(
            $this->file_provider,
            new ParserCacheProvider($this->testConfig, '', false),
        );

        $provider->getStatementsForFile($file_path, 80_400, false);
        $first = $this->getIssueTypes();
        IssueBuffer::clear();

        $provider->getStatementsForFile($file_path, 80_400, false);

        $this->assertSame(['ParseError'], $first);
        $this->assertSame($first, $this->getIssueTypes());
    }

    private function registerFile(string $contents): string
    {
        $file_path = $this->testConfig->base_dir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'somefile.php';
        $this->testConfig->throw_exception = false;
        $this->file_provider->registerFile($file_path, $contents);
        $this->project_analyzer->initProjectFiles();
        $this->project_analyzer->getCodebase()->addFilesToAnalyze([$file_path => $file_path]);

        return $file_path;
    }

    /**
     * @return list<string>
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function getIssueTypes(): array
    {
        $types = [];

        foreach (IssueBuffer::getIssuesData() as $issues) {
            $types = [...$types, ...array_column($issues, 'type')];
        }

        return $types;
    }
}
