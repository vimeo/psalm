<?php

namespace Psalm;

use Composer\Autoload\ClassLoader;
use Phar;
use Psalm\Internal\CliUtils;
use Psalm\Internal\Composer;
use function dirname;
use function strpos;
use function realpath;
use const DIRECTORY_SEPARATOR;
use function file_exists;
use function in_array;
use const PHP_EOL;
use function fwrite;
use const STDERR;
use function implode;
use function define;
use function json_decode;
use function file_get_contents;
use function is_array;
use function is_string;
use function count;
use function strlen;
use function substr;
use function stream_get_meta_data;
use const STDIN;
use function stream_set_blocking;
use function fgets;
use function preg_split;
use function trim;
use function is_dir;
use function preg_replace;
use function substr_replace;
use function file_put_contents;
use function ini_get;
use function preg_match;
use function strtoupper;

require_once __DIR__ . '/Psalm/Internal/CliUtils.php';

/** @deprecated going to be removed in Psalm 5 */
function requireAutoloaders(string $current_dir, bool $has_explicit_root, string $vendor_dir): ?ClassLoader
{
    return CliUtils::requireAutoloaders($current_dir, $has_explicit_root, $vendor_dir);
}

/** @deprecated going to be removed in Psalm 5 */
function getVendorDir(string $current_dir): string
{
    return CliUtils::getVendorDir($current_dir);
}

/**
 * @return list<string>
 * @deprecated going to be removed in Psalm 5
 */
function getArguments() : array
{
    return CliUtils::getArguments();
}

/**
 * @param  string|array|null|false $f_paths
 *
 * @return list<string>|null
 * @deprecated going to be removed in Psalm 5
 */
function getPathsToCheck($f_paths): ?array
{
    return CliUtils::getPathsToCheck($f_paths);
}

/**
 * @psalm-pure
 */
function getPsalmHelpText(): string
{
    return <<<HELP
Usage:
    psalm [options] [file...]

Basic configuration:
    -c, --config=psalm.xml
        Path to a psalm.xml configuration file. Run psalm --init to create one.

    --use-ini-defaults
        Use PHP-provided ini defaults for memory and error display

    --memory-limit=LIMIT
        Use a specific memory limit. Cannot be combined with --use-ini-defaults

    --disable-extension=[extension]
        Used to disable certain extensions while Psalm is running.

    --threads=INT
        If greater than one, Psalm will run analysis on multiple threads, speeding things up.

    --no-diff
        Turns off Psalm’s diff mode, checks all files regardless of whether they’ve changed.

Surfacing issues:
    --show-info[=BOOLEAN]
        Show non-exception parser findings (defaults to false).

    --show-snippet[=true]
        Show code snippets with errors. Options are 'true' or 'false'

    --find-dead-code[=auto]
    --find-unused-code[=auto]
        Look for unused code. Options are 'auto' or 'always'. If no value is specified, default is 'auto'

    --find-unused-psalm-suppress
        Finds all @psalm-suppress annotations that aren’t used

    --find-references-to=[class|method|property]
        Searches the codebase for references to the given fully-qualified class or method,
        where method is in the format class::methodName

    --no-suggestions
        Hide suggestions

    --taint-analysis
        Run Psalm in taint analysis mode – see https://psalm.dev/docs/security_analysis for more info

    --dump-taint-graph=OUTPUT_PATH
        Output the taint graph using the DOT language – requires --taint-analysis

Issue baselines:
    --set-baseline=PATH
        Save all current error level issues to a file, to mark them as info in subsequent runs

        Add --include-php-versions to also include a list of PHP extension versions

    --use-baseline=PATH
        Allows you to use a baseline other than the default baseline provided in your config

    --ignore-baseline
        Ignore the error baseline

    --update-baseline
        Update the baseline by removing fixed issues. This will not add new issues to the baseline

        Add --include-php-versions to also include a list of PHP extension versions

Plugins:
    --plugin=PATH
        Executes a plugin, an alternative to using the Psalm config

Output:
    -m, --monochrome
        Enable monochrome output

    --output-format=console
        Changes the output format.
        Available formats: compact, console, text, emacs, json, pylint, xml, checkstyle, junit, sonarqube, github,
                           phpstorm, codeclimate

    --no-progress
        Disable the progress indicator

    --long-progress
        Use a progress indicator suitable for Continuous Integration logs

    --stats
        Shows a breakdown of Psalm’s ability to infer types in the codebase

Reports:
    --report=PATH
        The path where to output report file. The output format is based on the file extension.
        (Currently supported formats: ".json", ".xml", ".txt", ".emacs", ".pylint", ".console",
        ".sarif", "checkstyle.xml", "sonarqube.json", "codeclimate.json", "summary.json", "junit.xml")

    --report-show-info[=BOOLEAN]
        Whether the report should include non-errors in its output (defaults to true)

Caching:
    --clear-cache
        Clears all cache files that Psalm uses for this specific project

    --clear-global-cache
        Clears all cache files that Psalm uses for all projects

    --no-cache
        Runs Psalm without using cache

    --no-reflection-cache
        Runs Psalm without using cached representations of unchanged classes and files.
        Useful if you want the afterClassLikeVisit plugin hook to run every time you visit a file.

    --no-file-cache
        Runs Psalm without using caching every single file for later diffing.
        This reduces the space Psalm uses on disk and file I/O.

Miscellaneous:
    -h, --help
        Display this help message

    -v, --version
        Display the Psalm version

    -i, --init [source_dir=src] [level=3]
        Create a psalm config file in the current directory that points to [source_dir]
        at the required level, from 1, most strict, to 8, most permissive.

    --debug
        Debug information

    --debug-by-line
        Debug information on a line-by-line level

    --debug-emitted-issues
        Print a php backtrace to stderr when emitting issues.

    -r, --root
        If running Psalm globally you’ll need to specify a project root. Defaults to cwd

    --generate-json-map=PATH
        Generate a map of node references and types in JSON format, saved to the given path.

    --generate-stubs=PATH
        Generate stubs for the project and dump the file in the given path

    --shepherd[=host]
        Send data to Shepherd, Psalm’s GitHub integration tool.

    --alter
        Run Psalter

    --language-server
        Run Psalm Language Server

HELP;
}

