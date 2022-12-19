<?php

declare(strict_types=1);

namespace Psalm\Internal\Cli;

use Exception;

use function array_key_exists;
use function filter_var;
use function getopt;
use function implode;
use function is_string;
use function sprintf;

use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_BOOL;
use const FILTER_VALIDATE_INT;

/**
 * @internal
 * @psalm-immutable
 */
final class Options
{
    private const SHORT_OPTIONS = [
        'f:',
        'm',
        'h',
        'v',
        'c:',
        'i',
        'r:',
    ];
    private const LONG_OPTIONS = [
        'clear-cache',
        'clear-global-cache',
        'config:',
        'debug',
        'debug-by-line',
        'debug-performance',
        'debug-emitted-issues',
        'diff',
        'disable-extension:',
        'find-dead-code::',
        'find-unused-code::',
        'find-unused-variables',
        'find-references-to:',
        'help',
        'ignore-baseline',
        'init',
        'memory-limit:',
        'monochrome',
        'no-diff',
        'no-cache',
        'no-reflection-cache',
        'no-file-cache',
        'output-format:',
        'plugin:',
        'report:',
        'report-show-info:',
        'root:',
        'set-baseline:',
        'show-info:',
        'show-snippet:',
        'stats',
        'threads:',
        'update-baseline',
        'use-baseline:',
        'use-ini-defaults',
        'version',
        'php-version:',
        'generate-json-map:',
        'generate-stubs:',
        'alter',
        'language-server',
        'refactor',
        'shepherd::',
        'no-progress',
        'long-progress',
        'no-suggestions',
        'include-php-versions', // used for baseline
        'pretty-print', // used for JSON reports
        'track-tainted-input',
        'taint-analysis',
        'security-analysis',
        'dump-taint-graph:',
        'find-unused-psalm-suppress',
        'error-level:',
    ];

    public bool $alter;
    public bool $language_server;
    public bool $refactor;
    public bool $use_ini_defaults;
    public ?string $memory_limit;
    public ?string $config;
    public bool $help;
    public ?string $root;
    public bool $taint_analysis;
    public bool $version;
    public ?string $output_format;
    public bool $init;
    public ?int $error_level;
    public bool $no_cache;
    public bool $long_progress;
    public ?int $threads;
    public bool $debug;
    /** @var list<string> */
    public array $disable_extension;
    public bool $debug_emitted_issues;
    /** @var list<string> */
    public array $files;
    /** @var list<string> */
    public array $plugin;
    public bool $show_info;
    public bool $no_diff;
    public bool $update_baseline;
    public ?string $set_baseline;
    public ?string $find_unused_code;
    public bool $find_unused_variables;
    public ?string $find_references_to;
    public ?string $shepherd;
    public bool $clear_cache;
    public bool $clear_global_cache;
    public bool $debug_by_line;
    public bool $no_reflection_cache;
    public bool $no_file_cache;
    public bool $monochrome;
    public bool $no_suggestions;
    public bool $show_snippet;
    public bool $pretty_print;
    /** @var list<string> */
    public array $report;
    public bool $report_show_info;
    public ?string $php_version;
    public ?string $dump_taint_graph;
    public ?string $generate_json_map;
    public bool $debug_performance;
    public bool $find_unused_psalm_suppress;
    public ?string $generate_stubs;
    public bool $stats;
    public ?string $use_baseline;
    public bool $include_php_versions;
    public bool $ignore_baseline;
    public bool $no_progress;

