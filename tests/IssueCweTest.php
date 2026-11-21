<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\VersionUtils;
use Psalm\Issue\CodeIssue;
use Psalm\Report\CompactReport;
use Psalm\Report\ConsoleReport;
use Psalm\Report\ReportOptions;
use Psalm\Report\TextReport;
use ReflectionClass;

use function array_map;
use function basename;
use function define;
use function defined;
use function file_exists;
use function file_get_contents;
use function glob;
use function is_subclass_of;
use function preg_match_all;
use function sort;
use function strval;

final class IssueCweTest extends TestCase
{
    #[Override]
    public static function setUpBeforeClass(): void
    {
        if (!defined('PSALM_VERSION')) {
            define('PSALM_VERSION', VersionUtils::getPsalmVersion());
        }

        parent::setUpBeforeClass();
    }

    public function testTheCweIdsOfAnIssueAreTheOnesItsDocumentationLinks(): void
    {
        $files = glob(__DIR__ . '/../src/Psalm/Issue/*.php');
        $this->assertIsArray($files);
        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $type = basename($file, '.php');
            $class = 'Psalm\\Issue\\' . $type;
            $doc_path = __DIR__ . '/../docs/running_psalm/issues/' . $type . '.md';

            if (!is_subclass_of($class, CodeIssue::class)
                || (new ReflectionClass($class))->isAbstract()
                || !file_exists($doc_path)
            ) {
                continue;
            }

            preg_match_all('/\[CWE-(\d+)\]/', (string) file_get_contents($doc_path), $matches);
            $documented = $matches[1];
            sort($documented);

            /** @var list<positive-int> $cwe_ids */
            $cwe_ids = $class::CWE_IDS;
            $declared = array_map(strval(...), $cwe_ids);
            sort($declared);

            $this->assertSame($documented, $declared, $type);
        }
    }

    /**
     * @return iterable<string, array{class-string<CompactReport|ConsoleReport|TextReport>, string}>
     * @psalm-pure
     */
    public static function reports(): iterable
    {
        yield 'compact' => [CompactReport::class, 'TaintedFile: Detected tainted file handling (CWE-22, CWE-73)'];
        yield 'console' => [ConsoleReport::class, 'Detected tainted file handling (CWE-22, CWE-73)'];
        yield 'text' => [TextReport::class, 'Detected tainted file handling (CWE-22, CWE-73)'];
    }

    /**
     * @param class-string<CompactReport|ConsoleReport|TextReport> $report_class
     */
    #[DataProvider('reports')]
    public function testHumanReadableReportsFollowTheMessageWithTheCweIds(string $report_class, string $expected): void
    {
        $options = new ReportOptions();
        $options->use_color = false;
        $options->show_snippet = false;

        $issues_data = [
            self::createIssueData('TaintedFile', 'Detected tainted file handling', [22, 73]),
            self::createIssueData('InvalidArgument', 'An issue without weakness', []),
        ];

        $report = match ($report_class) {
            CompactReport::class => new CompactReport($issues_data, [], $options),
            ConsoleReport::class => new ConsoleReport($issues_data, [], $options),
            TextReport::class => new TextReport($issues_data, [], $options),
        };

        $output = $report->create();

        $this->assertStringContainsString($expected, $output);
        $this->assertStringContainsString('An issue without weakness', $output);
        $this->assertStringNotContainsString('An issue without weakness (', $output);
    }

    /**
     * @param list<positive-int> $cwe_ids
     * @psalm-pure
     */
    private static function createIssueData(string $type, string $message, array $cwe_ids): IssueData
    {
        return new IssueData(
            IssueData::SEVERITY_ERROR,
            1,
            1,
            $type,
            $message,
            'file.php',
            '/file.php',
            '',
            '',
            0,
            0,
            0,
            0,
            1,
            1,
            cwe_ids: $cwe_ids,
        );
    }
}
