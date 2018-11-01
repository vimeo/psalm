<?php
require_once('command_functions.php');

use Psalm\ErrorBaseline;
use Psalm\Checker\ProjectChecker;
use Psalm\Config;
use Psalm\IssueBuffer;

// show all errors
error_reporting(-1);

$valid_short_options = [
    'f:',
    'm',
    'h',
    'v',
    'c:',
    'i',
    'r:',
];

$valid_long_options = [
    'clear-cache',
    'clear-global-cache',
    'config:',
    'debug',
    'debug-by-line',
    'diff',
    'diff-methods',
    'disable-extension:',
    'find-dead-code',
    'find-references-to:',
    'help',
    'ignore-baseline',
    'init',
    'monochrome',
    'no-cache',
    'output-format:',
    'plugin:',
    'report:',
    'root:',
    'set-baseline:',
    'show-info:',
    'show-snippet:',
    'stats',
    'threads:',
    'update-baseline',
    'use-ini-defaults',
    'version',
];

$args = array_slice($argv, 1);

array_map(
    /**
     * @param string $arg
     *
     * @return void
     */
    function ($arg) use ($valid_long_options, $valid_short_options) {
        if (substr($arg, 0, 2) === '--' && $arg !== '--') {
            $arg_name = preg_replace('/=.*$/', '', substr($arg, 2));

            if (!in_array($arg_name, $valid_long_options) && !in_array($arg_name . ':', $valid_long_options)) {
                echo 'Unrecognised argument "--' . $arg_name . '"' . PHP_EOL
                    . 'Type --help to see a list of supported arguments'. PHP_EOL;
                exit(1);
            }
        } elseif (substr($arg, 0, 2) === '-' && $arg !== '-' && $arg !== '--') {
            $arg_name = preg_replace('/=.*$/', '', substr($arg, 1));

            if (!in_array($arg_name, $valid_short_options) && !in_array($arg_name . ':', $valid_short_options)) {
                echo 'Unrecognised argument "-' . $arg_name . '"' . PHP_EOL
                    . 'Type --help to see a list of supported arguments'. PHP_EOL;
                exit(1);
            }
        }
    },
    $args
);

// get options from command line
$options = getopt(implode('', $valid_short_options), $valid_long_options);

if (!array_key_exists('use-ini-defaults', $options)) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    ini_set('memory_limit', (string) (4 * 1024 * 1024 * 1024));
}

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
    echo 'Too many config files provided' . PHP_EOL;
    exit(1);
}

if (array_key_exists('h', $options)) {
    echo <<<HELP
Usage:
    psalm [options] [file...]

Options:
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

    -c, --config=psalm.xml
        Path to a psalm.xml configuration file. Run psalm --init to create one.

    -m, --monochrome
        Enable monochrome output

    -r, --root
        If running Psalm globally you'll need to specify a project root. Defaults to cwd

    --show-info[=BOOLEAN]
        Show non-exception parser findings

    --show-snippet[=true]
        Show code snippets with errors. Options are 'true' or 'false'

    --diff
        Runs Psalm in diff mode, only checking files that have changed (and their dependents)

    --diff-methods
        Only checks methods that have changed (and their dependents)

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
        Clears all cache files that Psalm uses for this specific project

    --clear-global-cache
        Clears all cache files that Psalm uses for all projects

    --no-cache
        Runs Psalm without using cache

    --plugin=PATH
        Executes a plugin, an alternative to using the Psalm config

    --stats
        Shows a breakdown of Psalm's ability to infer types in the codebase

    --use-ini-defaults
        Use PHP-provided ini defaults for memory and error display

    --disable-extension=[extension]
        Used to disable certain extensions while Psalm is running.

    --set-baseline=PATH
        Save all current error level issues to a file, to mark them as info in subsequent runs

    --ignore-baseline
        Ignore the error baseline

    --update-baseline
        Update the baseline by removing fixed issues. This will not add new issues to the baseline

HELP;

    exit;
}

if (getcwd() === false) {
    echo 'Cannot get current working directory' . PHP_EOL;
    exit(1);
}

