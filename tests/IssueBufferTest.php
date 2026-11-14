<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Codebase;
use Psalm\Config;
use Psalm\Context;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Codebase\Analyzer;
use Psalm\Internal\EventDispatcher;
use Psalm\IssueBuffer;
use Psalm\Report;
use Psalm\Report\ReportOptions;

use function array_filter;
use function array_map;
use function in_array;
use function ksort;
use function microtime;
use function ob_get_clean;
use function ob_start;
use function sort;

final class IssueBufferTest extends TestCase
{

    public function testFinishDoesNotCorruptInternalState(): void
    {
        IssueBuffer::clear();
        IssueBuffer::addIssues([
            '/path/one.php' => [
                new IssueData(
                    IssueData::SEVERITY_ERROR,
                    0,
                    0,
                    "MissingPropertyType",
                    'Message',
                    "one.php",
                    "/path/one.php",
                    "snippet-1",
                    "snippet-1",
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                ),
            ],
            '/path/two.php' => [
                new IssueData(
                    IssueData::SEVERITY_ERROR,
                    0,
                    0,
                    "MissingPropertyType",
                    'Message',
                    "two.php",
                    "/path/two.php",
                    "snippet-2",
                    "snippet-2",
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                ),
            ],
            '/path/three.php' => [
                new IssueData(
                    IssueData::SEVERITY_ERROR,
                    0,
                    0,
                    "MissingPropertyType",
                    'Message',
                    "three.php",
                    "/path/three.php",
                    "snippet-3-has-carriage-return\r",
                    "snippet-3-has-carriage-return\r",
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                ),
            ],
            '/path/four.php' => [
                new IssueData(
                    IssueData::SEVERITY_ERROR,
                    0,
                    0,
                    "MissingPropertyType",
                    'Message',
                    "four.php",
                    "/path/four.php",
                    "snippet-4-multiline\r\nwith-carriage-return\r",
                    "snippet-4-multiline\r\nwith-carriage-return\r",
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                ),
            ],
        ]);
        $baseline = [
            'one.php' => ['MissingPropertyType' => ['o' => 1, 's' => ['snippet-1']] ],
            'two.php' => ['MissingPropertyType' => ['o' => 1, 's' => ['snippet-2']] ],
            'three.php' => ['MissingPropertyType' => ['o' => 1, 's' => ['snippet-3-has-carriage-return']] ],
            'four.php' => ['MissingPropertyType' => ['o' => 1, 's' => ["snippet-4-multiline\nwith-carriage-return"]] ],
        ];

        $analyzer = $this->createMock(Analyzer::class);
        $analyzer->method('getTotalTypeCoverage')->willReturn([0, 0]);

        $eventDispatcher = $this->createMock(EventDispatcher::class);

        $config = $this->createMock(Config::class);
        $config->eventDispatcher = $eventDispatcher;
        $config->method('isSuppressible')->willReturn(true);

        $codebase = $this->createMock(Codebase::class);
        $codebase->analyzer = $analyzer;
        $codebase->config = $config;

        $projectAnalyzer = $this->createMock(ProjectAnalyzer::class);
        $projectAnalyzer->method('getCodebase')->willReturn($codebase);

        $projectAnalyzer->stdout_report_options = new ReportOptions();
        // the report goes to STDOUT, keep the baselined issues out of it
        $projectAnalyzer->stdout_report_options->show_info = false;
        $projectAnalyzer->generated_report_options = [];

        ob_start();
        IssueBuffer::finish($projectAnalyzer, false, microtime(true), false, $baseline);
        $output = (string) ob_get_clean();
        $this->assertStringContainsString('No errors found!', $output, 'all issues baselined');
        IssueBuffer::clear();
    }

