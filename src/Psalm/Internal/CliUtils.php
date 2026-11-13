<?php

declare(strict_types=1);

namespace Psalm\Internal;

use Composer\Autoload\ClassLoader;
use JsonException;
use Phar;
use Psalm\Config;
use Psalm\Config\Creator;
use Psalm\Exception\ConfigCreationException;
use Psalm\Exception\ConfigException;
use Psalm\Exception\ConfigNotFoundException;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Cli\Psalm;
use Psalm\Internal\Cli\Psalter;
use Psalm\Report;
use RuntimeException;
use UnexpectedValueException;

use function array_filter;
use function array_key_exists;
use function array_slice;
use function assert;
use function count;
use function define;
use function dirname;
use function extension_loaded;
use function fgets;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function fwrite;
use function getenv;
use function implode;
use function in_array;
use function ini_set;
use function is_array;
use function is_dir;
use function is_scalar;
use function is_string;
use function json_decode;
use function preg_last_error_msg;
use function preg_replace;
use function preg_split;
use function realpath;
use function str_ends_with;
use function str_starts_with;
use function strcspn;
use function stream_get_meta_data;
use function stream_isatty;
use function stream_set_blocking;
use function strlen;
use function strpos;
use function strtolower;
use function substr;
use function substr_replace;
use function trim;

use const DIRECTORY_SEPARATOR;
use const JSON_THROW_ON_ERROR;
use const PHP_EOL;
use const PHP_VERSION;
use const PHP_VERSION_ID;
use const STDERR;
use const STDIN;
use const STDOUT;

/**
 * @internal
 */
