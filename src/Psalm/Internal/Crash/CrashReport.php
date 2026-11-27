<?php

declare(strict_types=1);

namespace Psalm\Internal\Crash;

use Amp\Parallel\Worker\Task;
use Amp\Parallel\Worker\TaskFailureThrowable;
use Closure;
use Psalm\Config;
use Psalm\Exception\ConfigException;
use Psalm\Internal\CliUtils;
use Psalm\Internal\FileChangedDuringRunException;
use Psalm\Internal\Fork\TaskCrashedException;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Filesystem\Path;
use Throwable;

use function array_column;
use function array_map;
use function array_reverse;
use function count;
use function date;
use function defined;
use function dirname;
use function file_put_contents;
use function function_exists;
use function fwrite;
use function get_loaded_extensions;
use function getenv;
use function getmypid;
use function http_build_query;
use function implode;
use function is_a;
use function is_dir;
use function is_int;
use function is_string;
use function is_subclass_of;
use function ltrim;
use function mb_strcut;
use function mkdir;
use function php_uname;
use function phpversion;
use function preg_match;
use function preg_replace;
use function realpath;
use function rtrim;
use function simplexml_load_file;
use function sort;
use function str_contains;
use function str_starts_with;
use function strlen;
use function strpos;
use function strrpos;
use function strtolower;
use function strtr;
use function substr;
use function sys_get_temp_dir;

use const DIRECTORY_SEPARATOR;
use const PHP_EOL;
use const PHP_INT_MAX;
use const PHP_OS_FAMILY;
use const PHP_QUERY_RFC3986;
use const PHP_VERSION;
use const STDERR;

/**
 * The report of an exception that crashed Psalm
 *
 * @internal
 * @psalm-type Package = array{
 *     name: string,
 *     version: ?string,
 *     install_path: string,
 *     issues: ?string,
 *     source: ?string,
 *     plugin_class: ?string
 * }
 * @psalm-type Frame = array{file: ?string, line: int, call: string}
 * @psalm-type Exception = array{class: string, message: string, file: string, line: int, frames: list<Frame>}
 */
final class CrashReport
{
    private const PSALM_ISSUES = 'https://github.com/vimeo/psalm/issues/new';

    /** VS Code's cap: GitHub answers 414 to longer URLs */
    private const MAX_URL_LENGTH = 7_500;

    private const MAX_FRAMES = 10;

    /** Errors of the machine Psalm runs on, and what to do about them */
    private const ENVIRONMENT_ERRORS = [
        'No space left on device' => 'The disk is full: free some space and run Psalm again',
        'Too many open files' => 'Psalm has too many files open: raise the limit (ulimit -n) and run Psalm again',
        'Permission denied' => 'Psalm may not read or write a file: check the permissions of the file in the message',
        'Read-only file system' => 'Psalm can not write to a read-only file system: check the cache directory',
    ];

    private readonly CrashContext $context;

    /** @var non-empty-list<Exception> the exception that was thrown first, then the ones it wraps */
    private readonly array $chain;

    /** @var ?Package */
    private readonly ?array $plugin_package;

    /** Whether the plugin is only guessed from the frames of the trace */
    private readonly bool $plugin_guessed;

    /** The crash on a file that Analyzer and Pool report, with a report for the console */
    private readonly ?TaskCrashedException $task_crash;

    /**
     * @param list<Package> $packages
     */
    public function __construct(
        private readonly Throwable $throwable,
        private readonly ?Config $config,
        private readonly array $packages,
    ) {
        $this->context = CrashContext::of($throwable);
        $this->chain = self::describeChain($throwable);

        $task_crash = null;
        for ($e = $throwable; $e !== null && $task_crash === null; $e = $e->getPrevious()) {
            $task_crash = $e instanceof TaskCrashedException ? $e : null;
        }

        $this->task_crash = $task_crash;

        if ($this->context->handler !== null && str_starts_with($this->context->handler, 'Psalm\\Internal\\')) {
            // a hook of Psalm itself (the taint handlers of Config::initializePlugins()) threw: no plugin did
            $this->context->hook = $this->context->handler = null;
        }

        $handler_file = $this->context->handler === null ? null : self::getClassFile($this->context->handler);
        if ($this->context->handler !== null) {
            $this->plugin_package = $handler_file === null ? null : $this->findPackage($handler_file, false);
            $this->plugin_guessed = false;
        } else {
            $this->plugin_package = $this->guessPluginPackage();
            $this->plugin_guessed = $this->plugin_package !== null;
        }
    }

