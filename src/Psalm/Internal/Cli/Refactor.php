<?php

declare(strict_types=1);

namespace Psalm\Internal\Cli;

use AssertionError;
use Composer\XdebugHandler\XdebugHandler;
use Psalm\Exception\RefactorException;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\CliUtils;
use Psalm\Internal\Composer;
use Psalm\Internal\ErrorHandler;
use Psalm\Internal\IncludeCollector;
use Psalm\Internal\Provider\ClassLikeStorageCacheProvider;
use Psalm\Internal\Provider\FileProvider;
use Psalm\Internal\Provider\FileStorageCacheProvider;
use Psalm\Internal\Provider\ProjectCacheProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\IssueBuffer;
use Psalm\Progress\DebugProgress;
use Psalm\Progress\DefaultProgress;
use Psalm\Progress\VoidProgress;
use Psalm\Report;
use Psalm\Report\ReportOptions;

use function array_key_exists;
use function array_slice;
use function chdir;
use function count;
use function end;
use function explode;
use function fwrite;
use function gc_collect_cycles;
use function gc_disable;
use function getcwd;
use function in_array;
use function is_array;
use function is_string;
use function microtime;
use function preg_last_error_msg;
use function preg_match;
use function preg_split;
use function realpath;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function strpos;
use function substr;
use function trim;

use const PHP_EOL;
use const STDERR;

// phpcs:disable PSR1.Files.SideEffects

require_once __DIR__ . '/../ErrorHandler.php';
require_once __DIR__ . '/../CliUtils.php';
require_once __DIR__ . '/../Composer.php';
require_once __DIR__ . '/../IncludeCollector.php';
require_once __DIR__ . '/../../IssueBuffer.php';

/**
 * @internal
 */
final class Refactor
{
    private const IDENTIFIER = '[a-zA-Z_\\x80-\\xff][a-zA-Z0-9_\\x80-\\xff]*';
    private const CLASS_NAME = '(?:' . self::IDENTIFIER . '\\\\)*' . self::IDENTIFIER;

    public const LONG_OPTIONS = [
        'help', 'debug', 'debug-by-line', 'debug-emitted-issues', 'config:', 'root:', 'monochrome',
        'scan-threads:', 'threads:', 'memory-limit:', 'use-ini-defaults', 'move:', 'into:', 'rename:', 'to:',
        'no-progress', 'no-cache',
    ];

    /** @param array<int,string> $argv */
    public static function run(array $argv): void
    {
        CliUtils::checkRuntimeRequirements();
        $argv = CliUtils::chosenByOperator($argv);

        gc_collect_cycles();
        gc_disable();

        ErrorHandler::install($argv);

        $args = array_slice($argv, 1);

        $valid_short_options = ['f:', 'm', 'h', 'r:', 'c:'];
        $valid_long_options = self::LONG_OPTIONS;

        // get options from command line
        $options = CliUtils::getOptions($args, $valid_short_options, $valid_long_options);

        // --refactor is an option of psalm, which forwards the call to psalm-refactor
        CliUtils::rejectUnrecognisedOptions($args, $valid_short_options, $valid_long_options, ['--refactor']);

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
            fwrite(STDERR, 'Too many config files provided' . PHP_EOL);
            exit(1);
        }