if (isset($options['root'])) {
    $options['r'] = $options['root'];
}

$current_dir = (string)getcwd() . DIRECTORY_SEPARATOR;

if (isset($options['r']) && is_string($options['r'])) {
    $root_path = realpath($options['r']);

    if (!$root_path) {
        echo 'Could not locate root directory ' . $current_dir . DIRECTORY_SEPARATOR . $options['r'] . PHP_EOL;
        exit(1);
    }

    $current_dir = $root_path . DIRECTORY_SEPARATOR;
}

$vendor_dir = getVendorDir($current_dir);

$first_autoloader = requireAutoloaders($current_dir, isset($options['r']), $vendor_dir);

if (array_key_exists('v', $options)) {
    echo 'Psalm ' . PSALM_VERSION . PHP_EOL;
    exit;
}

$threads = isset($options['threads']) ? (int)$options['threads'] : 1;

$ini_handler = new \Psalm\Fork\PsalmRestarter('PSALM');

if (isset($options['disable-extension'])) {
    if (is_array($options['disable-extension'])) {
        /** @psalm-suppress MixedAssignment */
        foreach ($options['disable-extension'] as $extension) {
            if (is_string($extension)) {
                $ini_handler->disableExtension($extension);
            }
        }
    } elseif (is_string($options['disable-extension'])) {
        $ini_handler->disableExtension($options['disable-extension']);
    }
}

if ($threads > 1) {
    $ini_handler->disableExtension('grpc');
}

// If XDebug is enabled, restart without it
$ini_handler->check();

setlocale(LC_CTYPE, 'C');

if (isset($options['set-baseline'])) {
    if (is_array($options['set-baseline'])) {
        die('Only one baseline file can be created at a time' . PHP_EOL);
    }
}

