<?php
require_once('command_functions.php');

use Psalm\Checker\ProjectChecker;
use Psalm\Config;
use Psalm\IssueBuffer;

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
        'self-check', 'output-format:', 'report:', 'find-dead-code', 'init',
        'find-references-to:', 'root:', 'threads:', 'clear-cache', 'no-cache',
        'version', 'plugin:', 'stats',
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

    --output-format=console
        Changes the output format. Possible values: console, emacs, json, pylint, xml

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

    --plugin=PATH
        Executes a plugin, an alternative to using the Psalm config

    --stats
        Shows a breakdown of Psalm's ability to infer types in the codebase

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

$vendor_dir = getVendorDir($current_dir);

requireAutoloaders($current_dir, isset($options['r']), $vendor_dir);

if (array_key_exists('v', $options)) {
    /** @var string */
    $version = \Muglug\PackageVersions\Versions::getVersion('vimeo/psalm');
    echo 'Psalm ' . $version . PHP_EOL;
    exit;
}

// If XDebug is enabled, restart without it
(new \Composer\XdebugHandler(\Composer\Factory::createOutput()))->check();

setlocale(LC_CTYPE, 'C');

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

$output_format = isset($options['output-format']) && is_string($options['output-format'])
    ? $options['output-format']
    : ProjectChecker::TYPE_CONSOLE;

$paths_to_check = getPathsToCheck(isset($options['f']) ? $options['f'] : null);

$plugins = [];

if (isset($options['plugin'])) {
    $plugins = $options['plugin'];

    if (!is_array($plugins)) {
        $plugins = [$plugins];
    }
}

$path_to_config = isset($options['c']) && is_string($options['c']) ? realpath($options['c']) : null;

if ($path_to_config === false) {
    /** @psalm-suppress InvalidCast */
    die('Could not resolve path to config ' . (string)$options['c'] . PHP_EOL);
}

$show_info = isset($options['show-info'])
    ? $options['show-info'] !== 'false' && $options['show-info'] !== '0'
    : true;

$is_diff = isset($options['diff']);

$find_dead_code = isset($options['find-dead-code']);

$find_references_to = isset($options['find-references-to']) && is_string($options['find-references-to'])
    ? $options['find-references-to']
    : null;

$threads = isset($options['threads']) ? (int)$options['threads'] : 1;

$cache_provider = isset($options['no-cache'])
    ? new Psalm\Provider\NoCache\NoParserCacheProvider()
    : new Psalm\Provider\ParserCacheProvider();

// initialise custom config, if passed
if ($path_to_config) {
    $config = Config::loadFromXMLFile($path_to_config, $current_dir);
} else {
    $config = Config::getConfigForPath($current_dir, $current_dir, $output_format);
}

$file_storage_cache_provider = isset($options['no-cache'])
    ? new Psalm\Provider\NoCache\NoFileStorageCacheProvider()
    : new Psalm\Provider\FileStorageCacheProvider($config);

$classlike_storage_cache_provider = isset($options['no-cache'])
    ? new Psalm\Provider\NoCache\NoClassLikeStorageCacheProvider()
    : new Psalm\Provider\ClassLikeStorageCacheProvider($config);

if (isset($options['clear-cache'])) {
    $cache_directory = $config->getCacheDirectory();

    Config::removeCacheDirectory($cache_directory);
    echo 'Cache directory deleted' . PHP_EOL;
    exit;
}

$project_checker = new ProjectChecker(
    $config,
    new Psalm\Provider\FileProvider(),
    $cache_provider,
    $file_storage_cache_provider,
    $classlike_storage_cache_provider,
    !array_key_exists('m', $options),
    $show_info,
    $output_format,
    $threads,
    array_key_exists('debug', $options),
    isset($options['report']) && is_string($options['report']) ? $options['report'] : null
);

if ($find_dead_code || $find_references_to !== null) {
    $project_checker->getCodebase()->collectReferences();
}

if ($find_dead_code) {
    $project_checker->getCodebase()->reportUnusedCode();
}

/** @var string $plugin_path */
foreach ($plugins as $plugin_path) {
    Config::getInstance()->addPluginPath($current_dir . DIRECTORY_SEPARATOR . $plugin_path);
}

$start_time = (float) microtime(true);

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

if ($find_references_to) {
    $project_checker->findReferencesTo($find_references_to);
} elseif ($find_dead_code && !$paths_to_check && !$is_diff) {
    if ($threads > 1) {
        if ($output_format === ProjectChecker::TYPE_CONSOLE) {
            echo 'Unused classes and methods cannot currently be found in multithreaded mode' . PHP_EOL;
        }
    } else {
        $project_checker->checkClassReferences();
    }
}

IssueBuffer::finish($project_checker, !$is_diff, $start_time, isset($options['stats']));
