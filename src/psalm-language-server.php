<?php
require_once('command_functions.php');

use Psalm\Config;
use Psalm\Internal\Analyzer\ProjectAnalyzer;

gc_disable();

// show all errors
error_reporting(-1);

require_once __DIR__ . '/Psalm/Internal/exception_handler.php';

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
    'tcp-server',
    'disable-on-change::',
    'enable-autocomplete',
    'use-extended-diagnostic-codes',
    'verbose'
];

$args = array_slice($argv, 1);

$psalm_proxy = array_search('--language-server', $args, true);

if ($psalm_proxy !== false) {
    unset($args[$psalm_proxy]);
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

            if (!in_array($arg_name, $valid_long_options, true)
                && !in_array($arg_name . ':', $valid_long_options, true)
                && !in_array($arg_name . '::', $valid_long_options, true)
            ) {
                fwrite(
                    STDERR,
                    'Unrecognised argument "--' . $arg_name . '"' . PHP_EOL
                    . 'Type --help to see a list of supported arguments' . PHP_EOL
                );
                error_log('Bad argument');
                exit(1);
            }
        } elseif (substr($arg, 0, 2) === '-' && $arg !== '-' && $arg !== '--') {
            $arg_name = preg_replace('/=.*$/', '', substr($arg, 1));

            if (!in_array($arg_name, $valid_short_options, true)
                && !in_array($arg_name . ':', $valid_short_options, true)
            ) {
                fwrite(
                    STDERR,
                    'Unrecognised argument "-' . $arg_name . '"' . PHP_EOL
                    . 'Type --help to see a list of supported arguments' . PHP_EOL
                );
                error_log('Bad argument');
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
    fwrite(STDERR, 'Too many config files provided' . PHP_EOL);
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

    --tcp-server
        Use TCP in server mode (default is client)

    --disable-on-change[=line-number-threshold]
        If added, the language server will not respond to onChange events.
        You can also specify a line count over which Psalm will not run on-change events.

    --enable-autocomplete[=BOOL]
        Enables or disables autocomplete on methods and properties. Default is true.

    --use-extended-diagnostic-codes
        Enables sending help uri links with the code in diagnostic messages.

    --verbose
        Will send log messages to the client with information.
HELP;

    exit;
}

if (array_key_exists('v', $options)) {
    echo 'Psalm ' . PSALM_VERSION . PHP_EOL;
    exit;
}

if (getcwd() === false) {
    fwrite(STDERR, 'Cannot get current working directory' . PHP_EOL);
    exit(1);
}

if (isset($options['root'])) {
    $options['r'] = $options['root'];
}

$current_dir = (string)getcwd() . DIRECTORY_SEPARATOR;

if (isset($options['r']) && is_string($options['r'])) {
    $root_path = realpath($options['r']);

    if (!$root_path) {
        fwrite(
            STDERR,
            'Could not locate root directory ' . $current_dir . DIRECTORY_SEPARATOR . $options['r'] . PHP_EOL
        );
        exit(1);
    }

    $current_dir = $root_path . DIRECTORY_SEPARATOR;
}

$vendor_dir = getVendorDir($current_dir);

$first_autoloader = requireAutoloaders($current_dir, isset($options['r']), $vendor_dir);

$ini_handler = new \Psalm\Internal\Fork\PsalmRestarter('PSALM');

$ini_handler->disableExtension('grpc');

// If Xdebug is enabled, restart without it
$ini_handler->check();

setlocale(LC_CTYPE, 'C');

$path_to_config = get_path_to_config($options);

if (isset($options['tcp'])) {
    if (!is_string($options['tcp'])) {
        fwrite(STDERR, 'tcp url should be a string' . PHP_EOL);
        exit(1);
    }
}

$find_dead_code = isset($options['find-dead-code']);

$config = initialiseConfig($path_to_config, $current_dir, \Psalm\Report::TYPE_CONSOLE, $first_autoloader);

if ($config->resolve_from_config_file) {
    $current_dir = $config->base_dir;
    chdir($current_dir);
}

$config->setServerMode();

if (isset($options['clear-cache'])) {
    $cache_directory = $config->getCacheDirectory();

    Config::removeCacheDirectory($cache_directory);
    echo 'Cache directory deleted' . PHP_EOL;
    exit;
}

$providers = new Psalm\Internal\Provider\Providers(
    new Psalm\Internal\Provider\FileProvider,
    new Psalm\Internal\Provider\ParserCacheProvider($config),
    new Psalm\Internal\Provider\FileStorageCacheProvider($config),
    new Psalm\Internal\Provider\ClassLikeStorageCacheProvider($config),
    new Psalm\Internal\Provider\FileReferenceCacheProvider($config),
    new Psalm\Internal\Provider\ProjectCacheProvider($current_dir . DIRECTORY_SEPARATOR . 'composer.lock')
);

$project_analyzer = new ProjectAnalyzer(
    $config,
    $providers
);

if (isset($options['disable-on-change'])) {
    $project_analyzer->onchange_line_limit = (int) $options['disable-on-change'];
}

$project_analyzer->provide_completion = !isset($options['enable-autocomplete'])
    || !is_string($options['enable-autocomplete'])
    || strtolower($options['enable-autocomplete']) !== 'false';

$config->visitComposerAutoloadFiles($project_analyzer);

if ($find_dead_code) {
    $project_analyzer->getCodebase()->reportUnusedCode();
}

if (isset($options['use-extended-diagnostic-codes'])) {
    $project_analyzer->language_server_use_extended_diagnostic_codes = true;
}

if (isset($options['verbose'])) {
    $project_analyzer->language_server_verbose = true;
}

$project_analyzer->server($options['tcp'] ?? null, isset($options['tcp-server']) ? true : false);
