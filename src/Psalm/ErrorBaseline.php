<?php
namespace Psalm;

use Psalm\Provider\FileProvider;

class ErrorBaseline
{
    /**
     * @param FileProvider $fileProvider
     * @param string $baselineFile
     * @param array<array{file_name: string, type: string, severity: string}> $issues
     *
     * @return void
     */
    public static function create(FileProvider $fileProvider, string $baselineFile, array $issues)
    {
        $groupedIssues = self::countIssueTypesByFile($issues);

        self::writeToFile($fileProvider, $baselineFile, $groupedIssues);
    }

    /**
     * @param FileProvider $fileProvider
     * @param string $baselineFile
     * @return array<string,array<string,int>>
     * @throws Exception\ConfigException
     */
    public static function read(FileProvider $fileProvider, string $baselineFile): array
    {
        if (!$fileProvider->fileExists($baselineFile)) {
            throw new Exception\ConfigException("{$baselineFile} does not exist or is not readable\n");
        }

        $xmlSource = $fileProvider->getContents($baselineFile);

        $baselineDoc = new \DOMDocument();
        $baselineDoc->loadXML($xmlSource, LIBXML_NOBLANKS);

        /** @var \DOMNodeList $filesElement */
        $filesElement = $baselineDoc->getElementsByTagName('files');

        if ($filesElement->length === 0) {
            throw new Exception\ConfigException('Baseline file does not contain <files>');
        }

        $files = [];

        /** @var \DOMElement $filesElement */
        $filesElement = $filesElement[0];

        /** @var \DOMElement $file */
        foreach ($filesElement->getElementsByTagName('file') as $file) {
            $fileName = $file->getAttribute('src');

            $files[$fileName] = [];

            /** @var \DOMElement $issue */
            foreach ($file->childNodes as $issue) {
                $issueType = $issue->tagName;

                $files[$fileName][$issueType] = (int)$issue->getAttribute('occurrences');
            }
        }

        return $files;
    }

    /**
     * @param FileProvider $fileProvider
     * @param string $baselineFile
     * @param array<array{file_name: string, type: string, severity: string}> $issues
     * @return array<string,array<string,int>>
     * @throws Exception\ConfigException
     */
    public static function update(FileProvider $fileProvider, string $baselineFile, array $issues)
    {
        $existingIssues = self::read($fileProvider, $baselineFile);
        $newIssues = self::countIssueTypesByFile($issues);

        foreach ($existingIssues as $file => &$existingIssuesCount) {
            if (!isset($newIssues[$file])) {
                unset($existingIssues[$file]);

                continue;
            }

            foreach ($existingIssuesCount as $issueType => $count) {
                if (!isset($newIssues[$file][$issueType])) {
                    unset($existingIssuesCount[$issueType]);

                    continue;
                }

                $existingIssuesCount[$issueType] = min($count, $newIssues[$file][$issueType]);
            }
        }

        $groupedIssues = array_filter($existingIssues);

        self::writeToFile($fileProvider, $baselineFile, $groupedIssues);

        return $groupedIssues;
    }

    /**
     * @param array<array{file_name: string, type: string, severity: string}> $issues
     * @return array<string,array<string,int>>
     */
    private static function countIssueTypesByFile(array $issues): array
    {
        $groupedIssues = array_reduce(
            $issues,
            /**
             * @param array<string,array<string,int>> $carry
             * @param array{type: string, file_name: string, severity: string} $issue
             * @return array<string,array<string,int>>
             */
            function (array $carry, array $issue): array {
                if ($issue['severity'] !== Config::REPORT_ERROR) {
                    return $carry;
                }

                $fileName = $issue['file_name'];
                $issueType = $issue['type'];

                if (!isset($carry[$fileName])) {
                    $carry[$fileName] = [];
                }

                if (!isset($carry[$fileName][$issueType])) {
                    $carry[$fileName][$issueType] = 0;
                }

                $carry[$fileName][$issueType]++;

                return $carry;
            },
            []
        );

        // Sort files first
        ksort($groupedIssues);

        foreach ($groupedIssues as &$issues) {
            ksort($issues);
        }

        return $groupedIssues;
    }

    /**
     * @param FileProvider $fileProvider
     * @param string $baselineFile
     * @param array<string,array<string,int>> $groupedIssues
     * @return void
     */
    private static function writeToFile(
        FileProvider $fileProvider,
        string $baselineFile,
        array $groupedIssues
    ) {
        $baselineDoc = new \DOMDocument('1.0', 'UTF-8');
        $filesNode = $baselineDoc->createElement('files');

        foreach ($groupedIssues as $file => $issueTypes) {
            $fileNode = $baselineDoc->createElement('file');
            $fileNode->setAttribute('src', $file);

            foreach ($issueTypes as $issueType => $count) {
                $issueNode = $baselineDoc->createElement($issueType);
                $issueNode->setAttribute('occurrences', (string)$count);
                $fileNode->appendChild($issueNode);
            }

            $filesNode->appendChild($fileNode);
        }

        $baselineDoc->appendChild($filesNode);
        $baselineDoc->formatOutput = true;

        $fileProvider->setContents($baselineFile, $baselineDoc->saveXML());
    }
}
