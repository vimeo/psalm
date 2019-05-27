<?php
require_once('command_functions.php');

use Psalm\ErrorBaseline;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider;
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
    'find-dead-code::',
    'find-unused-code::',
    'find-references-to:',
    'help',
    'ignore-baseline',
    'init',
    'monochrome',
    'no-cache',
    'no-reflection-cache',
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
    'php-version:',
    'generate-json-map:',
    'alter',
    'language-server',
    'shepherd::',
];

gc_collect_cycles();
gc_disable();

$args = array_slice($argv, 1);

// get options from command line
$options = getopt(implode('', $valid_short_options), $valid_long_options);

if (isset($options['alter'])) {
    include 'psalter.php';
    exit;
}

if (isset($options['language-server'])) {
    include 'psalm-language-server.php';
    exit;
}

array_map(
    /**
     * @param string $arg
     *
     * @return void
     */
    function ($arg) use ($valid_long_options, $valid_short_options) {
        if (substr($arg, 0, 2) === '--' && $arg !== '--') {
            $arg_name = preg_replace('/=.*$/', '', substr($arg, 2));

            if (!in_array($arg_name, $valid_long_options)
                && !in_array($arg_name . ':', $valid_long_options)
                && !in_array($arg_name . '::', $valid_long_options)
            ) {
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
        Changes the output format. Possible values: compact, console, emacs, json, pylint, xml, checkstyle

    --find-dead-code[=auto]
    --find-unused-code[=auto]
        Look for unused code. Options are 'auto' or 'always'. If no value is specified, default is 'auto'

    --find-references-to=[class|method|property]
        Searches the codebase for references to the given fully-qualified class or method,
        where method is in the format class::methodName

    --threads=INT
        If greater than one, Psalm will run analysis on multiple threads, speeding things up.

    --report=PATH
        The path where to output report file. The output format is based on the file extension.
        (Currently supported format: ".json", ".xml", ".txt", ".emacs")

    --clear-cache
        Clears all cache files that Psalm uses for this specific project

    --clear-global-cache
        Clears all cache files that Psalm uses for all projects

    --no-cache
        Runs Psalm without using cache

    --no-reflection-cache
        Runs Psalm without using cached representations of unchanged classes and files.
        Useful if you want the afterClassLikeVisit plugin hook to run every time you visit a file.

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

    --generate-json-map=PATH
        Generate a map of node references and types in JSON format, saved to the given path.

    --alter
        Run Psalter

    --language-server
        Run Psalm Language Server

HELP;

    /*
    --shepherd[=host]
        Send data to Shepherd, Psalm's GitHub integration tool.
        `host` is the location of the Shepherd server. It defaults to shepherd.dev
        More information is available at https://psalm.dev/shepherd
    */

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

$ini_handler = new \Psalm\Internal\Fork\PsalmRestarter('PSALM');

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

$type_map_location = null;

if (isset($options['generate-json-map']) && is_string($options['generate-json-map'])) {
    $type_map_location = $options['generate-json-map'];
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
    $source_dir = null;

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

    try {
        $template_contents = Psalm\Config\Creator::getContents($current_dir, $source_dir, $level);
    } catch (Psalm\Exception\ConfigCreationException $e) {
        die($e->getMessage() . PHP_EOL);
    }

    if (!file_put_contents($current_dir . 'psalm.xml', $template_contents)) {
        die('Could not write to psalm.xml' . PHP_EOL);
    }

    exit('Config file created successfully. Please re-run psalm.' . PHP_EOL);
}

$output_format = isset($options['output-format']) && is_string($options['output-format'])
    ? $options['output-format']
    : ProjectAnalyzer::TYPE_CONSOLE;

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

/** @var false|'always'|'auto' $find_unused_code */
$find_unused_code = false;
if (isset($options['find-dead-code'])) {
    $options['find-unused-code'] = $options['find-dead-code'];
}

if (isset($options['find-unused-code'])) {
    if ($options['find-unused-code'] === 'always') {
        $find_unused_code = 'always';
    } else {
        $find_unused_code = 'auto';
    }
}

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
    echo $e->getMessage() . PHP_EOL;
    exit(1);
}

if (isset($options['shepherd'])) {
    if (is_string($options['shepherd'])) {
        $config->shepherd_host = $options['shepherd'];
    }
    $shepherd_plugin = __DIR__ . '/Psalm/Plugin/Shepherd.php';

    if (!file_exists($shepherd_plugin)) {
        die('Could not find Shepherd plugin location ' . $shepherd_plugin . PHP_EOL);
    }

    $plugins[] = $shepherd_plugin;
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

    if ($cache_directory) {
        Config::removeCacheDirectory($cache_directory);
        echo 'Global cache directory deleted' . PHP_EOL;
    }

    exit;
}

$debug = array_key_exists('debug', $options) || array_key_exists('debug-by-line', $options);

if (isset($options['no-cache'])) {
    $providers = new Provider\Providers(
        new Provider\FileProvider
    );
} else {
    $no_reflection_cache = isset($options['no-reflection-cache']);

    $file_storage_cache_provider = $no_reflection_cache
        ? null
        : new Provider\FileStorageCacheProvider($config);

    $classlike_storage_cache_provider = $no_reflection_cache
        ? null
        : new Provider\ClassLikeStorageCacheProvider($config);

    $providers = new Provider\Providers(
        new Provider\FileProvider,
        new Provider\ParserCacheProvider($config),
        $file_storage_cache_provider,
        $classlike_storage_cache_provider,
        new Provider\FileReferenceCacheProvider($config)
    );
}

$project_analyzer = new ProjectAnalyzer(
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

if (isset($options['php-version'])) {
    if (!is_string($options['php-version'])) {
        die('Expecting a version number in the format x.y' . PHP_EOL);
    }

    $project_analyzer->setPhpVersion($options['php-version']);
}

$project_analyzer->getCodebase()->diff_methods = isset($options['diff-methods']);

if ($type_map_location) {
    $project_analyzer->getCodebase()->store_node_types = true;
}


$start_time = microtime(true);

$config->visitComposerAutoloadFiles($project_analyzer, $debug);

$now_time = microtime(true);

if ($debug) {
    echo 'Visiting autoload files took ' . number_format($now_time - $start_time, 3) . 's' . "\n";
}

if (array_key_exists('debug-by-line', $options)) {
    $project_analyzer->debug_lines = true;
}

if ($config->find_unused_code) {
    $find_unused_code = 'auto';
}

if ($find_references_to !== null) {
    $project_analyzer->getCodebase()->collectLocations();
    $project_analyzer->show_issues = false;
}

if ($find_unused_code) {
    $project_analyzer->getCodebase()->reportUnusedCode($find_unused_code);
}

if ($config->find_unused_variables) {
    $project_analyzer->getCodebase()->reportUnusedVariables();
}

/** @var string $plugin_path */
foreach ($plugins as $plugin_path) {
    $config->addPluginPath($plugin_path);
}

if ($paths_to_check === null) {
    $project_analyzer->check($current_dir, $is_diff);
} elseif ($paths_to_check) {
    $project_analyzer->checkPaths($paths_to_check);
}

if ($find_references_to) {
    $project_analyzer->findReferencesTo($find_references_to);
}

if (isset($options['set-baseline']) && is_string($options['set-baseline'])) {
    if ($is_diff) {
        if ($output_format === ProjectAnalyzer::TYPE_CONSOLE) {
            echo 'Cannot set baseline in --diff mode' . PHP_EOL;
        }
    } else {
        echo 'Writing error baseline to file...', PHP_EOL;

        ErrorBaseline::create(
            new \Psalm\Internal\Provider\FileProvider,
            $options['set-baseline'],
            IssueBuffer::getIssuesData()
        );

        echo "Baseline saved to {$options['set-baseline']}.";

        /** @var string $configFile */
        $configFile = Config::locateConfigFile($path_to_config ?? $current_dir);
        $configFileContents = $amendedConfigFileContents = file_get_contents($configFile);

        if ($config->error_baseline) {
            $amendedConfigFileContents = preg_replace(
                '/errorBaseline=".*?"/',
                "errorBaseline=\"{$options['set-baseline']}\"",
                $configFileContents
            );
        } else {
            $endPsalmOpenTag = strpos($configFileContents, '>', (int)strpos($configFileContents, '<psalm'));

            if (!$endPsalmOpenTag) {
                echo " Don't forget to set errorBaseline=\"{$options['set-baseline']}\" in your config.";
            } elseif ($configFileContents[$endPsalmOpenTag - 1] === "\n") {
                $amendedConfigFileContents = substr_replace(
                    $configFileContents,
                    "    errorBaseline=\"{$options['set-baseline']}\"\n>",
                    $endPsalmOpenTag,
                    1
                );
            } else {
                $amendedConfigFileContents = substr_replace(
                    $configFileContents,
                    " errorBaseline=\"{$options['set-baseline']}\">",
                    $endPsalmOpenTag,
                    1
                );
            }
        }

        file_put_contents($configFile, $amendedConfigFileContents);

        echo PHP_EOL;
    }
}

$issue_baseline = [];

if (isset($options['update-baseline'])) {
    if ($is_diff) {
        if ($output_format === ProjectAnalyzer::TYPE_CONSOLE) {
            echo 'Cannot update baseline in --diff mode' . PHP_EOL;
        }
    } else {
        $baselineFile = Config::getInstance()->error_baseline;

        if (empty($baselineFile)) {
            die('Cannot update baseline, because no baseline file is configured.' . PHP_EOL);
        }

        try {
            $issue_current_baseline = ErrorBaseline::read(
                new \Psalm\Internal\Provider\FileProvider,
                $baselineFile
            );
            $total_issues_current_baseline = ErrorBaseline::countTotalIssues($issue_current_baseline);

            $issue_baseline = ErrorBaseline::update(
                new \Psalm\Internal\Provider\FileProvider,
                $baselineFile,
                IssueBuffer::getIssuesData()
            );
            $total_issues_updated_baseline = ErrorBaseline::countTotalIssues($issue_baseline);

            $total_fixed_issues = $total_issues_current_baseline - $total_issues_updated_baseline;

            if ($total_fixed_issues > 0) {
                echo str_repeat('-', 30) . "\n";
                echo $total_fixed_issues . ' errors fixed' . "\n";
            }
        } catch (\Psalm\Exception\ConfigException $exception) {
            die('Could not update baseline file: ' . $exception->getMessage());
        }
    }
}

if (!empty(Config::getInstance()->error_baseline) && !isset($options['ignore-baseline'])) {
    try {
        $issue_baseline = ErrorBaseline::read(
            new \Psalm\Internal\Provider\FileProvider,
            (string)Config::getInstance()->error_baseline
        );
    } catch (\Psalm\Exception\ConfigException $exception) {
        die('Error while reading baseline: ' . $exception->getMessage());
    }
}

if ($type_map_location) {
    $file_map = $providers->file_reference_provider->getFileMaps();

    $name_file_map = [];

    $expected_references = [];

    foreach ($file_map as $file_path => $map) {
        $file_name = $config->shortenFileName($file_path);
        foreach ($map[0] as $map_parts) {
            $expected_references[$map_parts[1]] = true;
        }
        $map[2] = [];
        $name_file_map[$file_name] = $map;
    }

    $reference_dictionary = [];

    foreach ($providers->classlike_storage_provider->getAll() as $storage) {
        if (!$storage->location) {
            continue;
        }

        $fq_classlike_name = $storage->name;

        if (isset($expected_references[$fq_classlike_name])) {
            $reference_dictionary[$fq_classlike_name]
                = $storage->location->file_name
                    . ':' . $storage->location->getLineNumber()
                    . ':' . $storage->location->getColumn();
        }

        foreach ($storage->methods as $method_name => $method_storage) {
            if (!$method_storage->location) {
                continue;
            }

            if (isset($expected_references[$fq_classlike_name . '::' . $method_name . '()'])) {
                $reference_dictionary[$fq_classlike_name . '::' . $method_name . '()']
                    = $method_storage->location->file_name
                        . ':' . $method_storage->location->getLineNumber()
                        . ':' . $method_storage->location->getColumn();
            }
        }

        foreach ($storage->properties as $property_name => $property_storage) {
            if (!$property_storage->location) {
                continue;
            }

            if (isset($expected_references[$fq_classlike_name . '::$' . $property_name])) {
                $reference_dictionary[$fq_classlike_name . '::$' . $property_name]
                    = $property_storage->location->file_name
                        . ':' . $property_storage->location->getLineNumber()
                        . ':' . $property_storage->location->getColumn();
            }
        }
    }

    $type_map_string = json_encode(['files' => $name_file_map, 'references' => $reference_dictionary]);

    $providers->file_provider->setContents(
        $type_map_location,
        $type_map_string
    );
}

IssueBuffer::finish(
    $project_analyzer,
    !$paths_to_check,
    $start_time,
    isset($options['stats']),
    $issue_baseline
);
