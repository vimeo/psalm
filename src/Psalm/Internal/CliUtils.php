<?php

namespace Psalm\Internal;

use Composer\Autoload\ClassLoader;
use JsonException;
use Phar;
use Psalm\Config;
use Psalm\Config\Creator;
use Psalm\Exception\ConfigException;
use Psalm\Exception\ConfigNotFoundException;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Report;
use RuntimeException;

use function array_filter;
use function array_slice;
use function assert;
use function count;
use function define;
use function dirname;
use function extension_loaded;
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
use function preg_last_error_msg;
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
use const JSON_THROW_ON_ERROR;
use const PHP_EOL;
use const PHP_VERSION;
use const PHP_VERSION_ID;
use const STDERR;
use const STDIN;

/**
 * @internal
 */
final class CliUtils
{
    public static function requireAutoloaders(
        string $current_dir,
        bool $has_explicit_root,
        string $vendor_dir
    ): ?ClassLoader {
        $autoload_roots = [$current_dir];

        $psalm_dir = dirname(__DIR__, 3);

        $in_phar = Phar::running() || strpos(__NAMESPACE__, 'HumbugBox');

        if ($in_phar) {
            // require this before anything else
            $stringable_path = __DIR__ . '/../../../vendor/symfony/polyfill-php80/Resources/stubs/Stringable.php';
            if (file_exists($stringable_path)) {
                require_once $stringable_path;
            }
            require_once __DIR__ . '/../../../vendor/autoload.php';

            // hack required for JsonMapper
            require_once __DIR__ . '/../../../vendor/netresearch/jsonmapper/src/JsonMapper.php';
            require_once __DIR__ . '/../../../vendor/netresearch/jsonmapper/src/JsonMapper/Exception.php';
        }

        if (!$in_phar && realpath($psalm_dir) !== realpath($current_dir)) {
            $autoload_roots[] = $psalm_dir;
        }

        $autoload_files = [];

        foreach ($autoload_roots as $autoload_root) {
            $has_autoloader = false;

            $nested_autoload_file = dirname($autoload_root, 2) . DIRECTORY_SEPARATOR . 'autoload.php';

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
                    'Failed to find a valid Composer autoloader in ' . implode(', ', $autoload_files) . "\n",
                );
            }

