<?php

use Psalm\Checker\ProjectChecker;
use Psalm\Config;

// show all errors
error_reporting(-1);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('memory_limit', '2048M');

// get options from command line
$options = getopt(
    'f:mhvc:ir:',
    [
        'help', 'debug', 'config:', 'monochrome', 'show-info:', 'diff',
        'file:', 'self-check', 'update-docblocks', 'output-format:',
        'find-dead-code', 'init', 'find-references-to:', 'root:', 'threads:',
        'report:', 'clear-cache', 'no-cache', 'version', 'plugin:',
    ]
);

if (array_key_exists('help', $options)) {
    $options['h'] = false;
}

if (array_key_exists('version', $options)) {
    $options['v'] = false;
}

if (array_key_exists('init', $options)) {
    $options['i'] = false;
}

if (array_key_exists('monochrome', $options)) {
    $options['m'] = false;
}

if (isset($options['config'])) {
    $options['c'] = $options['config'];
}

if (isset($options['c']) && is_array($options['c'])) {
    die('Too many config files provided' . PHP_EOL);
}

if (array_key_exists('h', $options)) {
    echo <<< HELP
Usage:
    psalm [options] [file...]

Options:
    -h, --help
        Display this help message

    -v, --version
        Display the Psalm version

    -i, --init [source_dir=src] [--level=3]
        Create a psalm config file in the current directory that points to [source_dir]
        at the required level, from 1, most strict, to 5, most permissive

    --debug
        Debug information

    -c, --config=psalm.xml
        Path to a psalm.xml configuration file. Run psalm --init to create one.

    -m, --monochrome
        Enable monochrome output

    -r, --root
        If running Psalm globally you'll need to specify a project root. Defaults to cwd

    --show-info[=BOOLEAN]
        Show non-exception parser findings

    --diff
        Runs Psalm in diff mode, only checking files that have changed (and their dependents)

    --self-check
        Psalm checks itself

    --update-docblocks
        Adds correct return types to the given file(s)

    --output-format=console
        Changes the output format. Possible values: console, json, xml

    --find-dead-code
        Look for dead code

    --find-references-to=[class|method]
        Searches the codebase for references to the given fully-qualified class or method,
        where method is in the format class::methodName

    --threads=INT
        If greater than one, Psalm will run analysis on multiple threads, speeding things up.

    --report=PATH
        The path where to output report file. The output format is base on the file extension.
        (Currently supported format: ".json", ".xml", ".txt")

    --clear-cache
        Clears all cache files that Psalm uses

    --no-cache
        Runs Psalm without using cache

HELP;

    exit;
}

if (getcwd() === false) {
    die('Cannot get current working directory');
}

if (isset($options['root'])) {
    $options['r'] = $options['root'];
}

$current_dir = (string)getcwd() . DIRECTORY_SEPARATOR;

if (isset($options['r']) && is_string($options['r'])) {
    $root_path = realpath($options['r']);

    if (!$root_path) {
        die('Could not locate root directory ' . $current_dir . DIRECTORY_SEPARATOR . $options['r'] . PHP_EOL);
    }

    $current_dir = $root_path . DIRECTORY_SEPARATOR;
}

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
            $error_message .=
                PHP_EOL . 'Add a --root=[your/project/directory] flag to specify a particular project to run Psalm on.';
        }

        die($error_message . PHP_EOL);
    }
}

foreach ($autoload_files as $file) {
    /** @psalm-suppress UnresolvableInclude */
    require_once $file;
}

if (array_key_exists('v', $options)) {
    /** @var string */
    $version = \Muglug\PackageVersions\Versions::getVersion('vimeo/psalm');
    echo 'Psalm ' . $version . PHP_EOL;
    exit;
}

// If XDebug is enabled, restart without it
(new \Composer\XdebugHandler(\Composer\Factory::createOutput()))->check();

