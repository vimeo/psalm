<?php

declare(strict_types=1);

namespace Psalm\Internal\BaselineFormatter;

use function count;
use function get_loaded_extensions;
use function json_decode;
use function json_encode;
use function phpversion;
use function sort;
use function trim;
use function usort;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const PHP_VERSION;

/**
 * @internal
 */
class JsonBaselineFormatter implements BaselineFormatterInterface
{
    public static function getKey(): string
    {
        return 'json';
    }

    public function format(array $grouped_issues, bool $include_php_versions): string
    {
        $data = ['psalm_version' => PSALM_VERSION];
        if ($include_php_versions) {
            $extensions = [...get_loaded_extensions(), ...get_loaded_extensions(true)];
            usort($extensions, 'strnatcasecmp');
            $php_versions = ['php' => PHP_VERSION];
            foreach ($extensions as $extension) {
                $php_versions[$extension] = phpversion($extension);
            }
            $data['php_versions'] = $php_versions;
        }
        foreach ($grouped_issues as $file => $issue_types) {
            foreach ($issue_types as $issue_type => $existing_issue_type) {
                $data['files'][$file][$issue_type] = [];
                sort($existing_issue_type['s']);
                foreach ($existing_issue_type['s'] as $selection) {
                    $data['files'][$file][$issue_type][] = trim($selection);
                }
            }
        }
        return json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, array<string, array{o:int, s: list<string>}>>
     */
    public function read(string $content): array
    {
        /** @var array{files: array<string, array<string, list<string>>>} $data */
        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        $grouped_issues = [];
        foreach ($data['files'] as $file => $issue_types) {
            foreach ($issue_types as $issue_type => $selections) {
                $grouped_issues[$file][$issue_type]['o'] = count($selections);
                $grouped_issues[$file][$issue_type]['s'] = [];
                foreach ($selections as $selection) {
                    $grouped_issues[$file][$issue_type]['s'][] = $selection;
                }
            }
        }
        return $grouped_issues;
    }
}
