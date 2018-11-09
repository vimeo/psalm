<?php
namespace Psalm;

use Psalm\Provider\FileProvider;

class ErrorBaseline
{
    /**
     * @param FileProvider $fileProvider
     * @param string $baselineFile
     * @param array<array{file_name: string, type: string, severity: string, selected_text: string}> $issues
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
     * @return array<string,array<string,array{o:int, s:array<int, string>}>>
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

                $files[$fileName][$issueType] = [
                    'o' => (int)$issue->getAttribute('occurrences'),
                    's' => [],
                ];
                $codeSamples = $issue->getElementsByTagName('code');

                /** @var \DOMElement $codeSample */
                foreach ($codeSamples as $codeSample) {
                    $files[$fileName][$issueType]['s'][] = (string) $codeSample->textContent;
                }
            }
        }

        return $files;
    }

    /**
     * @param FileProvider $fileProvider
     * @param string $baselineFile
     * @param array<array{file_name: string, type: string, severity: string, selected_text: string}> $issues
     * @return array<string,array<string,array{o:int, s:array<int, string>}>>
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

            foreach ($existingIssuesCount as $issueType => ['o' => $occurrences, 's' => $selections]) {
                if (!isset($newIssues[$file][$issueType])) {
                    unset($existingIssuesCount[$issueType]);

                    continue;
                }

                $existingIssuesCount[$issueType]['o'] = min($occurrences, $newIssues[$file][$issueType]['o']);
                $existingIssuesCount[$issueType]['s'] = array_intersect(
                    $selections,
                    $newIssues[$file][$issueType]['s']
                );
            }
        }

        $groupedIssues = array_filter($existingIssues);

        self::writeToFile($fileProvider, $baselineFile, $groupedIssues);

        return $groupedIssues;
    }

    /**
     * @param array<array{file_name: string, type: string, severity: string, selected_text: string}> $issues
     * @return array<string,array<string,array{o:int, s:array<int, string>}>>
     */
    private static function countIssueTypesByFile(array $issues): array
    {
        $groupedIssues = array_reduce(
            $issues,
            /**
             * @param array<string,array<string,array{o:int, s:array<int, string>}>> $carry
             * @param array{type: string, file_name: string, severity: string, selected_text: string} $issue
             * @return array<string,array<string,array{o:int, s:array<int, string>}>>
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
                    $carry[$fileName][$issueType] = ['o' => 0, 's' => []];
                }

                $carry[$fileName][$issueType]['o']++;

                if (!strpos($issue['selected_text'], "\n")) {
                    $carry[$fileName][$issueType]['s'][] = $issue['selected_text'];
                }

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
     * @param array<string,array<string,array{o:int, s:array<int, string>}>> $groupedIssues
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

            foreach ($issueTypes as $issueType => ['o' => $occurrences, 's' => $selections]) {
                $issueNode = $baselineDoc->createElement($issueType);
                $issueNode->setAttribute('occurrences', (string)$occurrences);
                foreach ($selections as $selection) {
                    $codeNode = $baselineDoc->createElement('code');
                    $codeNode->textContent = $selection;
                    $issueNode->appendChild($codeNode);
                }
                $fileNode->appendChild($issueNode);
            }

            $filesNode->appendChild($fileNode);
        }

        $baselineDoc->appendChild($filesNode);
        $baselineDoc->formatOutput = true;

        $fileProvider->setContents($baselineFile, $baselineDoc->saveXML());
    }
}