final class CliUtils
{
    /** @return list<ClassLoader> */
    public static function requireAutoloaders(
        string $current_dir,
        bool $has_explicit_root,
        string $vendor_dir,
        bool $project_autoloader_required = true,
    ): array {
        $autoload_roots = [$current_dir];

        $psalm_dir = dirname(__DIR__, 3);

        $in_phar = Phar::running() || strpos(__NAMESPACE__, 'HumbugBox');

        if ($in_phar) {
            require_once __DIR__ . '/../../../vendor/autoload.php';

            // hack required for JsonMapper
            require_once __DIR__ . '/../../../vendor/netresearch/jsonmapper/src/JsonMapper.php';
            require_once __DIR__ . '/../../../vendor/netresearch/jsonmapper/src/JsonMapper/Exception.php';
        }

        if (!$in_phar && realpath($psalm_dir) !== realpath($current_dir)) {
            $autoload_roots[] = $psalm_dir;
        }

        $autoload_files = [];

        foreach ($autoload_roots as $autoload_root) {
            $has_autoloader = false;

            $nested_autoload_file = dirname($autoload_root, 2) . DIRECTORY_SEPARATOR . 'autoload.php';

            // note: don't realpath $nested_autoload_file, or phar version will fail
            if (file_exists($nested_autoload_file)) {
                if (!in_array($nested_autoload_file, $autoload_files, false)) {
                    $autoload_files[] = $nested_autoload_file;
                }
                $has_autoloader = true;
            }

            $vendor_autoload_file =
                $autoload_root . DIRECTORY_SEPARATOR . $vendor_dir . DIRECTORY_SEPARATOR . 'autoload.php';

            // note: don't realpath $vendor_autoload_file, or phar version will fail
            if (file_exists($vendor_autoload_file)) {
                if (!in_array($vendor_autoload_file, $autoload_files, false)) {
                    $autoload_files[] = $vendor_autoload_file;
                }
                $has_autoloader = true;
            }

            $composer_json_file = Composer::getJsonFilePath($autoload_root);
            if ($project_autoloader_required && !$has_autoloader && file_exists($composer_json_file)) {
                $error_message = 'Could not find any composer autoloaders in ' . $autoload_root;

                if (!$has_explicit_root) {
                    $error_message .= PHP_EOL . 'Add a --root=[your/project/directory] flag '
                        . 'to specify a particular project to run Psalm on.';
                }

                fwrite(STDERR, $error_message . PHP_EOL);
                exit(1);
            }
        }

        $autoloaders = [];
        foreach ($autoload_files as $file) {
            /**
             * @psalm-suppress UnresolvableInclude
             * @var mixed
             */
            $autoloader = ErrorHandler::runWithExceptionsSuppressed(static fn(): mixed => require_once $file);

            if ($autoloader instanceof ClassLoader
            ) {
                $autoloaders []= $autoloader;
            }
        }

        if (!$autoloaders && !$in_phar) {
            if (!$autoload_files) {
                fwrite(STDERR, 'Failed to find a valid Composer autoloader' . "\n");
            } else {
                fwrite(
                    STDERR,
                    'Failed to find a valid Composer autoloader in ' . implode(', ', $autoload_files) . "\n",
                );
            }

            fwrite(
                STDERR,
                'Please make sure you’ve run `composer install` in the current directory before using Psalm.' . "\n",
            );
            exit(1);
        }

        define('PSALM_VERSION', VersionUtils::getPsalmVersion());
        define('PHP_PARSER_VERSION', VersionUtils::getPhpParserVersion());

        return $autoloaders;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public static function getVendorDir(string $current_dir): string
    {
        $composer_json_path = Composer::getJsonFilePath($current_dir);

        if (!file_exists($composer_json_path)) {
            return 'vendor';
        }
        try {
            $composer_file_contents = file_get_contents($composer_json_path);
            assert($composer_file_contents !== false);
            $composer_json = json_decode($composer_file_contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            fwrite(
                STDERR,
                'Invalid composer.json at ' . $composer_json_path . "\n" . $e->getMessage() . "\n",
            );
            exit(1);
        }

        if (!$composer_json) {
            fwrite(
                STDERR,
                'Invalid composer.json at ' . $composer_json_path . "\n",
            );
            exit(1);
        }

        if (is_array($composer_json)
            && isset($composer_json['config'])
            && is_array($composer_json['config'])
            && isset($composer_json['config']['vendor-dir'])
            && is_string($composer_json['config']['vendor-dir'])
        ) {
            return $composer_json['config']['vendor-dir'];
        }

        return 'vendor';
    }

    /**
     * @return list<string>
     */
    public static function getRawCliArguments(): array
    {
        global $argv;

        if (!$argv) {
            return [];
        }

        return self::chosenByOperator(array_slice($argv, 1));
    }

    /**
     * Like getopt(), but an option may come after a path, as in GNU getopt: getopt() stops at the
     * first path and silently drops every option after it. Everything after "--" is a path.
     *
     * @param list<string> $args the arguments, without the script name
     * @param list<string> $short_options in the format of getopt(), one option per item, e.g. "c:"
     * @param list<string> $long_options in the format of getopt()
     * @return array<string, false|list<false|string>|string> options that aren't in $short_options and
     *      $long_options are left out: see rejectUnrecognisedOptions()
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    public static function getOptions(array $args, array $short_options, array $long_options): array
    {
        return self::chosenByOperator(self::parseOptions($args, $short_options, $long_options)[0]);
    }

    /**
     * Exits with code 1 when $args hold an option that isn't in $short_options or $long_options
     *
     * @param list<string> $args the arguments, without the script name
     * @param list<string> $short_options
     * @param list<string> $long_options
     * @param list<string> $ignored_options options of the psalm command that forwarded the call, like "--alter"
     */
    public static function rejectUnrecognisedOptions(
        array $args,
        array $short_options,
        array $long_options,
        array $ignored_options = [],
    ): void {
        foreach (self::parseOptions($args, $short_options, $long_options)[1] as $option) {
            if (in_array($option, $ignored_options, true)) {
                continue;
            }

            fwrite(
                STDERR,
                'Unrecognised argument "' . $option . '"' . PHP_EOL
                . 'Type --help to see a list of supported arguments' . PHP_EOL,
            );
            exit(1);
        }
    }

    /**
     * @param list<string> $args
     * @param list<string> $short_options
     * @param list<string> $long_options
     * @return array{array<string, false|list<false|string>|string>, list<string>} the options like getopt()
     *      returns them, and the options that aren't in $short_options and $long_options, like "-z"
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private static function parseOptions(array $args, array $short_options, array $long_options): array
    {
        $options = [];
        $unrecognised = [];

        for ($i = 0, $count = count($args); $i < $count; ++$i) {
            $arg = $args[$i];

            if ($arg === '--') {
                break;
            }

            if ($arg === '' || $arg === '-' || $arg[0] !== '-') {
                continue;
            }

            if ($arg[1] === '-') {
                $name = substr($arg, 2);
                $value = null;
                $equals_position = strpos($name, '=');

                if ($equals_position !== false) {
                    $value = substr($name, $equals_position + 1);
                    $name = substr($name, 0, $equals_position);
                }

                $arity = self::getOptionArity($name, $long_options);

                if ($arity === null) {
                    $unrecognised[] = '--' . $name;
                } elseif ($value === '') {
                    // getopt() drops an option given as --name=
                    continue;
                } elseif ($arity === '') {
                    self::addOption($options, $name, false);
                } elseif ($arity === '::') {
                    self::addOption($options, $name, $value ?? false);
                } elseif ($value !== null) {
                    self::addOption($options, $name, $value);
                } elseif (isset($args[$i + 1])) {
                    self::addOption($options, $name, $args[++$i]);
                }

                continue;
            }

            // a group of short options, like -mh, where only the last one may take a value
            for ($j = 1, $length = strlen($arg); $j < $length; ++$j) {
                $name = $arg[$j];
                $arity = self::getOptionArity($name, $short_options);

                if ($arity === null) {
                    $unrecognised[] = '-' . $name;
                    continue;
                }

                if ($arity === '') {
                    self::addOption($options, $name, false);
                    continue;
                }

                $value = substr($arg, $j + 1);

                if ($value !== '') {
                    self::addOption($options, $name, $value[0] === '=' ? substr($value, 1) : $value);
                } elseif ($arity === ':' && isset($args[$i + 1])) {
                    self::addOption($options, $name, $args[++$i]);
                }

                break;
            }
        }

        return [$options, $unrecognised];
    }

    /**
     * @param list<string> $definitions options in the format of getopt()
     * @return ''|':'|'::'|null whether the option takes no value, one, or an optional one; null if it isn't known
     * @psalm-pure
     */
    private static function getOptionArity(string $name, array $definitions): ?string
    {
        if (in_array($name, $definitions, true)) {
            return '';
        }

        if (in_array($name . ':', $definitions, true)) {
            return ':';
        }

        return in_array($name . '::', $definitions, true) ? '::' : null;
    }

    /**
     * @param array<string, false|list<false|string>|string> $options
     * @param-out array<string, false|list<false|string>|string> $options
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private static function addOption(array &$options, string $name, string|false $value): void
    {
        if (!array_key_exists($name, $options)) {
            $options[$name] = $value;
            return;
        }

        $previous = $options[$name];
        $options[$name] = is_array($previous) ? [...$previous, $value] : [$previous, $value];
    }

    /**
     * Marks the command-line arguments or options Psalm was run with as chosen by the person running it.
     *
     * They name the files and directories Psalm reads and writes, the code it scans and reflects on,
     * the config, plugins and autoloaders it loads, the Shepherd and language client addresses it
     * connects to, and the symbols psalm-refactor moves; Psalm prints them back to that person. Psalm
     * is meant to act on them: they aren't input from a third party, like the paths read from STDIN
     * (see getPathsToCheckFromStdin()). The other kinds of taint stay, so that an argument still has
     * to be escaped for a shell, for example.
     *
     * @template T of array|false
     * @param T $arguments
     * @return T
     * @psalm-pure
     * @psalm-taint-escape file
     * @psalm-taint-escape ssrf
     * @psalm-taint-escape include
     * @psalm-taint-escape callable
     * @psalm-taint-escape html
     * @psalm-taint-escape has_quotes
     * @psalm-taint-escape url_component
     * @psalm-taint-escape url_path
     */
    public static function chosenByOperator(array|false $arguments): array|false
    {
        return $arguments;
    }

    /**
     * @return list<string>
     */
    public static function getArguments(): array
    {
        $argv = self::getRawCliArguments();
        $filtered_input_paths = [];

        for ($i = 0, $iMax = count($argv); $i < $iMax; ++$i) {
            $input_path = $argv[$i];

            if (realpath($input_path) !== false) {
                continue;
            }

            if ($input_path[0] === '-' && strlen($input_path) === 2) {
                if ($input_path[1] === 'c' || $input_path[1] === 'f' || $input_path[1] === 'r') {
                    ++$i;
                }
                continue;
            }

            if ($input_path[0] === '-' && $input_path[2] === '=') {
                continue;
            }

            $filtered_input_paths[] = $input_path;
        }

        return $filtered_input_paths;
    }

    /**
     * @return list<string>|null
     */
    public static function getPathsToCheck(string|array|false|null $f_paths): ?array
    {
        $paths_to_check = [];

        if ($f_paths) {
            $input_paths = is_array($f_paths) ? $f_paths : [$f_paths];
        } else {
            $input_paths = self::getRawCliArguments();
            if (!$input_paths) {
                return null;
            }
        }

        $filtered_input_paths = [];
        $terminated_paths = [];

        for ($i = 0, $iMax = count($input_paths); $i < $iMax; ++$i) {
            /** @var string */
            $input_path = $input_paths[$i];

            if ($input_path === '--') {
                // everything after the terminator is a path, even when it starts with "-"
                for ($j = $i + 1; $j < $iMax; ++$j) {
                    /** @var string */
                    $terminated_path = $input_paths[$j];
                    $terminated_paths[] = $terminated_path;
                }

                break;
            }

            if ($input_path[0] === '-' && strlen($input_path) === 2) {
                if ($input_path[1] === 'c' || $input_path[1] === 'f' || $input_path[1] === 'r') {
                    ++$i;
                }
                continue;
            }

            if ($input_path[0] === '-' && $input_path[2] === '=') {
                continue;
            }

            if ($input_path[0] === '-' && $input_path[1] !== '-' && strlen($input_path) > 2) {
                // a group of short options like -mh, or -cpsalm.xml with the value attached;
                // the value of the option that ends a group follows it, as in `-mc psalm.xml`
                if (strcspn($input_path, 'cfr', 1) + 1 === strlen($input_path) - 1) {
                    ++$i;
                }
                continue;
            }

            if (str_starts_with($input_path, '--') && strlen($input_path) > 2) {
                // ignore --config psalm.xml
                // ignore common phpunit args that accept a class instead of a path, as this can cause issues on Windows
                $ignored_arguments = ['config', 'printer', 'root'];

                // the value of an option that requires one follows it, as in `--threads 4`
                foreach ([...Psalm::LONG_OPTIONS, ...Psalter::LONG_OPTIONS] as $long_option) {
                    if (str_ends_with($long_option, ':') && !str_ends_with($long_option, '::')) {
                        $ignored_arguments[] = substr($long_option, 0, -1);
                    }
                }

                if (in_array(substr($input_path, 2), $ignored_arguments, true)) {
                    ++$i;
                }
                continue;
            }

            $filtered_input_paths[] = $input_path;
        }

        if ($filtered_input_paths === ['-']) {
            $stdin_paths = self::getPathsToCheckFromStdin();

            if ($stdin_paths !== null) {
                $filtered_input_paths = $stdin_paths;
            }
        }

        $filtered_input_paths = [...$filtered_input_paths, ...$terminated_paths];

        foreach ($filtered_input_paths as $path_to_check) {
            if ($path_to_check[0] === '-' && !in_array($path_to_check, $terminated_paths, true)) {
                fwrite(STDERR, 'Invalid usage, expecting psalm [options] [file...]' . PHP_EOL);
                exit(1);
            }

            if (!file_exists($path_to_check)) {
                fwrite(STDERR, 'Cannot locate ' . $path_to_check . PHP_EOL);
                exit(1);
            }

            $path_to_check = realpath($path_to_check);

            if (!$path_to_check) {
                fwrite(STDERR, 'Error getting realpath for file' . PHP_EOL);
                exit(1);
            }

            $paths_to_check[] = $path_to_check;
        }

        if (!$paths_to_check) {
            $paths_to_check = null;
        }

        return $paths_to_check;
    }

    /**
     * Reads a whitespace-separated list of paths to analyse from STDIN (used with `psalm -`).
     *
     * The paths are supplied by the (trusted) user invoking the CLI, so the input taint that
     * the STDIN stream carries is escaped here.
     *
     * @return non-empty-list<string>|null
     * @psalm-taint-escape input
     */
    private static function getPathsToCheckFromStdin(): ?array
    {
        $meta = stream_get_meta_data(STDIN);
        stream_set_blocking(STDIN, false);

        try {
            $stdin = fgets(STDIN);

            if ($stdin === false) {
                return null;
            }

            $stdin = trim($stdin);

            if ($stdin === '') {
                return null;
            }

            $paths = preg_split('/\s+/', $stdin);

            if ($paths === false) {
                throw new RuntimeException('Invalid paths: ' . preg_last_error_msg());
            }

            return $paths;
        } finally {
            stream_set_blocking(STDIN, $meta['blocked']);
        }
    }


    /** @param list<ClassLoader> $autoloaders */
    public static function initializeConfig(
        ?string $path_to_config,
        string $current_dir,
        string $output_format,
        array $autoloaders,
        bool $create_if_non_existent = false,
    ): Config {
        Config::setAddCommandLinePaths(true);

        try {
            if ($path_to_config) {
                $config = Config::loadFromXMLFile($path_to_config, $current_dir);
            } else {
                try {
                    $config = Config::getConfigForPath($current_dir, $current_dir);
                } catch (ConfigNotFoundException $e) {
                    if (!$create_if_non_existent) {
                        if (in_array($output_format, [Report::TYPE_CONSOLE, Report::TYPE_PHP_STORM])) {
                            fwrite(
                                STDERR,
                                'Could not locate a config XML file in path ' . $current_dir
                                    . '. Have you run \'psalm --init\' ?' . PHP_EOL,
                            );
                            exit(1);
                        }

                        throw $e;
                    }

                    $config = Creator::createBareConfig(
                        $current_dir,
                        null,
                        self::getVendorDir($current_dir),
                    );
                }
            }
        } catch (ConfigException | ConfigCreationException $e) {
            fwrite(
                STDERR,
                $e->getMessage() . PHP_EOL,
            );
            exit(1);
        } finally {
            Config::setAddCommandLinePaths(false);
        }

        $config->setComposerClassLoader($autoloaders);

        return $config;
    }

    public static function updateConfigFile(Config $config, string $config_file_path, string $baseline_path): void
    {
        if ($config->error_baseline === $baseline_path) {
            return;
        }

        $config_file = $config_file_path;

        if (is_dir($config_file_path)) {
            $config_file = Config::locateConfigFile($config_file_path);
        }

        if (!$config_file) {
            fwrite(STDERR, "Add errorBaseline=\"{$baseline_path}\" to the config to use it" . PHP_EOL);

            return;
        }

        $config_file_contents = file_get_contents($config_file);
        assert($config_file_contents !== false);

        if ($config->error_baseline) {
            $amended_config_file_contents = (string) preg_replace(
                '/errorBaseline=".*?"/',
                "errorBaseline=\"{$baseline_path}\"",
                $config_file_contents,
            );
        } else {
            $end_psalm_open_tag = strpos($config_file_contents, '>', (int)strpos($config_file_contents, '<psalm'));

            if (!$end_psalm_open_tag) {
                fwrite(STDERR, "Add errorBaseline=\"{$baseline_path}\" to the config to use it" . PHP_EOL);
                return;
            }

            if ($config_file_contents[$end_psalm_open_tag - 1] === "\n") {
                $amended_config_file_contents = substr_replace(
                    $config_file_contents,
                    "    errorBaseline=\"{$baseline_path}\"\n>",
                    $end_psalm_open_tag,
                    1,
                );
            } else {
                $amended_config_file_contents = substr_replace(
                    $config_file_contents,
                    " errorBaseline=\"{$baseline_path}\">",
                    $end_psalm_open_tag,
                    1,
                );
            }
        }

        file_put_contents($config_file, $amended_config_file_contents);
    }

    public static function getPathToConfig(array $options): ?string
    {
        $path_to_config = isset($options['c']) && is_string($options['c']) ? realpath($options['c']) : null;

        if ($path_to_config === false) {
            fwrite(STDERR, 'Could not resolve path to config ' . (string) ($options['c'] ?? '') . PHP_EOL);
            exit(1);
        }
        return $path_to_config;
    }

    /**
     * @param array<string,string|false|list<mixed>> $options
     * @throws ConfigException
     */
    public static function setMemoryLimit(array $options, string $display_error = 'stderr'): void
    {
        if (!array_key_exists('use-ini-defaults', $options)) {
            ini_set('display_errors', $display_error);
            ini_set('display_startup_errors', '1');

            $memoryLimit = '-1';

            if (array_key_exists('memory-limit', $options)) {
                $memoryLimit = $options['memory-limit'];

                if (!is_scalar($memoryLimit)) {
                    throw new ConfigException('Invalid memory limit specified.');
                }
            }

            ini_set('memory_limit', (string) $memoryLimit);
        }
    }

    public static function initPhpVersion(array $options, Config $config, ProjectAnalyzer $project_analyzer): void
    {
        $source = null;

        if (isset($options['php-version'])) {
            if (!is_string($options['php-version'])) {
                fwrite(STDERR, 'Expecting a version number in the format x.y' . PHP_EOL);
                exit(1);
            }
            $version = $options['php-version'];
            $source = 'cli';
        } elseif ($version = $config->getPhpVersionFromConfig()) {
            $source = 'config';
        } elseif ($version = $config->getPHPVersionFromComposerJson()) {
            $source = 'composer';
        }

        if ($version !== null && $source !== null) {
            try {
                $project_analyzer->setPhpVersion($version, $source);
            } catch (UnexpectedValueException $e) {
                fwrite(
                    STDERR,
                    $e->getMessage() . PHP_EOL,
                );
                exit(1);
            }
        }
    }

    public static function runningInCI(): bool
    {
        return isset($_SERVER['TRAVIS'])
            || isset($_SERVER['CIRCLECI'])
            || isset($_SERVER['APPVEYOR'])
            || isset($_SERVER['JENKINS_URL'])
            || isset($_SERVER['SCRUTINIZER'])
            || isset($_SERVER['GITLAB_CI'])
            || isset($_SERVER['CI'])
            || isset($_SERVER['GITHUB_WORKFLOW'])
            || isset($_SERVER['DRONE']);
    }

    /**
     * Detects whether Psalm is being invoked by an AI coding agent.
     *
     * When an agent is driving the shell, `\r`-based progress bars and ANSI
     * colors are pure token noise: the agent never renders them and just
     * feeds the raw bytes back into its language model.
     *
     * The detection list is deliberately vendor-specific. The `AGENT`
     * variable on its own is too generic to trust (it collides with shell
     * rc files and unrelated tooling), so we only match it when paired
     * with a known agent name.
     */
    public static function runningUnderAiAgent(): bool
    {
        // Vendor-specific presence markers. Each agent documents a variable
        // it exports into every spawned subprocess.
        $markers = [
            'CLAUDECODE',            // Claude Code (Anthropic)
            'CLAUDE_CODE',           // Claude Code (alternate)
            'CURSOR_AGENT',          // Cursor Composer
            'CURSOR_TRACE_ID',       // Cursor (older builds)
            'GEMINI_CLI',            // Gemini CLI (Google)
            'CODEX_SANDBOX',         // OpenAI Codex CLI (macOS sandbox marker)
            'CODEX_THREAD_ID',       // OpenAI Codex CLI (cross-platform)
            'AUGMENT_AGENT',         // Augment
            'CLINE_ACTIVE',          // Cline
            'OPENCODE_CLIENT',       // OpenCode (sst/opencode)
            'OPENCODE',              // OpenCode (alternate)
            'AMP_CURRENT_THREAD_ID', // Amp (Sourcegraph)
            'TRAE_AI_SHELL_ID',      // TRAE AI
            'COPILOT_CLI',           // GitHub Copilot CLI
            'ANTIGRAVITY_AGENT',     // Google Antigravity
            'PI_CODING_AGENT',       // Pi coding agent
            'REPL_ID',               // Replit
            'AI_AGENT',              // Vendor-neutral convention (@vercel/detect-agent).
        ];
        foreach ($markers as $var) {
            if (self::envVarLooksTruthy($var)) {
                return true;
            }
        }

        // `AGENT=<name>` convention proposed in agentsmd.org/issue#136. Used
        // by Goose. Value-gated so generic shell usage does not false-positive.
        $agent = getenv('AGENT');
        if (is_string($agent) && strtolower($agent) === 'goose') {
            return true;
        }

        // Devin runs its agent in a sandbox that always contains this marker.
        if (file_exists('/opt/.devin')) {
            return true;
        }

        return false;
    }

    private static function envVarLooksTruthy(string $name): bool
    {
        $value = getenv($name);
        return is_string($value)
            && $value !== ''
            && $value !== '0'
            && $value !== 'false';
    }

    /**
     * Returns whether the given stream is attached to an interactive terminal.
     *
     * @param resource $stream
     * @psalm-capabilities io
     */
    public static function streamIsInteractive($stream): bool
    {
        return stream_isatty($stream);
    }

    /**
     * Decides whether CLI output should be colored.
     *
     * Precedence: an explicit `-m` wins, then FORCE_COLOR (https://force-color.org/),
     * then NO_COLOR (https://no-color.org/), an AI agent or TERM=dumb, and finally,
     * when $require_interactive_stdout is set, a non-TTY stdout.
     *
     * Plain `psalm` does not require a TTY so that CI logs keep their colors.
     *
     * @param array<array-key, mixed> $options parsed command line options
     */
    public static function useColor(array $options, bool $require_interactive_stdout): bool
    {
        if (array_key_exists('m', $options)) {
            return false;
        }

        if (self::envVarIsNonEmpty('FORCE_COLOR')) {
            return true;
        }

        if (self::envVarIsNonEmpty('NO_COLOR')
            || self::runningUnderAiAgent()
            || getenv('TERM') === 'dumb'
        ) {
            return false;
        }

        return !$require_interactive_stdout || self::streamIsInteractive(STDOUT);
    }

    private static function envVarIsNonEmpty(string $name): bool
    {
        $value = getenv($name);
        return is_string($value) && $value !== '';
    }

    public static function checkRuntimeRequirements(): void
    {
        // the following list was taken from vendor/composer/platform_check.php
        // It includes both Psalm's requirements (from composer.json) and the
        // requirements of our dependencies `netresearch/jsonmapper` and
        // `phpdocumentor/reflection-docblock`. The latter is transitive
        // dependency of `felixfbecker/advanced-json-rpc`
        $required_extensions = [
            'dom',
            'filter',
            'json',
            'libxml',
            'pcre',
            'reflection',
            'simplexml',
            'spl',
            'tokenizer',
        ];
        $issues = [];

        $major_minor = PHP_VERSION_ID - (PHP_VERSION_ID % 100);
        foreach ([
            8_02_27 => '8.2.27',
            8_03_16 => '8.3.16',
            8_04_03 => '8.4.3',
            8_05_00 => '8.5.0',
            8_06_00 => '8.6.0',
        ] as $version => $txt) {
            $version_m = $version - ($version % 100);
            if ($version_m === $major_minor) {
                if (PHP_VERSION_ID < $version) {
                    $issues[] = 'Psalm requires a PHP version ">= ' . $txt . '".'
                        . ' You are running ' . PHP_VERSION . '.';
                }
                break;
            }
        }

        $missing_extensions = array_filter(
            $required_extensions,
            static fn(string $ext) => !extension_loaded($ext),
        );

        if ($missing_extensions) {
            $issues[] = 'Psalm requires the following PHP extensions to be installed: '
                        . implode(', ', $missing_extensions)
                        . '.';
        }

        if ($issues) {
            fwrite(
                STDERR,
                'Psalm has detected issues in your platform:' . PHP_EOL . PHP_EOL
                . implode(PHP_EOL, $issues)
                . PHP_EOL . PHP_EOL,
            );
            exit(1);
        }
    }
}
