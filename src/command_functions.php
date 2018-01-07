<?php

/**
 * @param  string $current_dir
 *
 * @return void
 */
function requireAutoloaders($current_dir)
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

        if (file_exists($nested_autoload_file)) {
            $autoload_files[] = realpath($nested_autoload_file);
            $has_autoloader = true;
        }

        $vendor_autoload_file = $autoload_root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

        if (file_exists($vendor_autoload_file)) {
            $autoload_files[] = realpath($vendor_autoload_file);
            $has_autoloader = true;
        }

        if (!$has_autoloader) {
            $error_message = 'Could not find any composer autoloaders in ' . $autoload_root;

            if (!isset($options['r'])) {
                $error_message .= PHP_EOL . 'Add a --root=[your/project/directory] flag '
                    . 'to specify a particular project to run Psalm on.';
            }

            die($error_message . PHP_EOL);
        }
    }

    foreach ($autoload_files as $file) {
        /** @psalm-suppress UnresolvableInclude */
        require_once $file;
    }
}

/**
 * @param  string|string[]|null $f_paths
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

        stream_set_blocking(STDIN, false);

        if ($filtered_input_paths === ['-'] && $stdin = fgets(STDIN)) {
            $filtered_input_paths = preg_split('/\s+/', trim($stdin));
        }

        foreach ($filtered_input_paths as $i => $path_to_check) {
            if ($path_to_check[0] === '-') {
                die('Invalid usage, expecting psalm [options] [file...]' . PHP_EOL);
            }

            if (!file_exists($path_to_check)) {
                die('Cannot locate ' . $path_to_check . PHP_EOL);
            }

            $path_to_check = realpath($path_to_check);

            if (!$path_to_check) {
                die('Error getting realpath for file' . PHP_EOL);
            }

            $paths_to_check[] = $path_to_check;
        }

        if (!$paths_to_check) {
            $paths_to_check = null;
        }
    }

    return $paths_to_check;
}