    /**
     * Prints the report of the crash and writes the full report to a file. A failure to make either does not hide
     * the crash.
     *
     * @param resource $output
     */
    public static function print(Throwable $throwable, string $command_line, $output = STDERR): void
    {
        try {
            $report = self::create($throwable);
            $summary = $report->getSummary();
        } catch (Throwable $failure) {
            fwrite(
                $output,
                PHP_EOL . 'Uncaught ' . $throwable->__toString() . PHP_EOL
                . 'Psalm could not report the crash: ' . $failure->getMessage() . PHP_EOL,
            );

            return;
        }

        fwrite($output, PHP_EOL . $summary . PHP_EOL);

        try {
            $path = $report->writeFile($command_line);
            if ($path !== null) {
                fwrite($output, 'Full report: ' . $path . PHP_EOL);
            }
        } catch (Throwable $failure) {
            fwrite($output, 'Psalm could not write the full report: ' . $failure->getMessage() . PHP_EOL);
        }

        fwrite($output, ($report->getHint() ?? $report->getReportLine()) . PHP_EOL);
    }

    /**
     * What the user has to do when the crash is not a bug, as in PHPStan's InternalError::shouldReportBug()
     *
     * @psalm-capabilities read-props
     */
    public function getHint(): ?string
    {
        $config_exception = false;
        foreach ($this->chain as $exception) {
            if (is_a($exception['class'], FileChangedDuringRunException::class, true)) {
                return $exception['message'];
            }

            foreach (self::ENVIRONMENT_ERRORS as $error => $hint) {
                if (str_contains($exception['message'], $error)) {
                    return $hint;
                }
            }

            $config_exception = $config_exception || is_a($exception['class'], ConfigException::class, true);
        }

        // a plugin that fails to load is wrapped in a ConfigException
        return $config_exception && !$this->isPluginCrash()
            ? 'Psalm could not load its configuration: fix what the message says and run Psalm again'
            : null;
    }

    /**
     * Where to report the crash, with a link that fills in the report
     */
    public function getReportLine(): string
    {
        $package = $this->plugin_package;
        if ($package === null && $this->context->handler !== null) {
            return 'Report it to the authors of the plugin';
        }

        try {
            $url = $package === null ? $this->getPsalmIssueUrl() : $this->getPluginIssueUrl($package);
        } catch (Throwable) {
            $url = $package === null ? self::PSALM_ISSUES : self::getTracker($package);
        }

        if ($package === null) {
            return 'Report the bug to Psalm: ' . $url;
        }

        return $url === null ? 'Report it to the authors of the plugin' : 'Report it to the plugin: ' . $url;
    }

    /**
     * A link to a new issue on Psalm's tracker, with the title and the body filled in
     */
    public function getPsalmIssueUrl(): string
    {
        return $this->getIssueUrl('vimeo/psalm', null);
    }

    /**
     * A link to the tracker of a plugin, filled in when it is on GitHub
     *
     * @param Package $package
     */
    private function getPluginIssueUrl(array $package): ?string
    {
        $repository = self::findGithubRepository($package['issues'] ?? $package['source']);

        return $repository === null ? self::getTracker($package) : $this->getIssueUrl($repository, $package);
    }

    /**
     * @param ?Package $package the plugin that crashed
     */
    private function getIssueUrl(string $repository, ?array $package): string
    {
        $context = $this->getContextLine();
        $crashed = $package === null
            ? 'Psalm crashed.' . "\n\n" . '- Plugins: ' . $this->describePlugins() . "\n"
            : 'Psalm crashed in the plugin.' . "\n\n"
                . '- Plugin: ' . $package['name'] . ' ' . ($package['version'] ?? '') . "\n"
                . ($this->context->hook === null
                    ? '- Hook: unknown, guessed from the trace' . "\n"
                    : '- Hook: ' . $this->context->hook . ', ' . (string) $this->context->handler . "\n");

        return $this->fitUrl(
            'https://github.com/' . $repository . '/issues/new',
            fn(int $max_frames, int $max_length): array => [
                'title' => $this->getIssueTitle(),
                'body' => $crashed
                    . '- Psalm: ' . (defined('PSALM_VERSION') ? self::getPsalmVersion() : 'unknown')
                    . ', PHP ' . PHP_VERSION . ', ' . self::describeOs() . "\n"
                    . ($context === null ? '' : '- Context: ' . $context . "\n")
                    . "\n```\n" . $this->renderChain(false, $max_length) . PHP_EOL . $this->renderFrames($max_frames)
                    . "\n```\n\n"
                    . 'Psalm wrote a full report to a crash-*.txt file: attach it if it helps.'
                    . ($package === null
                        ? ' If you can, add the code and the `psalm.xml` that make Psalm crash.'
                        : '')
                    . "\n",
            ],
        );
    }