        if (array_key_exists('h', $options)) {
            echo <<<HELP
            Usage:
                psalm-refactor [options] [symbol1] into [symbol2]

            Options:
                -h, --help
                    Display this help message

                --debug, --debug-by-line, --debug-emitted-issues
                    Debug information

                -m
                    Enable monochrome output.
                    Auto-enabled when NO_COLOR is set to a non-empty value, when
                    TERM=dumb, when stdout is not a terminal, or when an AI coding
                    agent is driving the shell.
                    FORCE_COLOR set to a non-empty value forces colors on instead.

                --no-progress
                    Disable the progress indicator.
                    Auto-enabled when an AI coding agent is driving the shell outside CI.

                -m, --monochrome
                    Enable monochrome output.

                -c, --config=psalm.xml
                    Path to a psalm.xml configuration file. Run psalm --init to create one.

                -r, --root
                    If running Psalm globally you'll need to specify a project root. Defaults to cwd

                --threads=INT|auto
                    If greater than one, Psalm will run the scan and analysis on multiple threads,
                    speeding things up.
                    `auto` (the default) uses the threads setting of the config, or else one thread per CPU core.

                --scan-threads=INT|auto
                    If greater than one, Psalm will run the scan on multiple threads, speeding things up
                    (if specified, takes priority over the --threads flag).
                    `auto` (the default) uses the scanThreads setting of the config, or else one thread per CPU core.

                --use-ini-defaults
                    Use PHP-provided ini defaults for memory and error display

                --memory-limit=LIMIT
                    Use a specific memory limit. Cannot be combined with --use-ini-defaults

                --move "[Identifier]" --into "[Class]"
                    Moves the specified item into the class. More than one item can be moved into a class
                    by passing a comma-separated list of values e.g.

                    --move "Ns\Foo::bar,Ns\Foo::baz" --into "Biz\Bang\DestinationClass"

                --no-cache
                    Runs Psalm without using cache

                --rename "[Identifier]" --to "[NewIdentifier]"
                    Renames a specified item (e.g. method) and updates all references to it that Psalm can
                    identify.

            HELP;

            exit;
        }

        if (isset($options['root'])) {
            $options['r'] = $options['root'];
        }
        CliUtils::setMemoryLimit($options);

        $current_dir = (string) getcwd();

        if (isset($options['r']) && is_string($options['r'])) {
            $root_path = realpath($options['r']);

            if ($root_path === false) {
                fwrite(
                    STDERR,
                    CliUtils::getRootDirectoryNotFoundMessage($current_dir, $options['r']) . PHP_EOL,
                );
                exit(1);
            }

            $current_dir = $root_path;
        }

        $vendor_dir = CliUtils::getVendorDir($current_dir);

        // capture environment before registering autoloader (it may destroy it)
        IssueBuffer::captureServer($_SERVER);

        $include_collector = new IncludeCollector();
        $autoloaders = $include_collector->runAndCollect(
            // we ignore the FQN because of a hack in scoper.inc that needs full path
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
            /** @return list<ClassLoader> */
            static fn(): array =>
                CliUtils::requireAutoloaders($current_dir, isset($options['r']), $vendor_dir),
        );

        // If Xdebug is enabled, restart without it
        (new XdebugHandler('PSALTER'))->check();

        $path_to_config = CliUtils::getPathToConfig($options);

        // `--move=X` is the same as `--move X`.
        $args = [];
        $expects_destination = false;

        foreach (CliUtils::getArguments() as $arg) {
            $separator = strpos($arg, '=');

            if ($separator !== false) {
                $option = substr($arg, 0, $separator);

                if (in_array($option, ['--move', '--into', '--rename', '--to'], true)) {
                    $value_start = $separator + 1;
                    if (($option === '--into' || $option === '--to') && substr($arg, $value_start, 1) === '\\') {
                        ++$value_start;
                    }

                    $args[] = $option;
                    $args[] = substr($arg, $value_start);
                    $expects_destination = false;
                    continue;
                }
            }

            $args[] = $expects_destination && str_starts_with($arg, '\\') ? substr($arg, 1) : $arg;
            $expects_destination = $arg === '--into' || $arg === '--to';
        }

        $operation = null;
        $last_arg = null;

        $to_refactor = [];

        $expects_a_value = static fn(string $operation): string
            => ($operation === 'move_into' ? '--into' : '--to') . ' expects a value' . PHP_EOL;

