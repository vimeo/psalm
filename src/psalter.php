<?php
require_once('command_functions.php');

use Psalm\Checker\ProjectChecker;
use Psalm\Config;

// show all errors
error_reporting(-1);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('memory_limit', '2048M');

// get options from command line
$options = getopt(
    'f:mhr:',
    [
        'help', 'debug', 'config:', 'file:', 'root:',
        'plugin:', 'replace-code', 'issues:', 'target-php-version:', 'dry-run',
    ]
);

if (array_key_exists('help', $options)) {
    $options['h'] = false;
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

    --debug
        Debug information

    -c, --config=psalm.xml
        Path to a psalm.xml configuration file. Run psalm --init to create one.

    -m, --monochrome
        Enable monochrome output

    -r, --root
        If running Psalm globally you'll need to specify a project root. Defaults to cwd

    --plugin=PATH
        Executes a plugin, an alternative to using the Psalm config

    --dry-run
        Shows a diff of all the changes, without making them

    --php-version=PHP_MAJOR_VERSION.PHP_MINOR_VERSION

    --issues=IssueType1,IssueType2
        If any issues can be fixed automatically, Psalm will update the codebase

HELP;

    exit;
}

if (!isset($options['issues'])) {
    die('Please specify the issues you want to fix with --issues=IssueOne,IssueTwo' . PHP_EOL);
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

requireAutoloaders($current_dir);

$paths_to_check = getPathsToCheck(isset($options['f']) ? $options['f'] : null);

$path_to_config = isset($options['c']) && is_string($options['c']) ? realpath($options['c']) : null;

if ($path_to_config === false) {
    /** @psalm-suppress InvalidCast */
    die('Could not resolve path to config ' . (string)$options['c'] . PHP_EOL);
}

$project_checker = new ProjectChecker(
    new Psalm\Provider\FileProvider(),
    new Psalm\Provider\ParserCacheProvider(),
    !array_key_exists('m', $options),
    false,
    ProjectChecker::TYPE_CONSOLE,
    1,
    array_key_exists('debug', $options)
);

// initialise custom config, if passed
if ($path_to_config) {
    $project_checker->setConfigXML($path_to_config, $current_dir);
}

$config = $project_checker->getConfig();

if (!$config) {
    $project_checker->getConfigForPath($current_dir, $current_dir);
}

if (!is_string($options['issues']) || !$options['issues']) {
    die('Expecting a comma-separated list of issues' . PHP_EOL);
}

$issues = explode(',', $options['issues']);

$keyed_issues = [];
foreach ($issues as $issue) {
    $keyed_issues[$issue] = true;
}

$php_major_version = PHP_MAJOR_VERSION;
$php_minor_version = PHP_MINOR_VERSION;

if (isset($options['php-version'])) {
    if (!is_string($options['php-version']) || !preg_match('/^(5\.[456]|7\.[012])^/', $options['php-version'])) {
        die('Expecting a version number in the format x.y' . PHP_EOL);
    }

    list($php_major_version, $php_minor_version) = explode('.', $options['php-version']);
}

$project_checker->alterCodeAfterCompletion((int) $php_major_version, (int) $php_minor_version);
$project_checker->setIssuesToFix($keyed_issues);

/** @psalm-suppress MixedArgument */
\Psalm\IssueBuffer::setStartTime(microtime(true));

if ($paths_to_check === null) {
    $project_checker->check($current_dir);
} elseif ($paths_to_check) {
    foreach ($paths_to_check as $path_to_check) {
        if (is_dir($path_to_check)) {
            $project_checker->checkDir($path_to_check);
        } else {
            $project_checker->checkFile($path_to_check);
        }
    }
}