            fwrite(
                STDERR,
                'Please make sure you’ve run `composer install` in the current directory before using Psalm.' . "\n",
            );
            exit(1);
        }

        define('PSALM_VERSION', VersionUtils::getPsalmVersion());
        define('PHP_PARSER_VERSION', VersionUtils::getPhpParserVersion());

        return $first_autoloader;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public static function getVendorDir(string $current_dir): string
    {
        $composer_json_path = Composer::getJsonFilePath($current_dir);

        if (!file_exists($composer_json_path)) {
            return 'vendor';
        }
        try {
            $composer_json = json_decode(file_get_contents($composer_json_path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            fwrite(
                STDERR,
                'Invalid composer.json at ' . $composer_json_path . "\n" . $e->getMessage() . "\n",
            );
            exit(1);
        }

        if (!$composer_json) {
            fwrite(
                STDERR,
                'Invalid composer.json at ' . $composer_json_path . "\n",
            );
            exit(1);
        }

        if (is_array($composer_json)
            && isset($composer_json['config'])
            && is_array($composer_json['config'])
            && isset($composer_json['config']['vendor-dir'])
            && is_string($composer_json['config']['vendor-dir'])
        ) {
            return $composer_json['config']['vendor-dir'];
        }

        return 'vendor';
    }

    /**
     * @return list<string>
     */
    public static function getRawCliArguments(): array
    {
        global $argv;

        if (!$argv) {
            return [];
        }

        return array_slice($argv, 1);
    }

    /**
     * @return list<string>
     */
    public static function getArguments(): array
    {
        $argv = self::getRawCliArguments();
        $filtered_input_paths = [];

        for ($i = 0, $iMax = count($argv); $i < $iMax; ++$i) {
            $input_path = $argv[$i];

            if (realpath($input_path) !== false) {
                continue;
            }

            if ($input_path[0] === '-' && strlen($input_path) === 2) {
                if ($input_path[1] === 'c' || $input_path[1] === 'f') {
                    ++$i;
                }
                continue;
            }

            if ($input_path[0] === '-' && $input_path[2] === '=') {
                continue;
            }

            $filtered_input_paths[] = $input_path;
        }

        return $filtered_input_paths;
    }

    /**
     * @param  string|array|null|false $f_paths
     * @return list<string>|null
     */
    public static function getPathsToCheck($f_paths): ?array
    {
        $paths_to_check = [];

        if ($f_paths) {
            $input_paths = is_array($f_paths) ? $f_paths : [$f_paths];
        } else {
            $input_paths = self::getRawCliArguments();
            if (!$input_paths) {
                return null;
            }
        }

        $filtered_input_paths = [];

        for ($i = 0, $iMax = count($input_paths); $i < $iMax; ++$i) {
            /** @var string */
            $input_path = $input_paths[$i];

            if ($input_path[0] === '-' && strlen($input_path) === 2) {
                if ($input_path[1] === 'c' || $input_path[1] === 'f') {
                    ++$i;
                }
                continue;
            }

            if ($input_path[0] === '-' && $input_path[2] === '=') {
                continue;
            }

            if (strpos($input_path, '--') === 0 && strlen($input_path) > 2) {
                if (substr($input_path, 2) === 'config') {
                    ++$i;
                }
                continue;
            }

            $filtered_input_paths[] = $input_path;
        }

        if ($filtered_input_paths === ['-']) {
            $meta = stream_get_meta_data(STDIN);
            stream_set_blocking(STDIN, false);
            if ($stdin = fgets(STDIN)) {
                $filtered_input_paths = preg_split('/\s+/', trim($stdin));
                if ($filtered_input_paths === false) {
                    throw new RuntimeException('Invalid paths: ' . preg_last_error_msg());
                }
            }
            $blocked = $meta['blocked'];
            stream_set_blocking(STDIN, $blocked);
        }

        foreach ($filtered_input_paths as $path_to_check) {
            if ($path_to_check[0] === '-') {
                fwrite(STDERR, 'Invalid usage, expecting psalm [options] [file...]' . PHP_EOL);
                exit(1);
            }

            if (!file_exists($path_to_check)) {
                fwrite(STDERR, 'Cannot locate ' . $path_to_check . PHP_EOL);
                exit(1);
            }

            $path_to_check = realpath($path_to_check);

            if (!$path_to_check) {
                fwrite(STDERR, 'Error getting realpath for file' . PHP_EOL);
                exit(1);
            }

            $paths_to_check[] = $path_to_check;
        }

        if (!$paths_to_check) {
            $paths_to_check = null;
        }

        return $paths_to_check;
    }


    public static function initializeConfig(
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
                } catch (ConfigNotFoundException $e) {
                    if (!$create_if_non_existent) {
                        if (in_array($output_format, [Report::TYPE_CONSOLE, Report::TYPE_PHP_STORM])) {
                            fwrite(
                                STDERR,
                                'Could not locate a config XML file in path ' . $current_dir
                                    . '. Have you run \'psalm --init\' ?' . PHP_EOL,
                            );
                            exit(1);
                        }

                        throw $e;
                    }

                    $config = Creator::createBareConfig(
                        $current_dir,
                        null,
                        self::getVendorDir($current_dir),
                    );
                }
            }
        } catch (ConfigException $e) {
            fwrite(
                STDERR,
                $e->getMessage() . PHP_EOL,
            );
            exit(1);
        }

        $config->setComposerClassLoader($first_autoloader);

        return $config;
    }

    public static function updateConfigFile(Config $config, string $config_file_path, string $baseline_path): void
    {
        if ($config->error_baseline === $baseline_path) {
            return;
        }

        $config_file = $config_file_path;

        if (is_dir($config_file_path)) {
            $config_file = Config::locateConfigFile($config_file_path);
        }

        if (!$config_file) {
            fwrite(STDERR, "Don't forget to set errorBaseline=\"{$baseline_path}\" to your config.");

            return;
        }

        $config_file_contents = file_get_contents($config_file);

        if ($config->error_baseline) {
            $amended_config_file_contents = preg_replace(
                '/errorBaseline=".*?"/',
                "errorBaseline=\"{$baseline_path}\"",
                $config_file_contents,
            );
        } else {
            $end_psalm_open_tag = strpos($config_file_contents, '>', (int)strpos($config_file_contents, '<psalm'));

            if (!$end_psalm_open_tag) {
                fwrite(STDERR, " Don't forget to set errorBaseline=\"{$baseline_path}\" in your config.");
                return;
            }

            if ($config_file_contents[$end_psalm_open_tag - 1] === "\n") {
                $amended_config_file_contents = substr_replace(
                    $config_file_contents,
                    "    errorBaseline=\"{$baseline_path}\"\n>",
                    $end_psalm_open_tag,
                    1,
                );
            } else {
                $amended_config_file_contents = substr_replace(
                    $config_file_contents,
                    " errorBaseline=\"{$baseline_path}\">",
                    $end_psalm_open_tag,
                    1,
                );
            }
        }

        file_put_contents($config_file, $amended_config_file_contents);
    }

    public static function getPathToConfig(array $options): ?string
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
    public static function getMemoryLimitInBytes(): int
    {
        return self::convertMemoryLimitToBytes(ini_get('memory_limit'));
    }

    /** @psalm-pure */
    public static function convertMemoryLimitToBytes(string $limit): int
    {
        // for unlimited = -1
        if ($limit < 0) {
            return -1;
        }

        if (preg_match('/^(\d+)(\D?)$/', $limit, $matches)) {
            assert(isset($matches[1]));
            $limit = (int)$matches[1];
            switch (strtoupper($matches[2] ?? '')) {
                case 'G':
                    $limit *= 1_024 * 1_024 * 1_024;
                    break;
                case 'M':
                    $limit *= 1_024 * 1_024;
                    break;
                case 'K':
                    $limit *= 1_024;
                    break;
            }
        }

        return (int)$limit;
    }

    public static function initPhpVersion(array $options, Config $config, ProjectAnalyzer $project_analyzer): void
    {
        $source = null;

        if (isset($options['php-version'])) {
            if (!is_string($options['php-version'])) {
                die('Expecting a version number in the format x.y' . PHP_EOL);
            }
            $version = $options['php-version'];
            $source = 'cli';
        } elseif ($version = $config->getPhpVersionFromConfig()) {
            $source = 'config';
        } elseif ($version = $config->getPHPVersionFromComposerJson()) {
            $source = 'composer';
        }

        if ($version !== null && $source !== null) {
            $project_analyzer->setPhpVersion($version, $source);
        }
    }

    public static function runningInCI(): bool
    {
        return isset($_SERVER['TRAVIS'])
            || isset($_SERVER['CIRCLECI'])
            || isset($_SERVER['APPVEYOR'])
            || isset($_SERVER['JENKINS_URL'])
            || isset($_SERVER['SCRUTINIZER'])
            || isset($_SERVER['GITLAB_CI'])
            || isset($_SERVER['GITHUB_WORKFLOW'])
            || isset($_SERVER['DRONE']);
    }

    public static function checkRuntimeRequirements(): void
    {
        $required_php_version = 7_04_00;
        $required_php_version_text = '7.4.0';

        // the following list was taken from vendor/composer/platform_check.php
        // It includes both Psalm's requirements (from composer.json) and the
        // requirements of our dependencies `netresearch/jsonmapper` and
        // `phpdocumentor/reflection-docblock`. The latter is transitive
        // dependency of `felixfbecker/advanced-json-rpc`
        $required_extensions = [
            'dom',
            'filter',
            'json',
            'libxml',
            'pcre',
            'reflection',
            'simplexml',
            'spl',
            'tokenizer',
        ];
        $issues = [];

        if (PHP_VERSION_ID < $required_php_version) {
            $issues[] = 'Psalm requires a PHP version ">= ' . $required_php_version_text . '".'
                        . ' You are running ' . PHP_VERSION . '.';
        }

        $missing_extensions = array_filter(
            $required_extensions,
            static fn(string $ext) => !extension_loaded($ext)
        );

        if ($missing_extensions) {
            $issues[] = 'Psalm requires the following PHP extensions to be installed: '
                        . implode(', ', $missing_extensions)
                        . '.';
        }

        if ($issues) {
            fwrite(
                STDERR,
                'Psalm has detected issues in your platform:' . PHP_EOL . PHP_EOL
                . implode(PHP_EOL, $issues)
                . PHP_EOL . PHP_EOL,
            );
            exit(1);
        }
    }
}