        foreach ($args as $arg) {
            if (($operation === 'move_into' || $operation === 'rename_to') && str_starts_with($arg, '-')) {
                fwrite(STDERR, $expects_a_value($operation));
                exit(1);
            }

            if ($arg === '--move') {
                $operation = 'move';
                continue;
            }

            if ($arg === '--into') {
                if ($operation !== 'move' || !$last_arg) {
                    fwrite(STDERR, '--into is not expected here' . PHP_EOL);
                    exit(1);
                }

                $operation = 'move_into';
                continue;
            }

            if ($arg === '--rename') {
                $operation = 'rename';
                continue;
            }

            if ($arg === '--to') {
                if ($operation !== 'rename' || !$last_arg) {
                    fwrite(STDERR, '--to is not expected here' . PHP_EOL);
                    exit(1);
                }

                $operation = 'rename_to';

                continue;
            }

            if (str_starts_with($arg, '-')) {
                $operation = null;
                continue;
            }

            if ($operation === 'move_into' || $operation === 'rename_to') {
                if (!$last_arg) {
                    fwrite(STDERR, 'Expecting a previous argument' . PHP_EOL);
                    exit(1);
                }

                if (trim($arg) === '') {
                    fwrite(STDERR, $expects_a_value($operation));
                    exit(1);
                }

                if ($operation === 'move_into') {
                    $last_arg_parts = preg_split('/, ?/', $last_arg);
                    if ($last_arg_parts === false) {
                        throw new AssertionError(preg_last_error_msg());
                    }

                    foreach ($last_arg_parts as $listed_source) {
                        $last_arg_part = self::stripLeadingBackslash($listed_source);
                        if (strpos($last_arg_part, '::')) {
                            [, $identifier_name] = explode('::', $last_arg_part);
                            $destination = $arg . '::' . $identifier_name;
                        } else {
                            $namespace_parts = explode('\\', $last_arg_part);
                            $class_name = end($namespace_parts);
                            $destination = $arg . '\\' . $class_name;
                        }

                        self::exitOnShapeError($last_arg_part, $destination);
                        $to_refactor[$last_arg_part] = $destination;
                    }
                } else {
                    $source = self::stripLeadingBackslash($last_arg);
                    self::exitOnShapeError($source, $arg);
                    $to_refactor[$source] = $arg;
                }

                $last_arg = null;
                $operation = null;
                continue;
            }

            if ($operation === 'move' || $operation === 'rename') {
                $last_arg = $arg;

                continue;
            }

            fwrite(STDERR, 'Unexpected argument "' . $arg . '"' . PHP_EOL);
            exit(1);
        }

        if ($operation === 'move_into' || $operation === 'rename_to') {
            fwrite(STDERR, $expects_a_value($operation));
            exit(1);
        }

        if (!$to_refactor) {
            fwrite(STDERR, 'No --move or --rename arguments supplied' . PHP_EOL);
            exit(1);
        }

        $config = CliUtils::initializeConfig(
            $path_to_config,
            $current_dir,
            Report::TYPE_CONSOLE,
            $autoloaders,
        );

        if (isset($options['no-cache'])) {
            $config->cache_directory = null;
        }

        $config->setIncludeCollector($include_collector);

        if ($config->resolve_from_config_file) {
            $current_dir = $config->base_dir;
            chdir($current_dir);
        }

        $in_ci = CliUtils::runningInCI();

        $threads = Psalm::getThreads($options, $config, false);
        $scanThreads = Psalm::getThreads($options, $config, true);

        if ($config->cache_directory === null) {
            $providers = new Providers(
                new FileProvider(),
            );
        } else {
            $providers = new Providers(
                new FileProvider(),
                null,
                new FileStorageCacheProvider($config, Composer::getLockFile($current_dir)),
                new ClassLikeStorageCacheProvider($config, Composer::getLockFile($current_dir)),
                null,
                new ProjectCacheProvider(),
            );
        }

        $debug = array_key_exists('debug', $options) || array_key_exists('debug-by-line', $options);
        // CI takes precedence over AI detection, matching Psalm.php / Psalter.php.
        $no_progress = isset($options['no-progress'])
            || (!$in_ci && CliUtils::runningUnderAiAgent());
        if ($debug) {
            $progress = new DebugProgress();
        } elseif ($no_progress) {
            $progress = new VoidProgress();
        } else {
            $progress = new DefaultProgress();
        }

