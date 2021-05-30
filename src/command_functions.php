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
 * @deprecated going to be removed in Psalm 5
 */
function getPsalmHelpText(): string
{
    return CliUtils::getPsalmHelpText();
}

/** @deprecated going to be removed in Psalm 5 */
function initialiseConfig(
    ?string $path_to_config,
    string $current_dir,
    string $output_format,
    ?ClassLoader $first_autoloader,
    bool $create_if_non_existent = false
): Config {
    return CliUtils::initializeConfig(
        $path_to_config,
        $current_dir,
        $output_format,
        $first_autoloader,
        $create_if_non_existent
    );
}

/** @deprecated going to be removed in Psalm 5 */
function update_config_file(Config $config, string $config_file_path, string $baseline_path) : void
{
    CliUtils::updateConfigFile($config, $config_file_path, $baseline_path);
}

/** @deprecated going to be removed in Psalm 5 */
function get_path_to_config(array $options): ?string
{
    return CliUtils::getPathToConfig($options);
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
