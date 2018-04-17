<?php

/**
 * @param  string $current_dir
 * @param  bool   $has_explicit_root
 * @param  string $vendor_dir
 *
 * @psalm-suppress MixedInferred
 *
 * @return \Composer\Autoload\ClassLoader
 */
function requireAutoloaders($current_dir, $has_explicit_root, $vendor_dir)
{
    $autoload_roots = [$current_dir];

    $psalm_dir = dirname(__DIR__);

    if (realpath($psalm_dir) !== realpath($current_dir)) {
        $autoload_roots[] = $psalm_dir;
    }

    $autoload_files = [];

    foreach ($autoload_roots as $autoload_root) {
        $has_autoloader = false;

        $nested_autoload_file = dirname(dirname($autoload_root)) . DIRECTORY_SEPARATOR . 'autoload.php';

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

        if (!$has_autoloader) {
            $error_message = 'Could not find any composer autoloaders in ' . $autoload_root;

            if (!$has_explicit_root) {
                $error_message .= PHP_EOL . 'Add a --root=[your/project/directory] flag '
                    . 'to specify a particular project to run Psalm on.';
            }

            echo $error_message . PHP_EOL;
            exit(1);
        }
    }

    $first_autoloader = null;

    foreach ($autoload_files as $file) {
        /**
         * @psalm-suppress UnresolvableInclude
         * @var \Composer\Autoload\ClassLoader
         */
        $autoloader = require_once $file;

        if (!$first_autoloader) {
            $first_autoloader = $autoloader;
        }
    }

    if ($first_autoloader === null) {
        throw new \LogicException('Cannot be null here');
    }

    define('PSALM_VERSION', (string) \Muglug\PackageVersions\Versions::getVersion('vimeo/psalm'));

    return $first_autoloader;
}

/**
 * @param  string $current_dir
 *
 * @return string
 *
 * @psalm-suppress PossiblyFalseArgument
 * @psalm-suppress MixedArrayAccess
 * @psalm-suppress MixedAssignment
 */
function getVendorDir($current_dir)
{
    $composer_json_path = $current_dir . DIRECTORY_SEPARATOR . 'composer.json';

    if (!file_exists($composer_json_path)) {
        return 'vendor';
    }

    if (!$composer_json = json_decode(file_get_contents($composer_json_path), true)) {
        throw new \UnexpectedValueException('Invalid composer.json at ' . $composer_json_path);
    }

    if (isset($composer_json['config']['vendor-dir'])) {
        return (string) $composer_json['config']['vendor-dir'];
    }

    return 'vendor';
}

/**
 * @param  string|array|null|false $f_paths
 *
 * @return string[]|null
 */
function getPathsToCheck($f_paths)
{
    global $argv;

    $paths_to_check = [];

    if ($f_paths) {
        $input_paths = is_array($f_paths) ? $f_paths : [$f_paths];
    } else {
        $input_paths = $argv ? $argv : null;
    }

    if ($input_paths) {
        $filtered_input_paths = [];

        for ($i = 0; $i < count($input_paths); ++$i) {
            /** @var string */
            $input_path = $input_paths[$i];

            if (realpath($input_path) === realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'psalm')
                || realpath($input_path) === realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'psalter')
                || realpath($input_path) === \Phar::running(false)
            ) {
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

            if (substr($input_path, 0, 2) === '--' && strlen($input_path) > 2) {
                continue;
            }

            $filtered_input_paths[] = $input_path;
        }

        if ($filtered_input_paths === ['-']) {
            $meta = stream_get_meta_data(STDIN);
            stream_set_blocking(STDIN, false);
            if ($stdin = fgets(STDIN)) {
                $filtered_input_paths = preg_split('/\s+/', trim($stdin));
            }
            /** @var bool */
            $blocked = $meta['blocked'];
            stream_set_blocking(STDIN, $blocked);
        }

        foreach ($filtered_input_paths as $i => $path_to_check) {
            if ($path_to_check[0] === '-') {
                echo 'Invalid usage, expecting psalm [options] [file...]' . PHP_EOL;
                exit(1);
            }

            if (!file_exists($path_to_check)) {
                echo 'Cannot locate ' . $path_to_check . PHP_EOL;
                exit(1);
            }

            $path_to_check = realpath($path_to_check);

            if (!$path_to_check) {
                echo 'Error getting realpath for file' . PHP_EOL;
                exit(1);
            }

            $paths_to_check[] = $path_to_check;
        }

        if (!$paths_to_check) {
            $paths_to_check = null;
        }
    }

    return $paths_to_check;
}