        if (array_key_exists('debug-emitted-issues', $options)) {
            $config->debug_emitted_issues = true;
        }

        $report_options = new ReportOptions();
        $report_options->use_color = CliUtils::useColor($options, true);

        $project_analyzer = new ProjectAnalyzer(
            $config,
            $providers,
            $report_options,
            [],
            $threads,
            $scanThreads,
            $progress,
        );

        if (array_key_exists('debug-by-line', $options)) {
            $project_analyzer->debug_lines = true;
        }

        $project_analyzer->refactorCodeAfterCompletion($to_refactor);

        $start_time = microtime(true);

        try {
            $project_analyzer->check($current_dir);
        } catch (RefactorException $e) {
            fwrite(STDERR, $e->getMessage() . PHP_EOL);
            exit(1);
        }

        IssueBuffer::finish($project_analyzer, false, $start_time);
    }

    /**
     * @psalm-pure
     */
    private static function stripLeadingBackslash(string $name): string
    {
        return str_starts_with($name, '\\') ? substr($name, 1) : $name;
    }

    private static function exitOnShapeError(string $source, string $destination): void
    {
        $shape_error = self::getShapeError($source, $destination);
        if ($shape_error !== null) {
            fwrite(STDERR, $shape_error . PHP_EOL);
            exit(1);
        }
    }

    /**
     * Checks that a refactor destination has the same shape as its source, before any scanning.
     *
     * @psalm-pure
     */
    private static function getShapeError(string $source, string $destination): ?string
    {
        $class = '/^' . self::CLASS_NAME . '$/D';
        $member = '/^' . self::IDENTIFIER . '$/D';
        $property = '/^\\$' . self::IDENTIFIER . '$/D';

        if (str_ends_with($source, '*')) {
            $prefix = '/^(?:' . self::IDENTIFIER . '\\\\)*(?:' . self::IDENTIFIER . ')?$/D';
            $source_prefix = substr($source, 0, -1);
            if ($source_prefix === '' || !preg_match($prefix, $source_prefix)) {
                return 'Invalid refactor source ' . $source;
            }

            $destination_prefix = substr($destination, 0, -1);
            if (!str_ends_with($destination, '*')
                || $destination_prefix === ''
                || !preg_match($prefix, $destination_prefix)
            ) {
                return 'Destination for ' . $source . ' must be a wildcard like Foo\\*, got ' . $destination;
            }

            return null;
        }

        $source_parts = explode('::', $source);
        $destination_parts = explode('::', $destination);

        if (count($source_parts) === 1) {
            if (!preg_match($class, $source)) {
                return 'Invalid refactor source ' . $source;
            }

            if (!preg_match($class, $destination)) {
                return 'Destination for ' . $source . ' must be a class name like Foo\\Bar, got ' . $destination;
            }

            if (str_contains($source, '\\') && !str_contains($destination, '\\')) {
                return 'Renaming a namespaced class into the global namespace is not supported';
            }

            return null;
        }

        if (count($source_parts) !== 2) {
            return 'Invalid refactor source ' . $source;
        }

        [$source_class, $source_member] = $source_parts;
        $is_property = str_starts_with($source_member, '$');
        $member_pattern = $is_property ? $property : $member;

        if (!preg_match($class, $source_class) || !preg_match($member_pattern, $source_member)) {
            return 'Invalid refactor source ' . $source;
        }

        if (count($destination_parts) !== 2
            || !preg_match($class, $destination_parts[0])
            || !preg_match($member_pattern, $destination_parts[1])
        ) {
            return 'Destination for ' . $source . ' must be of the form '
                . ($is_property ? 'Foo\\Bar::$baz' : 'Foo\\Bar::baz') . ', got ' . $destination;
        }

        return null;
    }
}