if (isset($options['i'])) {
    if (file_exists($current_dir . 'psalm.xml')) {
        die('A config file already exists in the current directory' . PHP_EOL);
    }

    $args = array_values(array_filter(
        $args,
        /**
         * @param string $arg
         *
         * @return bool
         */
        function ($arg) {
            return $arg !== '--ansi'
                && $arg !== '--no-ansi'
                && $arg !== '-i'
                && $arg !== '--init'
                && strpos($arg, '--root=') !== 0
                && strpos($arg, '--r=') !== 0;
        }
    ));

    $level = 3;
    $source_dir = 'src';

    if (count($args)) {
        if (count($args) > 2) {
            die('Too many arguments provided for psalm --init' . PHP_EOL);
        }

        if (isset($args[1])) {
            if (!preg_match('/^[1-8]$/', $args[1])) {
                die('Config strictness must be a number between 1 and 8 inclusive' . PHP_EOL);
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

    $template = str_replace(
        '<directory name="src" />',
        '<directory name="' . $source_dir . '" />',
        $template
    );

    if (!\Phar::running(false)) {
        $template = str_replace(
            'vendor/vimeo/psalm/config.xsd',
            'file://' . realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config.xsd'),
            $template
        );
    }

    if (!file_put_contents($current_dir . 'psalm.xml', $template)) {
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
    echo 'Could not resolve path to config ' . (string)$options['c'] . PHP_EOL;
    exit(1);
}

$show_info = isset($options['show-info'])
    ? $options['show-info'] !== 'false' && $options['show-info'] !== '0'
    : true;

$is_diff = isset($options['diff']);

$find_dead_code = isset($options['find-dead-code']);

$find_references_to = isset($options['find-references-to']) && is_string($options['find-references-to'])
    ? $options['find-references-to']
    : null;

// initialise custom config, if passed
try {
    if ($path_to_config) {
        $config = Config::loadFromXMLFile($path_to_config, $current_dir);
    } else {
        $config = Config::getConfigForPath($current_dir, $current_dir, $output_format);
    }
} catch (Psalm\Exception\ConfigException $e) {
    echo $e->getMessage();
    exit(1);
}

$config->setComposerClassLoader($first_autoloader);

if (isset($options['clear-cache'])) {
    $cache_directory = $config->getCacheDirectory();

    Config::removeCacheDirectory($cache_directory);
    echo 'Cache directory deleted' . PHP_EOL;
    exit;
}

if (isset($options['clear-global-cache'])) {
    $cache_directory = $config->getGlobalCacheDirectory();

    Config::removeCacheDirectory($cache_directory);
    echo 'Global cache directory deleted' . PHP_EOL;
    exit;
}

$debug = array_key_exists('debug', $options) || array_key_exists('debug-by-line', $options);

if (isset($options['no-cache'])) {
    $providers = new Psalm\Provider\Providers(
        new Psalm\Provider\FileProvider
    );
} else {
    $providers = new Psalm\Provider\Providers(
        new Psalm\Provider\FileProvider,
        new Psalm\Provider\ParserCacheProvider($config),
        new Psalm\Provider\FileStorageCacheProvider($config),
        new Psalm\Provider\ClassLikeStorageCacheProvider($config),
        new Psalm\Provider\FileReferenceCacheProvider($config)
    );
}

$project_checker = new ProjectChecker(
    $config,
    $providers,
    !array_key_exists('m', $options),
    $show_info,
    $output_format,
    $threads,
    $debug,
    isset($options['report']) && is_string($options['report']) ? $options['report'] : null,
    !isset($options['show-snippet']) || $options['show-snippet'] !== "false"
);

$project_checker->diff_methods = isset($options['diff-methods']);

$start_time = microtime(true);

$config->visitComposerAutoloadFiles($project_checker, $debug);

$now_time = microtime(true);

if ($debug) {
    echo 'Visiting autoload files took ' . number_format($now_time - $start_time, 3) . 's' . "\n";
}

if (array_key_exists('debug-by-line', $options)) {
    $project_checker->debug_lines = true;
}

if ($find_dead_code || $find_references_to !== null) {
    $project_checker->getCodebase()->collectReferences();

    if ($find_references_to) {
        $project_checker->show_issues = false;
    }
}

if ($find_dead_code) {
    $project_checker->getCodebase()->reportUnusedCode();
}

/** @var string $plugin_path */
foreach ($plugins as $plugin_path) {
    Config::getInstance()->addPluginPath($current_dir . DIRECTORY_SEPARATOR . $plugin_path);
}

if ($paths_to_check === null) {
    $project_checker->check($current_dir, $is_diff);
} elseif ($paths_to_check) {
    $project_checker->checkPaths($paths_to_check);
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

if (isset($options['set-baseline']) && is_string($options['set-baseline'])) {
    echo 'Writing error baseline to file...', PHP_EOL;

    ErrorBaseline::create(
        new \Psalm\Provider\FileProvider,
        $options['set-baseline'],
        IssueBuffer::getIssuesData()
    );

    echo "Baseline saved to {$options['set-baseline']}.";

    if (Config::getInstance()->error_baseline !== $options['set-baseline']) {
        echo " Don't forget to set errorBaseline=\"{$options['set-baseline']}\" in your config.";
    }

    echo PHP_EOL;
}

$issue_baseline = [];

if (isset($options['update-baseline'])) {
    $baselineFile = Config::getInstance()->error_baseline;

    if (empty($baselineFile)) {
        die('Cannot update baseline, because no baseline file is configured.' . PHP_EOL);
    }

    try {
        $issue_baseline = ErrorBaseline::update(
            new \Psalm\Provider\FileProvider,
            $baselineFile,
            IssueBuffer::getIssuesData()
        );
    } catch (\Psalm\Exception\ConfigException $exception) {
        die('Could not update baseline file: ' . $exception->getMessage());
    }
}

if (!empty(Config::getInstance()->error_baseline) && !isset($options['ignore-baseline'])) {
    try {
        $issue_baseline = ErrorBaseline::read(
            new \Psalm\Provider\FileProvider,
            (string)Config::getInstance()->error_baseline
        );
    } catch (\Psalm\Exception\ConfigException $exception) {
        die('Error while reading baseline: ' . $exception->getMessage());
    }
}

IssueBuffer::finish(
    $project_checker,
    !$paths_to_check,
    $start_time,
    isset($options['stats']),
    $issue_baseline
);