    /**
     * The link with the fields, sanitized, and with fewer frames and shorter messages until it fits
     *
     * @param Closure(int, int): array<string, string> $fields the fields for a number of frames and a message length
     */
    private function fitUrl(string $base, Closure $fields): string
    {
        foreach ([[self::MAX_FRAMES, 2_000], [5, 1_000], [0, 500], [0, 100]] as [$max_frames, $max_length]) {
            $url = $base . '?' . http_build_query(
                array_map($this->sanitize(...), $fields($max_frames, $max_length)),
                '',
                '&',
                PHP_QUERY_RFC3986,
            );

            if (strlen($url) <= self::MAX_URL_LENGTH) {
                return $url;
            }
        }

        return $base;
    }

    /**
     * The exception that was thrown first, without the details of an error that Psalm turned into an exception
     *
     * @psalm-capabilities read-props
     */
    private function getIssueTitle(): string
    {
        $message = (string) preg_replace(
            ['# for command with CLI args ".*$#s', '# in \S+:\d+$#', '#^PHP Error: #'],
            '',
            $this->chain[0]['message'],
        );

        return 'Crash: ' . self::cut($this->chain[0]['class'] . ': ' . $message, 200);
    }

    /**
     * The first frames of the exception that wraps no other: the frames of the exceptions around it mostly repeat
     * its stack
     *
     * @psalm-capabilities read-props
     */
    private function renderFrames(int $max_frames): string
    {
        $lines = [];
        foreach ($this->chain[count($this->chain) - 1]['frames'] as $i => $frame) {
            if ($i >= $max_frames) {
                break;
            }

            $lines[] = self::renderFrame($i, $frame);
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * @psalm-pure
     */
    private static function cut(string $text, int $max_length): string
    {
        return strlen($text) <= $max_length ? $text : mb_strcut($text, 0, $max_length - 3) . '...';
    }

    /**
     * The owner and the name of a GitHub repository in its URL
     *
     * @psalm-pure
     */
    private static function findGithubRepository(?string $url): ?string
    {
        return $url !== null
            && preg_match('~^(?:https?://|git@)github\.com[/:]([\w.-]+/[\w.-]+?)(?:\.git)?(?:[/?#]|$)~', $url, $matches)
                ? $matches[1] ?? null
                : null;
    }

    /**
     * @param Package $package
     * @psalm-pure
     */
    private static function getTracker(array $package): ?string
    {
        $repository = $package['issues'] === null ? self::findGithubRepository($package['source']) : null;

        return $repository === null
            ? $package['issues'] ?? $package['source']
            : 'https://github.com/' . $repository . '/issues';
    }

    /**
     * @psalm-capabilities read-props
     */
    private function isPluginCrash(): bool
    {
        return $this->plugin_package !== null || $this->context->handler !== null;
    }

    public static function create(Throwable $throwable): self
    {
        try {
            $config = Config::getInstance();
        } catch (Throwable) {
            $config = null;
        }

        return new self($throwable, $config, $config?->getInstalledPackages() ?? []);
    }

    /**
     * Writes the full report to crash-<time>-<pid>.txt in the cache directory, the directory of PSALM_CRASH_REPORT
     * or the temporary directory when there is no cache. PSALM_CRASH_REPORT=0 writes nothing.
     *
     * @return ?string the path of the file
     */
    public function writeFile(string $command_line): ?string
    {
        $setting = CliUtils::chosenByOperator(getenv('PSALM_CRASH_REPORT'));
        if ($setting === '0') {
            return null;
        }

        $config = $this->config;
        $directory = is_string($setting) && $setting !== ''
            ? $setting
            : ($config !== null && $config->cache_directory !== null && $config->global_cache_directory !== null
                ? $config->global_cache_directory
                : sys_get_temp_dir());

        if (!is_dir($directory) && !@mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException('could not create ' . $directory);
        }

        $path = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR
            . 'crash-' . date('Ymd-His') . '-' . (int) getmypid() . '.txt';
        if (@file_put_contents($path, $this->getFileContents($command_line)) === false) {
            throw new RuntimeException('could not write ' . $path);
        }

        return $path;
    }

    /**
     * The full report, without the paths of the user's machine
     */
    public function getFileContents(string $command_line): string
    {
        $context = $this->getContextLine();

        $lines = [
            $this->getHeadline(),
            ...($context === null ? [] : [$context]),
            '',
            'Psalm: ' . (defined('PSALM_VERSION') ? self::getPsalmVersion() : 'unknown'),
            'PHP: ' . PHP_VERSION,
            'Extensions: ' . self::describeExtensions(),
            'OS: ' . self::describeOs(),
            'Command: ' . $command_line,
            'Config: ' . ($this->config?->source_filename ?? 'none'),
            'Config attributes: ' . $this->describeConfigAttributes(),
            'Plugins: ' . $this->describePlugins(),
            'Phase: ' . ($this->context->activity ?? ($this->context->line === null ? 'unknown' : 'analysing')),
            '',
            $this->renderChain(),
        ];

        return $this->sanitize(implode(PHP_EOL, $lines) . PHP_EOL);
    }

    /**
     * The text with the paths of the packages, of Psalm, of the project, of the home and the temporary directories
     * made relative to them, and the directories of other absolute paths left out
     */
    public function sanitize(string $text): string
    {
        $prefixes = [];
        foreach ($this->packages as $package) {
            self::addPrefix($prefixes, $package['install_path'], $package['name']);
        }

        self::addPrefix($prefixes, self::getPsalmRoot(), 'vimeo/psalm');
        if ($this->config !== null) {
            self::addPrefix($prefixes, $this->config->base_dir, '<project>');
        }

        foreach (['HOME', 'USERPROFILE'] as $variable) {
            $home = CliUtils::chosenByOperator(getenv($variable));
            if (is_string($home) && $home !== '') {
                self::addPrefix($prefixes, $home, '~');
            }
        }

        self::addPrefix($prefixes, sys_get_temp_dir(), '<tmp>');

        // strtr() replaces the longest prefix first: a package inside the project, the project inside the home
        return (string) preg_replace(
            [
                '#(?<![\w.~<>/:\\\\-])/(?:[^\s/\\\\:()\'"<>]+/)+#',
                '#(?<![\w.])[A-Za-z]:\\\\(?:[^\s/\\\\:()\'"<>]+\\\\)+#',
            ],
            '.../',
            strtr($text, $prefixes),
        );
    }

    /**
     * @param array<string, string> $prefixes
     */
    private static function addPrefix(array &$prefixes, string $path, string $replacement): void
    {
        $real_path = realpath($path);
        foreach ([$path, $real_path === false ? $path : $real_path] as $candidate) {
            $candidate = rtrim($candidate, '/\\');
            if ($candidate !== '') {
                $prefixes[$candidate . '/'] = $replacement . '/';
                $prefixes[$candidate . '\\'] = $replacement . '/';
            }
        }
    }

    private static function describeExtensions(): string
    {
        $extensions = [];
        foreach (get_loaded_extensions() as $extension) {
            $version = phpversion($extension);
            $extensions[] = $extension . ($version === false || $version === PHP_VERSION ? '' : ' ' . $version);
        }

        sort($extensions);

        return implode(', ', $extensions);
    }

    /**
     * @psalm-pure
     */
    private static function describeOs(): string
    {
        return function_exists('php_uname')
            ? PHP_OS_FAMILY . ' ' . php_uname('r') . ' ' . php_uname('m')
            : PHP_OS_FAMILY;
    }

    /**
     * The attributes the config file sets on <psalm>
     */
    private function describeConfigAttributes(): string
    {
        $path = $this->config?->source_filename;
        $xml = $path === null ? false : @simplexml_load_file($path);
        if ($xml === false) {
            return 'none';
        }

        $attributes = [];
        foreach ($xml->attributes() ?? [] as $name => $value) {
            $attributes[] = $name . '="' . (string) $value . '"';
        }

        return implode(' ', $attributes);
    }

    /**
     * The enabled plugins: the package and its version for a plugin class Composer installed
     */
    private function describePlugins(): string
    {
        if ($this->config === null) {
            return 'unknown';
        }

        $packages_by_class = [];
        foreach ($this->packages as $package) {
            if ($package['plugin_class'] !== null) {
                $packages_by_class[strtolower(ltrim($package['plugin_class'], '\\'))] = $package['name']
                    . ($package['version'] === null ? '' : ' ' . $package['version']);
            }
        }

        $plugins = [];
        foreach ($this->config->getPluginClasses() as $plugin) {
            $plugins[] = $packages_by_class[strtolower(ltrim($plugin['class'], '\\'))] ?? $plugin['class'];
        }

        foreach ($this->config->plugin_paths as $path) {
            $plugins[] = $this->config->shortenFileName($path);
        }

        return $plugins === [] ? 'none' : implode(', ', $plugins);
    }

    /**
     * Where and in which plugin Psalm crashed, the report of the crash and Psalm's version
     */
    public function getSummary(): string
    {
        $context = $this->getContextLine();
        $message = $this->task_crash?->getMessage();
        $end_of_first_line = $message === null ? false : strpos($message, PHP_EOL);

        return $this->getHeadline() . PHP_EOL
            . ($context === null ? '' : $context . PHP_EOL)
            // the first line of the report of a TaskCrashedException says where it crashed, like the headline
            . ($message === null || $end_of_first_line === false
                ? 'Uncaught ' . $this->throwable->__toString()
                : substr($message, $end_of_first_line + strlen(PHP_EOL)))
            . PHP_EOL
            . '(Psalm ' . (defined('PSALM_VERSION') ? self::getPsalmVersion() : '(unknown version)')
            . ' crashed due to an uncaught Throwable)';
    }

    /**
     * The statement Psalm was analysing: "while analysing App\Foo::bar() at src/Foo.php:42"
     */
    public function getContextLine(): ?string
    {
        if ($this->context->file_path === null || $this->context->line === null) {
            return null;
        }

        return 'while analysing '
            . ($this->context->function_like === null ? '' : $this->context->function_like . '() at ')
            . $this->shortenFileName($this->context->file_path) . ':' . $this->context->line;
    }

    /**
     * "Psalm crashed", on which file when there is no statement to name, and in which plugin
     */
    public function getHeadline(): string
    {
        $file_path = $this->context->file_path ?? $this->task_crash?->path;
        $headline = 'Psalm crashed'
            . ($file_path !== null && $this->context->line === null ? ' on ' . $this->shortenFileName($file_path) : '');

        $package = $this->plugin_package === null
            ? null
            : $this->plugin_package['name']
                . ($this->plugin_package['version'] === null ? '' : ' ' . $this->plugin_package['version']);

        if ($this->plugin_guessed) {
            return $headline . ', probably in plugin ' . (string) $package;
        }

        if ($this->context->handler === null || $this->context->hook === null) {
            return $headline;
        }

        $last_separator = strrpos($this->context->hook, '\\');
        $hook = $last_separator === false ? $this->context->hook : substr($this->context->hook, $last_separator + 1);

        return $headline . ' in ' . ($package === null ? 'a plugin' : 'plugin ' . $package)
            . ' (' . $hook . ', ' . $this->context->handler . ')';
    }

    private function shortenFileName(string $file_path): string
    {
        return $this->config?->shortenFileName($file_path) ?? $file_path;
    }

    /**
     * Each exception of the chain: its class and message, where it was thrown and, for the report file, its frames.
     * The message leaves out the command line that ErrorHandler appends: the report file has it on its own line.
     *
     * @psalm-capabilities read-props
     */
    private function renderChain(bool $with_frames = true, int $max_length = PHP_INT_MAX): string
    {
        $rendered = [];
        foreach ($this->chain as $exception) {
            $message = (string) preg_replace('# for command with CLI args ".*$#s', '', $exception['message']);
            $lines = [
                $exception['class'] . ': ' . self::cut($message, $max_length),
                '  at ' . $exception['file'] . ':' . $exception['line'],
            ];
            foreach ($with_frames ? $exception['frames'] : [] as $i => $frame) {
                $lines[] = self::renderFrame($i, $frame);
            }

            $rendered[] = implode(PHP_EOL, $lines);
        }

        return implode(PHP_EOL . 'Caused by ', $rendered);
    }

    /**
     * @param Frame $frame
     * @psalm-pure
     */
    private static function renderFrame(int $i, array $frame): string
    {
        return '#' . $i . ' '
            . ($frame['file'] === null ? '[internal function]' : $frame['file'] . '(' . $frame['line'] . ')')
            . ': ' . $frame['call'];
    }

    /**
     * The first frame, from where the innermost exception was thrown, that is in a plugin package
     *
     * @return ?Package
     */
    private function guessPluginPackage(): ?array
    {
        foreach (array_reverse($this->chain) as $exception) {
            foreach ([$exception['file'], ...array_column($exception['frames'], 'file')] as $file) {
                if ($file !== null && ($package = $this->findPackage($file, true)) !== null) {
                    return $package;
                }
            }
        }

        return null;
    }

    /**
     * The package with the file, the innermost one when packages are nested
     *
     * @return ?Package
     */
    private function findPackage(string $file, bool $plugins_only): ?array
    {
        $found = null;
        foreach ($this->packages as $package) {
            if (($plugins_only && $package['plugin_class'] === null)
                || strtolower($package['name']) === 'vimeo/psalm'
                || !self::isInDirectory($file, $package['install_path'])
            ) {
                continue;
            }

            if ($found === null || strlen($package['install_path']) > strlen($found['install_path'])) {
                $found = $package;
            }
        }

        return $found;
    }

    /**
     * @return non-empty-list<Exception>
     */
    private static function describeChain(Throwable $throwable): array
    {
        $chain = [];
        for ($e = $throwable; $e !== null; $e = $e->getPrevious()) {
            if ($e instanceof TaskCrashedException) {
                // it only wraps the failure of the task
                continue;
            }

            if ($e instanceof TaskFailureThrowable) {
                $frames = [];
                foreach ($e->getOriginalTrace() as $frame) {
                    $frames[] = self::describeFrame($frame);
                    $class = $frame['class'] ?? null;
                    if (is_string($class)
                        && ($frame['function'] ?? null) === 'run'
                        && is_subclass_of($class, Task::class)
                    ) {
                        // the rest is the task runner of amphp and the stack of the parent at the time of the fork
                        break;
                    }
                }

                $chain[] = [
                    'class' => $e->getOriginalClassName(),
                    'message' => $e->getOriginalMessage(),
                    'file' => $e->getOriginalFile(),
                    'line' => $e->getOriginalLine(),
                    'frames' => $frames,
                ];
                continue;
            }

            $frames = [];
            foreach ($e->getTrace() as $frame) {
                $frames[] = self::describeFrame($frame);
            }

            $chain[] = [
                'class' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'frames' => $frames,
            ];
        }

        if ($chain === []) {
            // a TaskCrashedException with nothing to unwrap
            $chain[] = [
                'class' => $throwable::class,
                'message' => $throwable->getMessage(),
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
                'frames' => [],
            ];
        }

        return $chain;
    }

    /**
     * @param array<array-key, mixed> $frame
     * @return Frame
     * @psalm-pure
     */
    private static function describeFrame(array $frame): array
    {
        $class = isset($frame['class']) && is_string($frame['class'])
            ? $frame['class'] . (isset($frame['type']) && is_string($frame['type']) ? $frame['type'] : '::')
            : '';

        return [
            'file' => isset($frame['file']) && is_string($frame['file']) ? $frame['file'] : null,
            'line' => isset($frame['line']) && is_int($frame['line']) ? $frame['line'] : 0,
            'call' => $class . (isset($frame['function']) && is_string($frame['function']) ? $frame['function'] : '')
                . '()',
        ];
    }

    /**
     * @param class-string $class
     * @psalm-pure
     */
    private static function getClassFile(string $class): ?string
    {
        try {
            $file = (new ReflectionClass($class))->getFileName();
        } catch (Throwable) {
            return null;
        }

        return $file === false ? null : $file;
    }

    private static function isInDirectory(string $file, string $directory): bool
    {
        $file = Path::canonicalize($file);
        $real_directory = realpath($directory);
        foreach ([$directory, $real_directory === false ? $directory : $real_directory] as $candidate) {
            if (str_starts_with($file, rtrim(Path::canonicalize($candidate), '/') . '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * The directory Psalm is installed in, a phar:// path in the phar
     *
     * @psalm-pure
     */
    private static function getPsalmRoot(): string
    {
        return dirname(__DIR__, 4);
    }

    /**
     * A commit is recognizable by its first 7 characters, e.g. dev-master@8f422cf
     *
     * @psalm-pure
     */
    private static function getPsalmVersion(): string
    {
        return (string) preg_replace('/@([0-9a-f]{7})[0-9a-f]{33}$/', '@$1', PSALM_VERSION);
    }
}