if (isset($options['i'])) {
    if (file_exists('psalm.xml')) {
        die('A config file already exists in the current directory' . PHP_EOL);
    }

    $args = array_values(array_filter(
        array_slice($argv, 2),
        /**
         * @param string $arg
         *
         * @return bool
         */
        function ($arg) {
            return $arg !== '--ansi' && $arg !== '--no-ansi';
        }
    ));

    $level = 3;
    $source_dir = 'src';

    if (count($args)) {
        if (count($args) > 2) {
            die('Too many arguments provided for psalm --init' . PHP_EOL);
        }

        if (isset($args[1])) {
            if (!preg_match('/^[1-5]$/', $args[1])) {
                die('Config strictness must be a number between 1 and 5 inclusive' . PHP_EOL);
            }

            $level = (int)$args[1];
        }

        $source_dir = $args[0];
    }

    if (!is_dir($source_dir)) {
        $bad_dir_path = getcwd() . DIRECTORY_SEPARATOR . $source_dir;

        if (!isset($args[0])) {
            die('Please specify a directory - the default, "src", was not found in this project.' . PHP_EOL);
        }

        die('The given path "' . $bad_dir_path . '" does not appear to be a directory' . PHP_EOL);
    }

    $template_file_name = dirname(__DIR__) . '/assets/config_levels/' . $level . '.xml';

    if (!file_exists($template_file_name)) {
        die('Could not open config template ' . $template_file_name . PHP_EOL);
    }

    $template = (string)file_get_contents($template_file_name);

    $template = str_replace('<projectFiles>
        <directory name="src" />
    </projectFiles>', '<projectFiles>
        <directory name="' . $source_dir . '" />
    </projectFiles>', $template);

    if (!file_put_contents('psalm.xml', $template)) {
        die('Could not write to psalm.xml' . PHP_EOL);
    }

    exit('Config file created successfully. Please re-run psalm.' . PHP_EOL);
}

// get vars from options
$debug = array_key_exists('debug', $options);

if (isset($options['f'])) {
    $input_paths = is_array($options['f']) ? $options['f'] : [$options['f']];
} else {
    $input_paths = $argv ? $argv : null;
}

$output_format = isset($options['output-format']) && is_string($options['output-format'])
    ? $options['output-format']
    : ProjectChecker::TYPE_CONSOLE;

$paths_to_check = null;

if ($input_paths) {
    $filtered_input_paths = [];

    for ($i = 0; $i < count($input_paths); ++$i) {
        /** @var string */
        $input_path = $input_paths[$i];

        if (realpath($input_path) === realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'psalm')) {
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

$path_to_config = isset($options['c']) && is_string($options['c']) ? realpath($options['c']) : null;

if ($path_to_config === false) {
    /** @psalm-suppress InvalidCast */
    die('Could not resolve path to config ' . (string)$options['c'] . PHP_EOL);
}

$use_color = !array_key_exists('m', $options);

$show_info = isset($options['show-info'])
            ? $options['show-info'] !== 'false' && $options['show-info'] !== '0'
            : true;

$is_diff = isset($options['diff']);

$find_dead_code = isset($options['find-dead-code']);

$find_references_to = isset($options['find-references-to']) && is_string($options['find-references-to'])
    ? $options['find-references-to']
    : null;

$update_docblocks = isset($options['update-docblocks']);

$threads = isset($options['threads']) ? (int)$options['threads'] : 1;

$cache_provider = isset($options['no-cache'])
    ? new Psalm\Provider\Cache\NoParserCacheProvider()
    : new Psalm\Provider\ParserCacheProvider();

$project_checker = new ProjectChecker(
    new Psalm\Provider\FileProvider(),
    $cache_provider,
    $use_color,
    $show_info,
    $output_format,
    $threads,
    $debug,
    $update_docblocks,
    $find_dead_code || $find_references_to !== null,
    $find_references_to,
    isset($options['report']) && is_string($options['report']) ? $options['report'] : null
);

// initialise custom config, if passed
if ($path_to_config) {
    $project_checker->setConfigXML($path_to_config, $current_dir);
}

if (isset($options['clear-cache'])) {
    // initialise config if it hasn't already been
    if (!$path_to_config) {
        $project_checker->getConfigForPath($current_dir, $current_dir);
    }

    $cache_directory = Config::getInstance()->getCacheDirectory();

    Config::removeCacheDirectory($cache_directory);
    echo 'Cache directory deleted' . PHP_EOL;
    exit;
}

$config = $project_checker->getConfig();

if (!$config) {
    $project_checker->getConfigForPath($current_dir, $current_dir);
}

/** @psalm-suppress MixedArgument */
\Psalm\IssueBuffer::setStartTime(microtime(true));

if (array_key_exists('self-check', $options)) {
    $project_checker->checkDir(__DIR__);
} elseif ($paths_to_check === null) {
    $project_checker->check($current_dir, $is_diff);
} elseif ($paths_to_check) {
    foreach ($paths_to_check as $path_to_check) {
        if (is_dir($path_to_check)) {
            $project_checker->checkDir($path_to_check);
        } else {
            $project_checker->checkFile($path_to_check);
        }
    }
}
