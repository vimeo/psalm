<?php

declare(strict_types=1);

namespace Psalm\Internal\Crash;

use Amp\Parallel\Worker\Task;
use Amp\Parallel\Worker\TaskFailureThrowable;
use Psalm\Config;
use Psalm\Internal\CliUtils;
use Psalm\Internal\Fork\TaskCrashedException;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Filesystem\Path;
use Throwable;

use function array_column;
use function array_reverse;
use function date;
use function defined;
use function dirname;
use function file_put_contents;
use function function_exists;
use function fwrite;
use function get_loaded_extensions;
use function getenv;
use function getmypid;
use function implode;
use function is_dir;
use function is_int;
use function is_string;
use function is_subclass_of;
use function ltrim;
use function mkdir;
use function php_uname;
use function phpversion;
use function preg_replace;
use function realpath;
use function rtrim;
use function simplexml_load_file;
use function sort;
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
use const PHP_OS_FAMILY;
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

        $handler_file = $this->context->handler === null ? null : self::getClassFile($this->context->handler);
        if ($handler_file !== null && self::isInDirectory($handler_file, self::getPsalmRoot() . '/src')) {
            // a hook of Psalm itself
            $this->context->hook = $this->context->handler = null;
        }

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
                PHP_EOL . 'Uncaught ' . $throwable . PHP_EOL
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
     * Each exception of the chain: its class and message, where it was thrown and the frames of its trace
     *
     * @psalm-capabilities read-props
     */
    private function renderChain(): string
    {
        $rendered = [];
        foreach ($this->chain as $exception) {
            $lines = [
                $exception['class'] . ': ' . $exception['message'],
                '  at ' . $exception['file'] . ':' . $exception['line'],
            ];
            foreach ($exception['frames'] as $i => $frame) {
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
