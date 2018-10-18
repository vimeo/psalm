<?php
require_once('command_functions.php');

use Psalm\Checker\ProjectChecker;
use Psalm\Config;
use Psalm\IssueBuffer;

// show all errors
error_reporting(-1);

$valid_short_options = [
    'h',
    'v',
    'c:',
    'r:',
];

$valid_long_options = [
    'clear-cache',
    'config:',
    'find-dead-code',
    'help',
    'root:',
    'use-ini-defaults',
    'version',
    'tcp:',
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
    psalm-language-server [options]

Options:
    -h, --help
        Display this help message

    -v, --version
        Display the Psalm version

    -c, --config=psalm.xml
        Path to a psalm.xml configuration file. Run psalm --init to create one.

    -r, --root
        If running Psalm globally you'll need to specify a project root. Defaults to cwd

    --find-dead-code
        Look for dead code

    --clear-cache
        Clears all cache files that the language server uses for this specific project

    --use-ini-defaults
        Use PHP-provided ini defaults for memory and error display

    --tcp=url
        Use TCP mode (by default Psalm uses STDIO)

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

$ini_handler = new \Psalm\Fork\PsalmRestarter('PSALM');

$ini_handler->disableExtension('grpc');

// If XDebug is enabled, restart without it
$ini_handler->check();

setlocale(LC_CTYPE, 'C');

$output_format = isset($options['output-format']) && is_string($options['output-format'])
    ? $options['output-format']
    : ProjectChecker::TYPE_CONSOLE;

$path_to_config = isset($options['c']) && is_string($options['c']) ? realpath($options['c']) : null;

if ($path_to_config === false) {
    /** @psalm-suppress InvalidCast */
    echo 'Could not resolve path to config ' . (string)$options['c'] . PHP_EOL;
    exit(1);
}

if (isset($options['tcp'])) {
    if (!is_string($options['tcp'])) {
        echo 'tcp url should be a string' . PHP_EOL;
        exit(1);
    }
}

$find_dead_code = isset($options['find-dead-code']);

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

$config->setServerMode();
$config->setComposerClassLoader($first_autoloader);

if (isset($options['clear-cache'])) {
    $cache_directory = $config->getCacheDirectory();

    Config::removeCacheDirectory($cache_directory);
    echo 'Cache directory deleted' . PHP_EOL;
    exit;
}

$providers = new Psalm\Provider\Providers(
    new Psalm\Provider\FileProvider,
    new Psalm\Provider\ParserCacheProvider($config),
    new Psalm\Provider\FileStorageCacheProvider($config),
    new Psalm\Provider\ClassLikeStorageCacheProvider($config),
    new Psalm\Provider\FileReferenceCacheProvider($config)
);

$project_checker = new ProjectChecker(
    $config,
    $providers
);

$config->visitComposerAutoloadFiles($project_checker);

if ($find_dead_code) {
    $project_checker->getCodebase()->collectReferences();
    $project_checker->getCodebase()->reportUnusedCode();
}

$project_checker->server($options['tcp'] ?? null);