    /**
     * @param list<string> $disable_extension
     * @param list<string> $files
     * @param list<string> $plugin
     * @param list<string> $report
     */
    public function __construct(
        bool $alter,
        bool $language_server,
        bool $refactor,
        bool $use_ini_defaults,
        ?string $memory_limit,
        ?string $config,
        bool $help,
        ?string $root,
        bool $taint_analysis,
        bool $version,
        ?string $output_format,
        bool $init,
        ?int $error_level,
        bool $no_cache,
        bool $long_progress,
        ?int $threads,
        bool $debug,
        array $disable_extension,
        bool $debug_emitted_issues,
        array $files,
        array $plugin,
        bool $show_info,
        bool $no_diff,
        bool $update_baseline,
        ?string $set_baseline,
        ?string $find_unused_code,
        bool $find_unused_variables,
        ?string $find_references_to,
        ?string $shepherd,
        bool $clear_cache,
        bool $clear_global_cache,
        bool $debug_by_line,
        bool $no_reflection_cache,
        bool $no_file_cache,
        bool $monochrome,
        bool $no_suggestions,
        bool $show_snippet,
        bool $pretty_print,
        array $report,
        bool $report_show_info,
        ?string $php_version,
        ?string $dump_taint_graph,
        ?string $generate_json_map,
        bool $debug_performance,
        bool $find_unused_psalm_suppress,
        ?string $generate_stubs,
        bool $stats,
        ?string $use_baseline,
        bool $include_php_versions,
        bool $ignore_baseline,
        bool $no_progress
    ) {
        $this->alter = $alter;
        $this->language_server = $language_server;
        $this->refactor = $refactor;
        $this->use_ini_defaults = $use_ini_defaults;
        $this->memory_limit = $memory_limit;
        $this->config = $config;
        $this->help = $help;
        $this->root = $root;
        $this->taint_analysis = $taint_analysis;
        $this->version = $version;
        $this->output_format = $output_format;
        $this->init = $init;
        $this->error_level = $error_level;
        $this->no_cache = $no_cache;
        $this->long_progress = $long_progress;
        $this->threads = $threads;
        $this->debug = $debug;
        $this->disable_extension = $disable_extension;
        $this->debug_emitted_issues = $debug_emitted_issues;
        $this->files = $files;
        $this->plugin = $plugin;
        $this->show_info = $show_info;
        $this->no_diff = $no_diff;
        $this->update_baseline = $update_baseline;
        $this->set_baseline = $set_baseline;
        $this->find_unused_code = $find_unused_code;
        $this->find_unused_variables = $find_unused_variables;
        $this->find_references_to = $find_references_to;
        $this->shepherd = $shepherd;
        $this->clear_cache = $clear_cache;
        $this->clear_global_cache = $clear_global_cache;
        $this->debug_by_line = $debug_by_line;
        $this->no_reflection_cache = $no_reflection_cache;
        $this->no_file_cache = $no_file_cache;
        $this->monochrome = $monochrome;
        $this->no_suggestions = $no_suggestions;
        $this->show_snippet = $show_snippet;
        $this->pretty_print = $pretty_print;
        $this->report = $report;
        $this->report_show_info = $report_show_info;
        $this->php_version = $php_version;
        $this->dump_taint_graph = $dump_taint_graph;
        $this->generate_json_map = $generate_json_map;
        $this->debug_performance = $debug_performance;
        $this->find_unused_psalm_suppress = $find_unused_psalm_suppress;
        $this->generate_stubs = $generate_stubs;
        $this->stats = $stats;
        $this->use_baseline = $use_baseline;
        $this->include_php_versions = $include_php_versions;
        $this->ignore_baseline = $ignore_baseline;
        $this->no_progress = $no_progress;
    }

    public static function fromGetopt(): Options
    {
        $optionsArray = getopt(implode('', self::SHORT_OPTIONS), self::LONG_OPTIONS);
        if ($optionsArray === false) {
            throw new Exception('Failed to get CLI options.');
        }
        return new Options(
            self::noValue($optionsArray, 'alter'),
            self::noValue($optionsArray, 'language-server'),
            // TODO: Missing documentation
            self::noValue($optionsArray, 'refactor'),
            self::noValue($optionsArray, 'use-ini-defaults'),
            self::stringValue($optionsArray, 'memory-limit'),
            self::stringValue($optionsArray, 'config', 'c'),
            self::noValue($optionsArray, 'help', 'h'),
            self::stringValue($optionsArray, 'root', 'r'),
            self::noValue($optionsArray, 'taint-analysis', 'security-analysis', 'track-tainted-input'),
            self::noValue($optionsArray, 'version', 'v'),
            self::stringValue($optionsArray, 'output-format'),
            self::noValue($optionsArray, 'init', 'i'),
            // TODO: Missing documentation
            self::intValue($optionsArray, 'error-level'),
            self::noValue($optionsArray, 'no-cache'),
            self::noValue($optionsArray, 'long-progress'),
            self::intValue($optionsArray, 'threads'),
            self::noValue($optionsArray, 'debug'),
            self::stringListOption($optionsArray, 'disable-extension'),
            self::noValue($optionsArray, 'debug-emitted-issues'),
            // TODO: Missing documentation
            self::stringListOption($optionsArray, 'f'),
            self::stringListOption($optionsArray, 'plugin'),
            self::boolStringOption($optionsArray, ['show-info']),
            self::noValue($optionsArray, 'no-diff'),
            self::noValue($optionsArray, 'update-baseline'),
            self::stringValue($optionsArray, 'set-baseline'),
            self::stringValue($optionsArray, 'find-unused-code', 'find-dead-code'),
            self::noValue($optionsArray, 'find-unused-variables'),
            self::stringValue($optionsArray, 'find-references-to'),
            self::noOrStringValue($optionsArray, 'shepherd'),
            self::noValue($optionsArray, 'clear-cache'),
            self::noValue($optionsArray, 'clear-global-cache'),
            self::noValue($optionsArray, 'debug-by-line'),
            self::noValue($optionsArray, 'no-reflection-cache'),
            self::noValue($optionsArray, 'no-file-cache'),
            self::noValue($optionsArray, 'monochrome', 'm'),
            self::noValue($optionsArray, 'no-suggestions'),
            self::boolStringOption($optionsArray, ['show-snippet']),
            self::boolStringOption($optionsArray, ['pretty-print']),
            self::stringListOption($optionsArray, 'report'),
            self::boolStringOption($optionsArray, ['report-show-info'], true),
            self::stringValue($optionsArray, 'php-version'),
            self::stringValue($optionsArray, 'dump-taint-graph'),
            self::stringValue($optionsArray, 'generate-json-map'),
            self::noValue($optionsArray, 'debug-performance'),
            self::noValue($optionsArray, 'find-unused_psalm-suppress'),
            self::stringValue($optionsArray, 'generate-stubs'),
            self::noValue($optionsArray, 'stats'),
            self::stringValue($optionsArray, 'use-baseline'),
            self::noValue($optionsArray, 'include-php-versions'),
            self::noValue($optionsArray, 'ignore-baseline'),
            self::noValue($optionsArray, 'no-progress'),
        );
    }

