<?php

namespace Psalm\Internal;

use Composer\Autoload\ClassLoader;
use Phar;
use Psalm\Internal\Composer;

use function count;
use function define;
use function dirname;
use function fgets;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function fwrite;
use function implode;
use function in_array;
use function ini_get;
use function is_array;
use function is_dir;
use function is_string;
use function json_decode;
use function preg_match;
use function preg_replace;
use function preg_split;
use function realpath;
use function stream_get_meta_data;
use function stream_set_blocking;
use function strlen;
use function strpos;
use function strtoupper;
use function substr;
use function substr_replace;
use function trim;

use const DIRECTORY_SEPARATOR;
use const PHP_EOL;
use const STDERR;
use const STDIN;

final class CliUtils
{
    public static function requireAutoloaders(
        string $current_dir,
        bool $has_explicit_root,
        string $vendor_dir
    ): ?ClassLoader {
        $autoload_roots = [$current_dir];

        $psalm_dir = dirname(__DIR__, 3);

        /** @psalm-suppress UndefinedConstant */
        $in_phar = Phar::running() || strpos(__NAMESPACE__, 'HumbugBox');

        if ($in_phar) {
            require_once __DIR__ . '/../../../vendor/autoload.php';

            // hack required for JsonMapper
            require_once __DIR__ . '/../../../vendor/netresearch/jsonmapper/src/JsonMapper.php';
            require_once __DIR__ . '/../../../vendor/netresearch/jsonmapper/src/JsonMapper/Exception.php';
        }

        if (realpath($psalm_dir) !== realpath($current_dir) && !$in_phar) {
            $autoload_roots[] = $psalm_dir;
        }

        $autoload_files = [];

        foreach ($autoload_roots as $autoload_root) {
            $has_autoloader = false;

            $nested_autoload_file = dirname($autoload_root, 2). DIRECTORY_SEPARATOR . 'autoload.php';

            // note: don't realpath $nested_autoload_file, or phar version will fail
            if (file_exists($nested_autoload_file)) {
                if (!in_array($nested_autoload_file, $autoload_files, false)) {
                    $autoload_files[] = $nested_autoload_file;
                }
                $has_autoloader = true;
            }

            $vendor_autoload_file =
                $autoload_root . DIRECTORY_SEPARATOR . $vendor_dir . DIRECTORY_SEPARATOR . 'autoload.php';

            // note: don't realpath $vendor_autoload_file, or phar version will fail
            if (file_exists($vendor_autoload_file)) {
                if (!in_array($vendor_autoload_file, $autoload_files, false)) {
                    $autoload_files[] = $vendor_autoload_file;
                }
                $has_autoloader = true;
            }

            $composer_json_file = Composer::getJsonFilePath($autoload_root);
            if (!$has_autoloader && file_exists($composer_json_file)) {
                $error_message = 'Could not find any composer autoloaders in ' . $autoload_root;

                if (!$has_explicit_root) {
                    $error_message .= PHP_EOL . 'Add a --root=[your/project/directory] flag '
                        . 'to specify a particular project to run Psalm on.';
                }

                fwrite(STDERR, $error_message . PHP_EOL);
                exit(1);
            }
        }

        $first_autoloader = null;

        foreach ($autoload_files as $file) {
            /**
             * @psalm-suppress UnresolvableInclude
             *
             * @var mixed
             */
            $autoloader = require_once $file;

            if (!$first_autoloader
                && $autoloader instanceof ClassLoader
            ) {
                $first_autoloader = $autoloader;
            }
        }

        if ($first_autoloader === null && !$in_phar) {
            if (!$autoload_files) {
                fwrite(STDERR, 'Failed to find a valid Composer autoloader' . "\n");
            } else {
                fwrite(
                    STDERR,
                    'Failed to find a valid Composer autoloader in ' . implode(', ', $autoload_files) . "\n"
                );
            }

            fwrite(
                STDERR,
                'Please make sure you’ve run `composer install` in the current directory before using Psalm.' . "\n"
            );
            exit(1);
        }

        define('PSALM_VERSION', \PackageVersions\Versions::getVersion('vimeo/psalm'));
        define('PHP_PARSER_VERSION', \PackageVersions\Versions::getVersion('nikic/php-parser'));

        return $first_autoloader;
    }
}
