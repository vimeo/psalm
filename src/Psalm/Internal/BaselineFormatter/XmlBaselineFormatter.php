<?php

declare(strict_types=1);

namespace Psalm\Internal\BaselineFormatter;

use DOMDocument;
use DOMElement;
use Psalm\Exception\ConfigException;
use RuntimeException;

use function array_map;
use function get_loaded_extensions;
use function implode;
use function phpversion;
use function preg_replace_callback;
use function sort;
use function sprintf;
use function str_replace;
use function trim;
use function usort;

use const LIBXML_NOBLANKS;
use const PHP_VERSION;

/**
 * @internal
 */
final class XmlBaselineFormatter implements BaselineFormatterInterface
{
    public static function getKey(): string
    {
        return 'xml';
    }

    public function format(array $grouped_issues, bool $include_php_versions): string
    {
        $baselineDoc = new DOMDocument('1.0', 'UTF-8');
        $filesNode = $baselineDoc->createElement('files');
        $filesNode->setAttribute('psalm-version', PSALM_VERSION);

        if ($include_php_versions) {
            $extensions = [...get_loaded_extensions(), ...get_loaded_extensions(true)];

            usort($extensions, 'strnatcasecmp');

            $filesNode->setAttribute('php-version', implode(';' . "\n\t", [...[
                ('php:' . PHP_VERSION),
            ], ...array_map(
                static fn(string $extension): string => $extension . ':' . phpversion($extension),
                $extensions
            )]));
        }

        foreach ($grouped_issues as $file => $issueTypes) {
            $fileNode = $baselineDoc->createElement('file');

            $fileNode->setAttribute('src', $file);

            foreach ($issueTypes as $issueType => $existingIssueType) {
                $issueNode = $baselineDoc->createElement($issueType);

                $issueNode->setAttribute('occurrences', (string)$existingIssueType['o']);

                sort($existingIssueType['s']);

                foreach ($existingIssueType['s'] as $selection) {
                    $codeNode = $baselineDoc->createElement('code');
                    $codeNode->textContent = trim($selection);
                    $issueNode->appendChild($codeNode);
                }
                $fileNode->appendChild($issueNode);
            }

            $filesNode->appendChild($fileNode);
        }

        $baselineDoc->appendChild($filesNode);
        $baselineDoc->formatOutput = true;

        $xml = preg_replace_callback(
            '/<files (psalm-version="[^"]+") php-version="(.+)"(\/?>)\n/',
            /**
             * @param string[] $matches
             */
            static fn(array $matches): string => sprintf(
                "<files\n  %s\n  php-version=\"\n    %s\n  \"\n%s\n",
                $matches[1],
                str_replace('&#10;&#9;', "\n    ", $matches[2]),
                $matches[3]
            ),
            $baselineDoc->saveXML()
        );

        if ($xml === null) {
            throw new RuntimeException('Failed to reformat opening attributes!');
        }

        return $xml;
    }

    public function read(string $content): array
    {
        if ($content === '') {
            throw new ConfigException('Baseline file is empty.');
        }

        $baselineDoc = new DOMDocument();
        $baselineDoc->loadXML($content, LIBXML_NOBLANKS);

        $filesElement = $baselineDoc->getElementsByTagName('files');

        if ($filesElement->length === 0) {
            throw new ConfigException('Baseline file does not contain <files>.');
        }

        $files = [];

        /** @var DOMElement $filesElement */
        $filesElement = $filesElement[0];

        foreach ($filesElement->getElementsByTagName('file') as $file) {
            $fileName = $file->getAttribute('src');

            $fileName = str_replace('\\', '/', $fileName);

            $files[$fileName] = [];

            foreach ($file->childNodes as $issue) {
                if (!$issue instanceof DOMElement) {
                    continue;
                }

                $issueType = $issue->tagName;

                $files[$fileName][$issueType] = [
                    'o' => (int)$issue->getAttribute('occurrences'),
                    's' => [],
                ];
                $codeSamples = $issue->getElementsByTagName('code');

                foreach ($codeSamples as $codeSample) {
                    $files[$fileName][$issueType]['s'][] = trim($codeSample->textContent);
                }
            }
        }

        return $files;
    }
}