function initialiseConfig(
    ?string $path_to_config,
    string $current_dir,
    string $output_format,
    ?ClassLoader $first_autoloader,
    bool $create_if_non_existent = false
): Config {
    try {
        if ($path_to_config) {
            $config = Config::loadFromXMLFile($path_to_config, $current_dir);
        } else {
            try {
                $config = Config::getConfigForPath($current_dir, $current_dir);
            } catch (\Psalm\Exception\ConfigNotFoundException $e) {
                if (!$create_if_non_existent) {
                    if (in_array($output_format, [\Psalm\Report::TYPE_CONSOLE, \Psalm\Report::TYPE_PHP_STORM])) {
                        fwrite(
                            STDERR,
                            'Could not locate a config XML file in path ' . $current_dir
                                . '. Have you run \'psalm --init\' ?' . PHP_EOL
                        );
                        exit(1);
                    }

                    throw $e;
                }

                $config = \Psalm\Config\Creator::createBareConfig(
                    $current_dir,
                    null,
                    \Psalm\getVendorDir($current_dir)
                );
            }
        }
    } catch (\Psalm\Exception\ConfigException $e) {
        fwrite(
            STDERR,
            $e->getMessage() . PHP_EOL
        );
        exit(1);
    }

    $config->setComposerClassLoader($first_autoloader);

    return $config;
}

function update_config_file(Config $config, string $config_file_path, string $baseline_path) : void
{
    if ($config->error_baseline === $baseline_path) {
        return;
    }

    $configFile = $config_file_path;

    if (is_dir($config_file_path)) {
        $configFile = Config::locateConfigFile($config_file_path);
    }

    if (!$configFile) {
        fwrite(STDERR, "Don't forget to set errorBaseline=\"{$baseline_path}\" to your config.");

        return;
    }

    $configFileContents = file_get_contents($configFile);

    if ($config->error_baseline) {
        $amendedConfigFileContents = preg_replace(
            '/errorBaseline=".*?"/',
            "errorBaseline=\"{$baseline_path}\"",
            $configFileContents
        );
    } else {
        $endPsalmOpenTag = strpos($configFileContents, '>', (int)strpos($configFileContents, '<psalm'));

        if (!$endPsalmOpenTag) {
            fwrite(STDERR, " Don't forget to set errorBaseline=\"{$baseline_path}\" in your config.");
            return;
        }

        if ($configFileContents[$endPsalmOpenTag - 1] === "\n") {
            $amendedConfigFileContents = substr_replace(
                $configFileContents,
                "    errorBaseline=\"{$baseline_path}\"\n>",
                $endPsalmOpenTag,
                1
            );
        } else {
            $amendedConfigFileContents = substr_replace(
                $configFileContents,
                " errorBaseline=\"{$baseline_path}\">",
                $endPsalmOpenTag,
                1
            );
        }
    }

    file_put_contents($configFile, $amendedConfigFileContents);
}

function get_path_to_config(array $options): ?string
{
    $path_to_config = isset($options['c']) && is_string($options['c']) ? realpath($options['c']) : null;

    if ($path_to_config === false) {
        fwrite(STDERR, 'Could not resolve path to config ' . (string) ($options['c'] ?? '') . PHP_EOL);
        exit(1);
    }
    return $path_to_config;
}

/**
 * @psalm-pure
 */
function getMemoryLimitInBytes(): int
{
    $limit = ini_get('memory_limit');
    // for unlimited = -1
    if ($limit < 0) {
        return -1;
    }

    if (preg_match('/^(\d+)(\D?)$/', $limit, $matches)) {
        $limit = (int)$matches[1];
        switch (strtoupper($matches[2] ?? '')) {
            case 'G':
                $limit *= 1024 * 1024 * 1024;
                break;
            case 'M':
                $limit *= 1024 * 1024;
                break;
            case 'K':
                $limit *= 1024;
                break;
        }
    }

    return (int)$limit;
}
