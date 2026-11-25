<?php

declare(strict_types=1);

namespace Psalm\Internal\Cli;

use Phar;
use Psalm\Config;
use Psalm\Internal\CliUtils;
use Psalm\Internal\ErrorHandler;
use Psalm\Internal\IncludeCollector;
use Psalm\Internal\Mcp\McpServer;
use Psalm\Internal\Mcp\Session;
use Psalm\Report;

use function array_key_exists;
use function array_slice;
use function chdir;
use function dirname;
use function extension_loaded;
use function fwrite;
use function gc_disable;
use function is_array;
use function is_string;
use function setlocale;

use const LC_CTYPE;
use const PHP_BINARY;
use const PHP_EOL;
use const STDERR;
use const STDIN;
use const STDOUT;

// phpcs:disable PSR1.Files.SideEffects

require_once __DIR__ . '/../ErrorHandler.php';
require_once __DIR__ . '/../CliUtils.php';
require_once __DIR__ . '/../Composer.php';
require_once __DIR__ . '/../IncludeCollector.php';

/**
 * @internal
 */
final class Mcp
{
    /**
     * @param array<int,string> $argv
     */
    public static function run(array $argv): void
    {
        CliUtils::checkRuntimeRequirements();
        gc_disable();
        ErrorHandler::install($argv);

        $valid_short_options = ['h', 'v', 'c:', 'r:'];

        $valid_long_options = [
            'config:',
            'help',
            'ignore-baseline',
            'php-version:',
            'root:',
            'threads:',
            'use-baseline:',
            'use-ini-defaults',
            'version',
        ];

        $args = array_slice($argv, 1);

        CliUtils::rejectUnrecognisedOptions($args, $valid_short_options, $valid_long_options, ['--mcp']);

        $options = CliUtils::getOptions($args, $valid_short_options, $valid_long_options);

        // stdout carries the protocol, so PHP's own errors go to stderr
        CliUtils::setMemoryLimit($options);

        CliUtils::syncShortOptions($options, [
            'help' => 'h',
            'version' => 'v',
            'config' => 'c',
            'root' => 'r',
        ]);

        if (isset($options['c']) && is_array($options['c'])) {
            fwrite(STDERR, 'Too many config files provided' . PHP_EOL);
            exit(1);
        }

        if (array_key_exists('h', $options)) {
            echo <<<HELP
            Usage:
                psalm-mcp [options]

            Runs a Model Context Protocol server on stdin/stdout that gives AI agents Psalm's analysis:
            the issues of files or of the whole project, the type of the code at a position, definitions,
            references, and the documentation of issue types. It keeps the codebase in memory like the
            language server, and picks up the files an agent changes on disk before every tool call.

            Options:
                -h, --help
                    Display this help message

                -v, --version
                    Display the Psalm version

                -c, --config=psalm.xml
                    Path to a psalm.xml configuration file. Run psalm --init to create one.

                -r, --root
                    If running Psalm globally you'll need to specify a project root. Defaults to cwd

                --use-baseline=PATH
                    Use a baseline other than the one in the config

                --ignore-baseline
                    Report the issues of the baseline too

                --php-version=PHP_VERSION
                    The PHP version to analyse the code for, as in psalm --php-version

                --threads=INT
                    The number of threads the whole-project analysis uses. Defaults to the CPU count

                --use-ini-defaults
                    Use PHP-provided ini defaults for memory and error display

            HELP;

            exit;
        }

        $current_dir = CliUtils::getCurrentDir($options);

        $vendor_dir = CliUtils::getVendorDir($current_dir);

        $include_collector = new IncludeCollector();

        $autoloaders = CliUtils::loadAutoloaders(
            $include_collector,
            $current_dir,
            isset($options['r']),
            $vendor_dir,
            !array_key_exists('v', $options),
        );

        if (array_key_exists('v', $options)) {
            echo 'Psalm ' . PSALM_VERSION . PHP_EOL;
            exit;
        }

        CliUtils::createRestarter('PSALM')->check();

        setlocale(LC_CTYPE, 'C');

        $path_to_config = CliUtils::getPathToConfig($options);

        if ($path_to_config === null && Config::locateConfigFile($current_dir) === null) {
            // the agent can't read a server that fails to start, so the tools say what to do
            (new McpServer(null, 'No psalm.xml was found in ' . $current_dir . ' or above it. Create one with'
                . ' `vendor/bin/psalm --init`, then restart this MCP server.'))->run(STDIN, STDOUT);
            exit;
        }

        $config = CliUtils::initializeConfig(
            $path_to_config,
            $current_dir,
            Report::TYPE_CONSOLE,
            $autoloaders,
        );
        $config->setIncludeCollector($include_collector);

        if ($config->resolve_from_config_file) {
            $current_dir = $config->base_dir;
            chdir($current_dir);
        }

        $session = Session::create(
            $config,
            $current_dir,
            $options,
            self::getPsalmCommand($options, $path_to_config, $current_dir),
        );

        (new McpServer($session, null))->run(STDIN, STDOUT);
    }

    /**
     * The `psalm` run that checks the whole project, with the options of this server that apply to it. They are the
     * operator's options, passed on to the same tool, and proc_open() passes them as arguments, not to a shell.
     *
     * @param array<string,string|false|list<string|false>> $options
     * @return list<string>
     * @psalm-taint-escape shell
     */
    private static function getPsalmCommand(array $options, ?string $path_to_config, string $current_dir): array
    {
        $phar = extension_loaded('phar') ? Phar::running(false) : '';

        $command = [
            PHP_BINARY,
            $phar !== '' ? $phar : dirname(__DIR__, 4) . '/psalm',
            '--no-progress',
            '--monochrome',
            '--output-format=console',
            '--root=' . $current_dir,
        ];

        if ($path_to_config !== null) {
            $command[] = '--config=' . $path_to_config;
        }

        foreach (['use-baseline', 'php-version', 'threads'] as $option) {
            if (isset($options[$option]) && is_string($options[$option])) {
                $command[] = '--' . $option . '=' . $options[$option];
            }
        }

        if (isset($options['ignore-baseline'])) {
            $command[] = '--ignore-baseline';
        }

        return $command;
    }
}
