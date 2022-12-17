<?php

namespace Psalm;

use Psalm\Exception\ConfigException;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\BaselineFormatter\BaselineFormatterInterface;
use Psalm\Internal\BaselineFormatter\XmlBaselineFormatter;
use Psalm\Internal\Provider\FileProvider;

use function array_filter;
use function array_intersect;
use function array_merge;
use function array_reduce;
use function array_values;
use function ksort;
use function min;
use function sprintf;
use function str_replace;
use function trigger_error;

use const E_USER_DEPRECATED;

/**
 * @psalm-type psalmFormattedBaseline = array<string, array<string, array{o: int, s: array<int, string>}>>
 */
final class ErrorBaseline
{
    /**
     * @param psalmFormattedBaseline $existingIssues
     * @psalm-pure
     */
    public static function countTotalIssues(array $existingIssues): int
    {
        $totalIssues = 0;

        foreach ($existingIssues as $existingIssue) {
            $totalIssues += array_reduce(
                $existingIssue,
                /**
                 * @param array{o:int, s:array<int, string>} $existingIssue
                 */
                static fn(int $carry, array $existingIssue): int => $carry + $existingIssue['o'],
                0
            );
        }

        return $totalIssues;
    }

    /**
     * @param array<string, list<IssueData>> $issues
     * @return psalmFormattedBaseline
     */
    public static function create(
        FileProvider $fileProvider,
        string $baselineFile,
        array $issues,
        bool $include_php_versions,
        ?BaselineFormatterInterface $baseline_formatter = null
    ): array {
        if ($baseline_formatter === null) {
            trigger_error(
                sprintf(
                    'Not passing in a "%s" explicitly to "%s" is deprecated.',
                    BaselineFormatterInterface::class,
                    __METHOD__
                ),
                E_USER_DEPRECATED,
            );
            $baseline_formatter = new XmlBaselineFormatter();
        }
        $groupedIssues = self::countIssueTypesByFile($issues);
        self::writeToFile(
            $fileProvider,
            $baselineFile,
            $groupedIssues,
            $include_php_versions,
            $baseline_formatter,
        );
        return $groupedIssues;
    }

    /**
     * @return psalmFormattedBaseline
     * @throws ConfigException
     */
    public static function read(
        FileProvider $fileProvider,
        string $baselineFile,
        ?BaselineFormatterInterface $baseline_formatter = null
    ): array {
        if ($baseline_formatter === null) {
            trigger_error(
                sprintf(
                    'Not passing in a "%s" explicitly to "%s" is deprecated.',
                    BaselineFormatterInterface::class,
                    __METHOD__
                ),
                E_USER_DEPRECATED,
            );
            $baseline_formatter = new XmlBaselineFormatter();
        }

        if (!$fileProvider->fileExists($baselineFile)) {
            throw new ConfigException("{$baselineFile} does not exist or is not readable");
        }

        $content = $fileProvider->getContents($baselineFile);
        return $baseline_formatter->read($content);
    }

    /**
     * @param array<string, list<IssueData>> $issues
     * @return psalmFormattedBaseline
     * @throws ConfigException
     */
    public static function update(
        FileProvider $fileProvider,
        string $baselineFile,
        array $issues,
        bool $include_php_versions,
        ?BaselineFormatterInterface $baseline_formatter = null
    ): array {
        if ($baseline_formatter === null) {
            trigger_error(
                sprintf(
                    'Not passing in a "%s" explicitly to "%s" is deprecated.',
                    BaselineFormatterInterface::class,
                    __METHOD__
                ),
                E_USER_DEPRECATED,
            );
            $baseline_formatter = new XmlBaselineFormatter();
        }

        $existingIssues = self::read($fileProvider, $baselineFile, $baseline_formatter);
        $newIssues = self::countIssueTypesByFile($issues);

        foreach ($existingIssues as $file => &$existingIssuesCount) {
            if (!isset($newIssues[$file])) {
                unset($existingIssues[$file]);

                continue;
            }

            foreach ($existingIssuesCount as $issueType => $existingIssueType) {
                if (!isset($newIssues[$file][$issueType])) {
                    unset($existingIssuesCount[$issueType]);

                    continue;
                }

                $existingIssuesCount[$issueType]['o'] = min(
                    $existingIssueType['o'],
                    $newIssues[$file][$issueType]['o']
                );
                $existingIssuesCount[$issueType]['s'] = array_intersect(
                    $existingIssueType['s'],
                    $newIssues[$file][$issueType]['s']
                );
            }
        }

        $groupedIssues = array_filter($existingIssues);

        self::writeToFile(
            $fileProvider,
            $baselineFile,
            $groupedIssues,
            $include_php_versions,
            $baseline_formatter,
        );

        return $groupedIssues;
    }

    /**
     * @param array<string, list<IssueData>> $issues
     * @return psalmFormattedBaseline
     */
    private static function countIssueTypesByFile(array $issues): array
    {
        if ($issues === []) {
            return [];
        }
        $groupedIssues = array_reduce(
            array_merge(...array_values($issues)),
            /**
             * @param psalmFormattedBaseline $carry
             * @return psalmFormattedBaseline
             */
            static function (array $carry, IssueData $issue): array {
                if ($issue->severity !== Config::REPORT_ERROR) {
                    return $carry;
                }

                $fileName = $issue->file_name;
                $fileName = str_replace('\\', '/', $fileName);
                $issueType = $issue->type;

                if (!isset($carry[$fileName])) {
                    $carry[$fileName] = [];
                }

                if (!isset($carry[$fileName][$issueType])) {
                    $carry[$fileName][$issueType] = ['o' => 0, 's' => []];
                }

                ++$carry[$fileName][$issueType]['o'];
                $carry[$fileName][$issueType]['s'][] = $issue->selected_text;

                return $carry;
            },
            []
        );

        // Sort files first
        ksort($groupedIssues);

        foreach ($groupedIssues as &$issues) {
            ksort($issues);
        }
        unset($issues);

        return $groupedIssues;
    }

    /**
     * @param psalmFormattedBaseline $groupedIssues
     */
    private static function writeToFile(
        FileProvider $fileProvider,
        string $baselineFile,
        array $groupedIssues,
        bool $include_php_versions,
        BaselineFormatterInterface $baseline_formatter
    ): void {
        $fileProvider->setContents(
            $baselineFile,
            $baseline_formatter->format($groupedIssues, $include_php_versions),
        );
    }
}