    /**
     * Messages render types exactly, so one location analysed with different exact types (a trait method per
     * using class, a call on a union of receivers) reports each distinct type once.
     */
    public function testIssuesWithDistinctExactTypesAtOneLocationAreAllReported(): void
    {
        $this->testConfig->throw_exception = false;
        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                trait HasTable {
                    abstract protected function table(): ?string;

                    public function q(): string {
                        return "select * from " . $this->table();
                    }
                }

                final class Users {
                    use HasTable;

                    /** @return non-empty-string|null */
                    protected function table(): ?string {
                        return rand(0, 1) ? "users" : null;
                    }
                }

                final class Ids {
                    use HasTable;

                    /** @return numeric-string|null */
                    protected function table(): ?string {
                        return rand(0, 1) ? "1" : null;
                    }
                }

                interface MarkerOne {}

                interface MarkerTwo {}

                /** @template T as string */
                class Takes {
                    /** @param T $s */
                    public function f(string $s): void {}
                }

                /** @param (Takes<numeric-string>&MarkerOne)|(Takes<non-empty-string>&MarkerTwo) $t */
                function checkUnionReceiver($t): void {
                    $t->f(json_decode("null"));
                }',
        );

        $this->analyzeFile($file_path, new Context());

        $issues = array_map(
            static fn(IssueData $issue): array => [
                $issue->line_from,
                $issue->column_from,
                $issue->type,
                $issue->message,
            ],
            array_filter(
                IssueBuffer::getIssuesDataForFile($file_path),
                static fn(IssueData $issue): bool => in_array(
                    $issue->type,
                    ['PossiblyNullOperand', 'MixedArgument'],
                    true,
                ),
            ),
        );
        sort($issues);