    /**
     * TODO: Ensure multiple keys are not used.
     *
     * @param array<string, string|false|list<string|false>> $optionsArray
     */
    private static function noValue(array $optionsArray, string ...$keys): bool
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $optionsArray)) {
                if ($optionsArray[$key] === false) {
                    return true;
                }
                throw new Exception(sprintf('CLI option "%s" should not have a value.', $key));
            }
        }
        return false;
    }

    /**
     * TODO: Ensure multiple keys are not used.
     *
     * @param array<string, string|false|list<string|false>> $optionsArray
     */
    private static function boolStringOption(array $optionsArray, array $keys, bool $default = false): bool
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $optionsArray)) {
                if (is_string($optionsArray[$key])) {
                    $value = filter_var($optionsArray[$key], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
                    if ($value === null) {
                        throw new Exception(sprintf('CLI option "%s" should have a boolean value.', $key));
                    }
                    return $value;
                }
                throw new Exception(sprintf('CLI option "%s" should have a single value.', $key));
            }
        }
        return $default;
    }

    /**
     * TODO: Ensure multiple keys are not used.
     *
     * @param array<string, string|false|list<string|false>> $optionsArray
     */
    private static function stringValue(array $optionsArray, string ...$keys): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $optionsArray)) {
                if (is_string($optionsArray[$key])) {
                    return $optionsArray[$key];
                }
                throw new Exception(sprintf('CLI option "%s" should have a single value.', $key));
            }
        }
        return null;
    }

    /**
     * TODO: Ensure multiple keys are not used.
     *
     * @param array<string, string|false|list<string|false>> $optionsArray
     */
    private static function noOrStringValue(array $optionsArray, string ...$keys): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $optionsArray)) {
                if (is_string($optionsArray[$key])) {
                    return $optionsArray[$key];
                } elseif ($optionsArray[$key] === false) {
                    return '';
                }
                throw new Exception(sprintf('CLI option "%s" should have a single value.', $key));
            }
        }
        return null;
    }

    /**
     * TODO: Ensure multiple keys are not used.
     *
     * @param array<string, string|false|list<string|false>> $optionsArray
     */
    private static function intValue(array $optionsArray, string ...$keys): ?int
    {
        $value = self::stringValue($optionsArray, ...$keys);
        if ($value === null) {
            return null;
        }
        $value = filter_var($value, FILTER_VALIDATE_INT);
        if ($value === false) {
            throw new Exception(sprintf('CLI option(s) "%s" should have an integer value.', implode('", "', $keys)));
        }
        return $value;
    }

    /**
     * @param array<string, string|false|list<string|false>> $optionsArray
     * @return list<string>
     */
    private static function stringListOption(array $optionsArray, string ...$keys): array
    {
        $values = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $optionsArray)) {
                if (is_string($optionsArray[$key])) {
                    $values[] = $optionsArray[$key];
                } else {
                    throw new Exception(sprintf('CLI option "%s" should have a single value.', $key));
                }
            }
        }
        return $values;
    }
}
