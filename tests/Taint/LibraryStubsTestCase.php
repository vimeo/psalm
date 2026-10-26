<?php

declare(strict_types=1);

namespace Psalm\Tests\Taint;

use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Context;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\IssueBuffer;
use Psalm\Tests\TestCase;

use function array_any;
use function array_filter;
use function array_map;
use function array_values;
use function getcwd;
use function sort;
use function str_starts_with;

use const DIRECTORY_SEPARATOR;

/**
 * Runs code using libraries Psalm ships stubs for (see stubs/libraries). The code of getLibraries() declares classes
 * of the libraries, as their sources do, so that Psalm loads their stubs. Each case lists every issue it reports
 * whose type starts with one of getReportedIssueTypePrefixes() (by default the taint issues), by line.
 */
abstract class LibraryStubsTestCase extends TestCase
{
    /**
     * The code declaring classes of the libraries, in a file of its own
     *
     * @psalm-pure
     */
    abstract protected static function getLibraries(): string;

    /**
     * @return array<string, array{string, list<string>}>
     */
    abstract public static function providerTaints(): array;

    /**
     * The directory of the library stubs: by default Psalm's
     *
     * @psalm-pure
     */
    protected static function getLibraryStubsDir(): ?string
    {
        return null;
    }

    /**
     * The prefixes of the types of the issues the cases list
     *
     * @return non-empty-list<string>
     * @psalm-pure
     */
    protected static function getReportedIssueTypePrefixes(): array
    {
        return ['Tainted'];
    }

    /**
     * @param list<string> $expected_issues
     */
    #[DataProvider('providerTaints')]
    public function testTaints(string $code, array $expected_issues): void
    {
        $this->testConfig->throw_exception = false;
        $this->testConfig->library_stubs_dir = static::getLibraryStubsDir();
        $this->project_analyzer->setPhpVersion('8.1', 'tests');
        // outside the project directories, as the libraries are: a stub doesn't override the classes of those
        $this->addFile(
            (string) getcwd() . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'libraries.php',
            static::getLibraries(),
        );
        $file_path = self::$src_dir_path . 'somefile.php';
        $this->addFile($file_path, $code);
        $this->project_analyzer->trackTaintedInputs();

        $this->analyzeFile($file_path, new Context(), false);

        $prefixes = static::getReportedIssueTypePrefixes();
        $issues = array_values(array_filter(
            array_map(
                static fn(IssueData $issue): string => $issue->type . ' on line ' . $issue->line_from,
                IssueBuffer::getIssuesDataForFile($file_path),
            ),
            static fn(string $issue): bool => array_any(
                $prefixes,
                static fn(string $prefix): bool => str_starts_with($issue, $prefix),
            ),
        ));
        sort($issues);

        self::assertSame($expected_issues, $issues);
    }
}