        self::assertSame(
            [
                [6, 51, 'PossiblyNullOperand', 'Cannot concatenate with a possibly null non-empty-string|null'],
                [6, 51, 'PossiblyNullOperand', 'Cannot concatenate with a possibly null null|numeric-string'],
                [40, 27, 'MixedArgument', 'Argument 1 of Takes::f cannot be mixed, expecting non-empty-string'],
                [40, 27, 'MixedArgument', 'Argument 1 of Takes::f cannot be mixed, expecting numeric-string'],
            ],
            $issues,
        );
    }

    public function testPrintSuccessMessageWorks(): void
    {
        $project_analyzer = $this->createMock(ProjectAnalyzer::class);
        $project_analyzer->stdout_report_options = new ReportOptions;
        ob_start();
        IssueBuffer::printSuccessMessage($project_analyzer);
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('No errors found!', $output);
    }

    /**
     * @param Report::TYPE_* $format
     * @psalm-pure
     */
    private static function reportOptions(string $format): ReportOptions
    {
        $options = new ReportOptions();
        $options->format = $format;

        return $options;
    }

    public function testASarifReportAmongTheGeneratedReportsIsRecognised(): void
    {
        self::assertTrue(IssueBuffer::hasSarifReport(
            [self::reportOptions(Report::TYPE_JSON), self::reportOptions(Report::TYPE_SARIF)],
        ));
    }

    public function testOtherReportsAreNotSarif(): void
    {
        self::assertFalse(IssueBuffer::hasSarifReport(
            [self::reportOptions(Report::TYPE_JSON), self::reportOptions(Report::TYPE_CHECKSTYLE)],
        ));
    }

    public function testNoGeneratedReportIsNotSarif(): void
    {
        self::assertFalse(IssueBuffer::hasSarifReport([]));
    }

    /**
     * The issues of a group that two workers send, in either order, leave the one at the earliest location: by file
     * path, then line, then column. An issue of another type or message, or of no group, stays.
     */
    #[DataProvider('providerWorkerOrders')]
    public function testGroupedIssuesOfTwoWorkersLeaveTheEarliest(bool $reverse): void
    {
        self::addIssuesOfTwoWorkers($reverse);

        self::assertSame(
            [
                '/path/a.php' => ['a 3:4', 'a 3:4 UndefinedClass', 'a 3:4 Unknown2'],
                '/path/c.php' => ['c ungrouped'],
            ],
            self::labels(IssueBuffer::getIssuesData()),
        );
    }

    /**
     * The issues held back for their group are kept with the others, so that a later analysis (of the changed
     * a.php, which no longer has its issues) reports the earliest of the rest from the cache
     */
    #[DataProvider('providerWorkerOrders')]
    public function testHeldBackIssueIsReportedOnceTheReportedOneIsGone(bool $reverse): void
    {
        self::addIssuesOfTwoWorkers($reverse);
        $cached_issues = IssueBuffer::getAllIssuesData();
        unset($cached_issues['/path/a.php']);

        IssueBuffer::clear();
        IssueBuffer::addIssues($cached_issues);

        self::assertSame(
            ['/path/b.php' => ['b 1:1'], '/path/c.php' => ['c ungrouped']],
            self::labels(IssueBuffer::getIssuesData()),
        );
    }

    /**
     * @param Closure(): mixed $clear
     */
    #[DataProvider('providerClears')]
    public function testClearingForgetsTheGroups(Closure $clear): void
    {
        IssueBuffer::clear();
        IssueBuffer::addIssues([
            '/path/a.php' => [self::issue('a.php', 3, 4, 'a 3:4')],
            '/path/b.php' => [self::issue('b.php', 1, 1, 'b 1:1')],
        ]);

        $clear();

        self::assertSame([], IssueBuffer::getAllIssuesData());

        IssueBuffer::addIssues(['/path/b.php' => [self::issue('b.php', 1, 1, 'b 1:1')]]);

        self::assertSame(['/path/b.php' => ['b 1:1']], self::labels(IssueBuffer::getIssuesData()));
    }

    /**
     * @return iterable<string, array{bool}>
     * @psalm-pure
     */
    public static function providerWorkerOrders(): iterable
    {
        yield 'first worker first' => [false];
        yield 'second worker first' => [true];
    }

    /**
     * @return iterable<string, array{Closure(): mixed}>
     * @psalm-pure
     */
    public static function providerClears(): iterable
    {
        yield 'clear' => [IssueBuffer::clear(...)];
        yield 'clearCache' => [IssueBuffer::clearCache(...)];
    }

    /**
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private static function addIssuesOfTwoWorkers(bool $reverse): void
    {
        $first_worker = [
            '/path/b.php' => [self::issue('b.php', 1, 1, 'b 1:1')],
            '/path/c.php' => [self::issue('c.php', 5, 5, 'c ungrouped', group_key: null)],
        ];
        $second_worker = [
            '/path/a.php' => [
                self::issue('a.php', 3, 9, 'a 3:9'),
                self::issue('a.php', 3, 4, 'a 3:4'),
                self::issue('a.php', 8, 1, 'a 8:1'),
                self::issue('a.php', 3, 4, 'a 3:4 Unknown2', 'Unknown2'),
                self::issue('a.php', 3, 4, 'a 3:4 UndefinedClass', type: 'UndefinedClass'),
            ],
        ];

        IssueBuffer::clear();

        foreach ($reverse ? [$second_worker, $first_worker] : [$first_worker, $second_worker] as $worker_issues) {
            IssueBuffer::addIssues($worker_issues);
        }
    }

    /**
     * @param string $label told apart from the others by the selected text
     * @psalm-pure
     */
    private static function issue(
        string $file_name,
        int $line,
        int $column,
        string $label,
        string $class = 'Unknown',
        ?string $group_key = '/vendor/lib.php:120',
        string $type = 'UndefinedDocblockClass',
    ): IssueData {
        return new IssueData(
            IssueData::SEVERITY_ERROR,
            $line,
            $line,
            $type,
            'Docblock-defined class, interface or enum named ' . $class . ' does not exist',
            $file_name,
            '/path/' . $file_name,
            '',
            $label,
            0,
            0,
            0,
            0,
            $column,
            $column,
            group_key: $group_key,
        );
    }

    /**
     * @param array<string, list<IssueData>> $issues_data
     * @return array<string, list<string>>
     * @psalm-capabilities read-props
     */
    private static function labels(array $issues_data): array
    {
        $labels = [];

        foreach ($issues_data as $file_path => $file_issues) {
            $file_labels = [];

            foreach ($file_issues as $issue) {
                $file_labels[] = $issue->selected_text;
            }

            sort($file_labels);
            $labels[$file_path] = $file_labels;
        }

        ksort($labels);

        return $labels;
    }
}
