<?php

declare(strict_types=1);

namespace Psalm\Internal\Crash;

use Amp\Parallel\Worker\Task;
use Amp\Parallel\Worker\TaskFailureThrowable;
use Psalm\Config;
use Psalm\Internal\Fork\TaskCrashedException;
use ReflectionClass;
use Symfony\Component\Filesystem\Path;
use Throwable;

use function array_column;
use function array_reverse;
use function defined;
use function dirname;
use function fwrite;
use function is_int;
use function is_string;
use function is_subclass_of;
use function preg_replace;
use function realpath;
use function rtrim;
use function str_starts_with;
use function strlen;
use function strpos;
use function strrpos;
use function strtolower;
use function substr;

use const PHP_EOL;
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
     * Prints the report of the crash to STDERR. A failure to make the report does not hide the crash.
     */
    public static function print(Throwable $throwable): void
    {
        try {
            $report = self::create($throwable);
            $summary = $report->getSummary();
        } catch (Throwable $failure) {
            fwrite(
                STDERR,
                PHP_EOL . 'Uncaught ' . $throwable->__toString() . PHP_EOL
                . 'Psalm could not report the crash: ' . $failure->getMessage() . PHP_EOL,
            );

            return;
        }

        fwrite(STDERR, PHP_EOL . $summary . PHP_EOL);
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
