<?php

declare(strict_types=1);

namespace Psalm\Internal\Perf;

use Closure;
use PhpParser\Node\Stmt;
use Psalm\StatementsSource;
use ReflectionFunction;

use function array_keys;
use function array_map;
use function array_slice;
use function array_values;
use function basename;
use function count;
use function dirname;
use function getcwd;
use function getenv;
use function getmypid;
use function getrusage;
use function hrtime;
use function intdiv;
use function is_string;
use function max;
use function memory_get_peak_usage;
use function memory_get_usage;
use function memory_reset_peak_usage;
use function preg_match;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function strlen;
use function substr;
use function usort;

use const DIRECTORY_SEPARATOR;
use const PHP_OS_FAMILY;

/**
 * Records the timings behind `--debug-performance`. It is loaded before the autoloader, so it depends on no other
 * class at load time.
 *
 * @internal
 * @psalm-type FileRecord = array{
 *     op: string,
 *     path: string,
 *     pid: int,
 *     start_us: int,
 *     wall_us: int,
 *     cpu_us: int,
 *     peak_growth_bytes: int,
 *     deep?: bool,
 *     storage?: string,
 *     size_bytes?: int,
 *     issues?: int,
 * }
 * @psalm-type StatementRecord = array{
 *     path: string,
 *     line: int,
 *     node: string,
 *     passes: int,
 *     self_us: int,
 *     wall_us: int,
 * }
 * @psalm-type FunctionRecord = array{
 *     path: string,
 *     line: int,
 *     id: string,
 *     pass: string,
 *     passes: int,
 *     self_us: int,
 *     wall_us: int,
 * }
 * @psalm-type HookRecord = array{handler: string, hook: string, calls: int, wall_ns: int}
 * @psalm-type ParserCounts = array{parses: int, cache_hits: int}
 * @psalm-type WorkerPerf = array{
 *     pid: int,
 *     role: string,
 *     start_us: int,
 *     end_us: int,
 *     cpu_us: int,
 *     cpu_sys_us: int,
 *     php_start_bytes: int,
 *     peak_growth_bytes: int,
 *     max_rss_bytes: int,
 *     files: list<FileRecord>,
 *     statements: list<StatementRecord>,
 *     functions: list<FunctionRecord>,
 *     hooks: list<HookRecord>,
 *     parser: ParserCounts,
 * }
 * @psalm-type StageRecord = array{
 *     name: string,
 *     start_us: int,
 *     wall_us: int,
 *     cpu_us: int,
 *     children_cpu_us: int,
 *     php_end_bytes: int,
 *     php_peak_bytes: int,
 * }
 * @psalm-type StepRecord = array{
 *     name: string,
 *     stage: string,
 *     start_us: int,
 *     wall_us: int,
 *     cpu_us: int,
 *     php_end_bytes: int,
 * }
 * @psalm-type PoolRecord = array{
 *     id: int,
 *     role: string,
 *     tasks: int,
 *     start_us: int,
 *     init_us: int,
 *     run_us: int,
 *     collect_us: int,
 *     merge_us: int,
 *     cpu_us: int,
 *     payload_bytes?: int,
 *     payload_max_bytes?: int,
 *     workers: list<WorkerRecord>,
 * }
 * @psalm-type WorkerRecord = array{
 *     pid: int,
 *     start_us: int,
 *     end_us: int,
 *     tasks: int,
 *     busy_us: int,
 *     cpu_us: int,
 *     cpu_sys_us: int,
 *     php_start_bytes: int,
 *     peak_growth_bytes: int,
 *     max_rss_bytes: int,
 * }
 * @psalm-type Measurements = array{
 *     totals: array{
 *         wall_us: int,
 *         cpu_us: int,
 *         cpu_sys_us: int,
 *         children_cpu_us: int,
 *         php_peak_bytes: int,
 *         max_rss_bytes: int,
 *         files_scanned: int,
 *         files_analyzed: int,
 *         issues: array{errors: int, infos: int},
 *     },
 *     stages: list<StageRecord>,
 *     steps: list<StepRecord>,
 *     pools: list<PoolRecord>,
 *     files: list<FileRecord>,
 *     taint: array{nodes: int, sources: int, sinks: int, specializations: int, resolver_rounds: int},
 *     hot: array{
 *         min_us: int,
 *         limit: int,
 *         statements: list<StatementRecord>,
 *         functions: list<FunctionRecord>,
 *     },
 *     hooks: list<array{handler: string, hook: string, origin: string, calls: int, wall_us: int}>,
 *     plugins: list<array{class: string, init_us: int}>,
 *     parser: ParserCounts,
 * }
 */
final class Collector
{
    /** Read by every hook: nothing else is evaluated when false */
    public static bool $enabled = false;

    /** Where the storage of the file being scanned came from, set by the scanner while collecting */
    private static string $scan_storage = '';

    private static int $scan_bytes = 0;

    /** Inclusive nanoseconds of the statements analysed inside the statement being timed */
    public static int $child_ns = 0;

    /** Inclusive nanoseconds of the function-like passes analysed inside the pass being timed */
    private static int $function_child_ns = 0;

    private const HOT_COUNT = 50;

    /** Statements and function-likes that take less than this are not recorded */
    private const MIN_RECORD_US = 1_000;

    private static ?string $dump_path = null;

    /** @var list<string> */
    private static array $option_names = [];

    private static bool $worker = false;

    /** When the last file the worker took ended, in microseconds from the start; 0 before the first */
    private static int $worker_end_us = 0;

    /** The stage the main process is in */
    private static string $stage = '';

    private static int $t0 = 0;

    private static int $pid = 0;

    private static int $start_sys_us = 0;

    /** @var list<array{name: string, t_us: int, cpu_us: int, children_cpu_us: int, bytes: int, peak: int}> */
    private static array $marks = [];

    /**
     * Where a step starts, or, with no name, where the step before it ends because a stage starts
     *
     * @var list<array{name: ?string, stage: string, mark: array{
     *     name: string, t_us: int, cpu_us: int, children_cpu_us: int, bytes: int, peak: int
     * }}>
     */
    private static array $step_marks = [];

    /** @var list<FileRecord> */
    private static array $files = [];

    /** @var array<string, StatementRecord> the statements that took long, by place; one record for all passes */
    private static array $statements = [];

    /** @var array<string, FunctionRecord> the function-likes that took long, by place and pass */
    private static array $functions = [];

    /** @var array<string, array<string, int>> calls of the handlers, by handler class and hook */
    private static array $hook_calls = [];

    /** @var array<string, array<string, int>> nanoseconds in the handlers, by handler class and hook */
    private static array $hook_ns = [];

    private static int $parses = 0;

    private static int $cache_hits = 0;

    /** @var list<PoolRecord> */
    private static array $pools = [];

    /** @var list<array{class: string, init_us: int}> */
    private static array $plugins = [];

    /** @var ?PoolRecord */
    private static ?array $pool = null;

    private static int $pool_mark_ns = 0;

    /** The CPU time of the terminated children of the main process when the pool started */
    private static int $pool_cpu_us = 0;

    private static ?int $payload_sum = null;

    private static int $payload_max = 0;

    /** @var array{nodes: int, sources: int, sinks: int, specializations: int, resolver_rounds: int} */
    private static array $taint = ['nodes' => 0, 'sources' => 0, 'sinks' => 0, 'specializations' => 0,
        'resolver_rounds' => 0];

    /** @var array{start_us: int, cpu_us: int, sys_us: int, bytes: int, role: string} */
    private static array $worker_start = ['start_us' => 0, 'cpu_us' => 0, 'sys_us' => 0, 'bytes' => 0, 'role' => ''];

    /**
     * @param list<string> $option_names the names of the options given on the command line
     */
    public static function start(?string $dump_path, array $option_names): void
    {
        self::reset();

        self::$enabled = true;
        self::$dump_path = $dump_path === null ? null : self::absolutePath($dump_path, (string) getcwd());
        self::$option_names = $option_names;
        self::$t0 = (int) hrtime(true);
        self::$pid = (int) getmypid();
        self::$start_sys_us = self::sysUs();
        self::$marks[] = self::mark('bootstrap');
        self::$stage = 'bootstrap';
    }

    public static function reset(): void
    {
        self::$enabled = false;
        self::$worker = false;
        self::$worker_end_us = 0;
        self::$scan_storage = '';
        self::$scan_bytes = 0;
        self::$stage = '';
        self::clearRecords();
        self::$dump_path = null;
        self::$option_names = [];
        self::$marks = [];
        self::$step_marks = [];
        self::$pools = [];
        self::$plugins = [];
        self::$pool = null;
        self::$payload_sum = null;
        self::$payload_max = 0;
        self::$taint = ['nodes' => 0, 'sources' => 0, 'sinks' => 0, 'specializations' => 0, 'resolver_rounds' => 0];
    }

    /** What a process records itself: a worker starts without what it inherited from the main process */
    private static function clearRecords(): void
    {
        self::$child_ns = 0;
        self::$function_child_ns = 0;
        self::$files = [];
        self::$statements = [];
        self::$functions = [];
        self::$hook_calls = [];
        self::$hook_ns = [];
        self::$parses = 0;
        self::$cache_hits = 0;
    }

    public static function dumpPath(): ?string
    {
        return self::$dump_path;
    }

    /** @return list<string> */
    public static function optionNames(): array
    {
        return self::$option_names;
    }

    /**
     * Ends the current stage of the main process and starts the next one
     *
     * @return string the stage that ended, to restore it later
     */
    public static function stage(string $name): string
    {
        if (!self::$enabled || self::$worker) {
            return '';
        }

        $previous = self::$stage;
        self::$stage = $name;
        self::$marks[] = self::mark($name);

        // the step that is open ends with the stage it is in
        if (self::$step_marks !== [] && self::$step_marks[count(self::$step_marks) - 1]['name'] !== null) {
            self::$step_marks[] = ['name' => null, 'stage' => $name, 'mark' => self::$marks[count(self::$marks) - 1]];
        }

        return $previous;
    }

    /**
     * Starts a named step of the current stage of the main process. A step lasts until the next step, or until the
     * stage ends; what a stage does before its first step belongs to no step.
     */
    public static function step(string $name): void
    {
        if (self::$enabled && !self::$worker) {
            self::$step_marks[] = ['name' => $name, 'stage' => self::$stage, 'mark' => self::mark($name)];
        }
    }

    /** Where the storage of the file being scanned came from, and how long its contents are */
    public static function setScan(string $storage, int $bytes): void
    {
        self::$scan_storage = $storage;
        self::$scan_bytes = $bytes;
    }

    /** @return array{int, int, int} */
    public static function fileStart(): array
    {
        return [(int) hrtime(true), self::cpuUs(0), memory_get_peak_usage()];
    }

    /**
     * @param string $file_path the path of the file, as it is on disk
     * @param string $file_name the path of the file as Psalm prints it (see Config::shortenFileName())
     * @param array{int, int, int} $start
     */
    public static function scanEnd(
        string $file_path,
        string $file_name,
        array $start,
        bool $deep,
    ): void {
        self::$files[] = self::fileRecord('scan', $file_path, $file_name, $start)
            + ['deep' => $deep, 'storage' => self::$scan_storage, 'size_bytes' => self::$scan_bytes];
    }

    /** @param array{int, int, int} $start */
    public static function analysisEnd(string $file_path, string $file_name, array $start, int $issues): void
    {
        self::$files[] = self::fileRecord('analysis', $file_path, $file_name, $start) + ['issues' => $issues];
    }

    public static function statementEnd(StatementsSource $source, Stmt $stmt, int $start_ns, int $outer_child_ns): void
    {
        $inclusive_ns = (int) hrtime(true) - $start_ns;
        $self_ns = $inclusive_ns - self::$child_ns;
        self::$child_ns = $outer_child_ns + $inclusive_ns;

        if ($self_ns >= self::MIN_RECORD_US * 1_000) {
            self::recordStatement(
                self::displayPath($source->getFilePath(), $source->getFileName()),
                $stmt->getStartLine(),
                $stmt->getType(),
                intdiv($self_ns, 1_000),
                intdiv($inclusive_ns, 1_000),
            );
        }
    }

    /** $passes executions of a statement, which took $self_us of their own and $wall_us with what they analysed */
    public static function recordStatement(
        string $path,
        int $line,
        string $node,
        int $self_us,
        int $wall_us,
        int $passes = 1,
    ): void {
        $key = $path . ':' . $line . ':' . $node;
        $record = self::$statements[$key]
            ?? ['path' => $path, 'line' => $line, 'node' => $node, 'passes' => 0, 'self_us' => 0, 'wall_us' => 0];
        $record['passes'] += $passes;
        $record['self_us'] += $self_us;
        $record['wall_us'] += $wall_us;
        self::$statements[$key] = $record;
    }

    /** @return array{int, int} the time the pass starts at, and what the passes it is in took so far */
    public static function functionStart(): array
    {
        $outer_child_ns = self::$function_child_ns;
        self::$function_child_ns = 0;

        return [(int) hrtime(true), $outer_child_ns];
    }

    /** @param array{int, int} $start */
    public static function functionEnd(
        string $file_path,
        string $file_name,
        int $line,
        string $id,
        bool $initializations,
        array $start,
    ): void {
        $inclusive_ns = (int) hrtime(true) - $start[0];
        $self_ns = $inclusive_ns - self::$function_child_ns;
        self::$function_child_ns = $start[1] + $inclusive_ns;

        if ($inclusive_ns >= self::MIN_RECORD_US * 1_000) {
            self::recordFunction(
                self::displayPath($file_path, $file_name),
                $line,
                $id,
                $initializations ? 'initializations' : 'body',
                intdiv($self_ns, 1_000),
                intdiv($inclusive_ns, 1_000),
            );
        }
    }

    /**
     * $passes passes over a function-like, which took $self_us of their own, and $wall_us with the passes over
     * other function-likes that they made
     */
    public static function recordFunction(
        string $path,
        int $line,
        string $id,
        string $pass,
        int $self_us,
        int $wall_us,
        int $passes = 1,
    ): void {
        $key = $path . ':' . $line . ':' . $id . ':' . $pass;
        $record = self::$functions[$key] ?? [
            'path' => $path, 'line' => $line, 'id' => $id, 'pass' => $pass, 'passes' => 0, 'self_us' => 0,
            'wall_us' => 0,
        ];
        $record['passes'] += $passes;
        $record['self_us'] += $self_us;
        $record['wall_us'] += $wall_us;
        self::$functions[$key] = $record;
    }

    /** Called in a forked worker before it does any work: what it inherited belongs to the main process */
    public static function workerStart(string $role): void
    {
        if (!self::$enabled) {
            return;
        }

        self::$worker = true;
        self::$worker_end_us = 0;
        self::$pid = (int) getmypid();
        self::clearRecords();
        memory_reset_peak_usage();
        self::$worker_start = [
            'start_us' => self::nowUs(),
            'cpu_us' => self::cpuUs(0),
            'sys_us' => self::sysUs(),
            'bytes' => memory_get_usage(),
            'role' => $role,
        ];
    }

    /** @return ?WorkerPerf */
    public static function workerData(): ?array
    {
        if (!self::$enabled) {
            return null;
        }

        return [
            'pid' => self::$pid,
            'role' => self::$worker_start['role'],
            'start_us' => self::$worker_start['start_us'],
            // when it stopped working, not when the main process asked for its result
            'end_us' => self::$worker_end_us > 0 ? self::$worker_end_us : self::nowUs(),
            'cpu_us' => self::cpuUs(0) - self::$worker_start['cpu_us'],
            'cpu_sys_us' => self::sysUs() - self::$worker_start['sys_us'],
            'php_start_bytes' => self::$worker_start['bytes'],
            'peak_growth_bytes' => memory_get_peak_usage() - self::$worker_start['bytes'],
            'max_rss_bytes' => self::maxRssBytes(),
            'files' => self::$files,
            'statements' => array_values(self::$statements),
            'functions' => array_values(self::$functions),
            'hooks' => array_values(self::hookTotals()),
            'parser' => self::parserCounts(),
        ];
    }

    public static function poolStart(string $role, int $tasks): void
    {
        if (!self::$enabled) {
            return;
        }

        self::$pool = [
            'id' => count(self::$pools) + 1,
            'role' => $role,
            'tasks' => $tasks,
            'start_us' => self::nowUs(),
            'init_us' => 0,
            'run_us' => 0,
            'collect_us' => 0,
            'merge_us' => 0,
            'cpu_us' => 0,
            'workers' => [],
        ];
        self::$pool_cpu_us = self::cpuUs(1);
        self::$pool_mark_ns = (int) hrtime(true);
        self::$payload_sum = null;
        self::$payload_max = 0;
    }

    /** @param 'init_us'|'run_us' $step the step of the pool that just ended */
    public static function poolStep(string $step): void
    {
        if (self::$pool !== null) {
            self::$pool[$step] = self::sincePoolMark();
        }
    }

    /** The size of the serialized result of a worker */
    public static function payload(int $bytes): void
    {
        if (self::$enabled) {
            self::$payload_sum = (self::$payload_sum ?? 0) + $bytes;
            self::$payload_max = max(self::$payload_max, $bytes);
        }
    }

    /**
     * Called with the result of each worker, in the loop that merges them
     *
     * @param ?WorkerPerf $data
     */
    public static function addWorkerData(?array $data): void
    {
        if (!self::$enabled) {
            return;
        }

        if (self::$pool !== null) {
            self::$pool['collect_us'] += self::sincePoolMark();
        }

        if ($data === null) {
            return;
        }

        $tasks = 0;
        $busy_us = 0;

        foreach ($data['files'] as $file) {
            self::$files[] = $file;

            // a scan inside the analysis of a file is part of it
            if ($file['op'] === $data['role']) {
                ++$tasks;
                $busy_us += $file['wall_us'];
            }
        }

        foreach ($data['statements'] as $r) {
            self::recordStatement($r['path'], $r['line'], $r['node'], $r['self_us'], $r['wall_us'], $r['passes']);
        }

        foreach ($data['functions'] as $r) {
            self::recordFunction(
                $r['path'],
                $r['line'],
                $r['id'],
                $r['pass'],
                $r['self_us'],
                $r['wall_us'],
                $r['passes'],
            );
        }

        foreach ($data['hooks'] as $hook) {
            self::addHook($hook['handler'], $hook['hook'], $hook['calls'], $hook['wall_ns']);
        }

        self::$parses += $data['parser']['parses'];
        self::$cache_hits += $data['parser']['cache_hits'];

        if (self::$pool === null) {
            return;
        }

        self::$pool['workers'][] = [
            'pid' => $data['pid'],
            'start_us' => $data['start_us'],
            'end_us' => $data['end_us'],
            'tasks' => $tasks,
            'busy_us' => $busy_us,
            'cpu_us' => $data['cpu_us'],
            'cpu_sys_us' => $data['cpu_sys_us'],
            'php_start_bytes' => $data['php_start_bytes'],
            'peak_growth_bytes' => $data['peak_growth_bytes'],
            'max_rss_bytes' => $data['max_rss_bytes'],
        ];
    }

    /** Called when the merge of one worker result is done */
    public static function mergeDone(): void
    {
        if (self::$pool !== null) {
            self::$pool['merge_us'] += self::sincePoolMark();
        }
    }

    public static function poolEnd(): void
    {
        if (self::$pool === null) {
            return;
        }

        // the workers of the pool are reaped by now, so their CPU time is in the children's
        self::$pool['cpu_us'] = self::cpuUs(1) - self::$pool_cpu_us;

        if (self::$payload_sum !== null) {
            self::$pool['payload_bytes'] = self::$payload_sum;
            self::$pool['payload_max_bytes'] = self::$payload_max;
        }

        self::$pools[] = self::$pool;
        self::$pool = null;
    }

    /**
     * Calls a handler of an event as EventDispatcher does, and times the call
     *
     * @template TEvent of object
     * @template TResult
     * @param callable(TEvent): TResult $handler
     * @param TEvent $event
     * @param string $class the class of the handler
     * @return TResult
     */
    public static function timed(callable $handler, object $event, string $class, string $hook): mixed
    {
        $start = (int) hrtime(true);
        $result = $handler($event);
        self::addHook($class, $hook, 1, (int) hrtime(true) - $start);

        return $result;
    }

    /**
     * A closure that does what the handler does and times it: for the provider registries, which call the closures
     * they hold, so the closures are wrapped once, when they are registered
     *
     * @template TEvent of object
     * @template TResult
     * @param Closure(TEvent): TResult $handler
     * @return Closure(TEvent): TResult
     */
    public static function wrap(Closure $handler, string $hook): Closure
    {
        // the class the closure was made for, not the one that declares its method
        $class = (new ReflectionFunction($handler))->getClosureCalledClass()?->name ?? '{closure}';

        return static fn(object $event): mixed => self::timed($handler, $event, $class, $hook);
    }

    /** A file the parser parsed */
    public static function parsed(): void
    {
        ++self::$parses;
    }

    /** The plugin was initialised since $start_ns */
    public static function pluginInit(string $class, int $start_ns): void
    {
        self::$plugins[] = ['class' => $class, 'init_us' => intdiv((int) hrtime(true) - $start_ns, 1_000)];
    }

    /** Statements of a file that were reused instead of parsed */
    public static function parserCacheHit(): void
    {
        ++self::$cache_hits;
    }

    public static function taint(int $nodes, int $sources, int $sinks, int $specializations): void
    {
        if (self::$enabled) {
            self::$taint = [
                'nodes' => $nodes,
                'sources' => $sources,
                'sinks' => $sinks,
                'specializations' => $specializations,
                'resolver_rounds' => 0,
            ];
        }
    }

    /** A round of the taint resolution */
    public static function taintRound(): void
    {
        ++self::$taint['resolver_rounds'];
    }

    /**
     * Ends the last stage and the collection
     *
     * @param ?int $php_peak_bytes the peak the run printed, if it printed one: the dump holds the same number
     * @return ?Measurements null when nothing was collected
     */
    public static function finish(int $errors, int $infos, ?int $php_peak_bytes = null): ?array
    {
        if (!self::$enabled) {
            return null;
        }

        $end = self::mark('end');
        self::$enabled = false;

        $first = self::$marks[0] ?? $end;
        $stages = [];
        foreach (self::$marks as $i => $mark) {
            $next = self::$marks[$i + 1] ?? $end;
            $stages[] = [
                'name' => $mark['name'],
                'start_us' => $mark['t_us'],
                'wall_us' => $next['t_us'] - $mark['t_us'],
                'cpu_us' => $next['cpu_us'] - $mark['cpu_us'],
                'children_cpu_us' => $next['children_cpu_us'] - $mark['children_cpu_us'],
                'php_end_bytes' => $next['bytes'],
                'php_peak_bytes' => $next['peak'],
            ];
        }

        $steps = [];
        foreach (self::$step_marks as $i => $entry) {
            if ($entry['name'] === null) {
                continue;
            }

            $next = self::$step_marks[$i + 1]['mark'] ?? $end;
            $steps[] = [
                'name' => $entry['name'],
                'stage' => $entry['stage'],
                'start_us' => $entry['mark']['t_us'],
                'wall_us' => $next['t_us'] - $entry['mark']['t_us'],
                'cpu_us' => $next['cpu_us'] - $entry['mark']['cpu_us'],
                'php_end_bytes' => $next['bytes'],
            ];
        }

        $files = self::$files;
        usort(
            $files,
            static fn(array $a, array $b): int => [$a['path'], $a['op']] <=> [$b['path'], $b['op']],
        );

        $scanned = 0;
        $analyzed = 0;
        foreach ($files as $file) {
            if ($file['op'] === 'scan') {
                ++$scanned;
            } else {
                ++$analyzed;
            }
        }

        $statements = self::ranked(self::$statements);
        $functions = self::ranked(self::$functions);

        return [
            'totals' => [
                'wall_us' => $end['t_us'] - $first['t_us'],
                'cpu_us' => $end['cpu_us'] - $first['cpu_us'],
                'cpu_sys_us' => self::sysUs() - self::$start_sys_us,
                'children_cpu_us' => $end['children_cpu_us'] - $first['children_cpu_us'],
                'php_peak_bytes' => $php_peak_bytes ?? $end['peak'],
                'max_rss_bytes' => self::maxRssBytes(),
                'files_scanned' => $scanned,
                'files_analyzed' => $analyzed,
                'issues' => ['errors' => $errors, 'infos' => $infos],
            ],
            'stages' => $stages,
            'steps' => $steps,
            'pools' => self::$pools,
            'files' => $files,
            'taint' => self::$taint,
            'plugins' => self::$plugins,
            'hot' => [
                'min_us' => self::MIN_RECORD_US,
                'limit' => self::HOT_COUNT,
                'statements' => array_slice($statements, 0, self::HOT_COUNT),
                'functions' => array_slice($functions, 0, self::HOT_COUNT),
            ],
            'hooks' => self::hookRecords(),
            'parser' => self::parserCounts(),
        ];
    }

    /**
     * The path of a file as the dump shows it: as Psalm prints it (see Config::shortenFileName()), except that a
     * file of Psalm's own installation outside the project is `<psalm>/<path in Psalm>` and any other path that
     * is still absolute is `<external>/<file name>`, and one inside another phar `<external>/<phar>/<path in it>`:
     * the dump names no directory above the project that Psalm didn't print itself.
     *
     * @param string $file_path the path of the file on disk
     * @param string $file_name the path as Psalm prints it
     * @param ?string $psalm_root the directory Psalm is installed in, or its phar
     * @psalm-pure
     */
    public static function displayPath(string $file_path, string $file_name, ?string $psalm_root = null): string
    {
        $root = str_replace('\\', '/', $psalm_root ?? dirname(__DIR__, 4));
        $path = str_replace('\\', '/', $file_path);
        $in_psalm = str_starts_with($path, $root . '/');

        if (str_starts_with($path, 'phar://')) {
            if ($in_psalm) {
                return '<psalm>/' . substr($path, strlen($root) + 1);
            }

            // the phar and the path in it
            return preg_match('~([^/]+\.phar)(/.*)?$~', $path, $phar) === 1 && isset($phar[1])
                ? '<external>/' . $phar[1] . ($phar[2] ?? '')
                : '<external>/' . basename($path);
        }

        $outside_project = str_starts_with($file_name, '../') || self::isAbsolute($file_name);

        if ($in_psalm && $outside_project) {
            return '<psalm>/' . substr($path, strlen($root) + 1);
        }

        return self::isAbsolute($file_name)
            ? '<external>/' . basename(str_replace('\\', '/', $file_name))
            : $file_name;
    }

    /**
     * The absolute form of a path. A `..` stays where it is: after a symlink it is not the parent of what the
     * path names so far.
     *
     * @psalm-pure
     */
    public static function absolutePath(
        string $path,
        string $base,
        bool $windows = DIRECTORY_SEPARATOR === '\\',
    ): string {
        if ($windows) {
            $path = str_replace('\\', '/', $path);
            $base = str_replace('\\', '/', $base);
        }

        return self::isAbsolute($path) ? $path : $base . '/' . $path;
    }

    /**
     * The `TRACEPARENT` environment variable if it is a valid W3C Trace Context header (version 00)
     */
    public static function traceparent(): ?string
    {
        $value = getenv('TRACEPARENT');

        // version 00, and neither id is all zeros
        return is_string($value)
            && preg_match('/^00-(?!0{32}-)[0-9a-f]{32}-(?!0{16}-)[0-9a-f]{16}-[0-9a-f]{2}\z/', $value) === 1
            ? $value
            : null;
    }

    private static function addHook(string $handler, string $hook, int $calls, int $wall_ns): void
    {
        self::$hook_calls[$handler][$hook] = (self::$hook_calls[$handler][$hook] ?? 0) + $calls;
        self::$hook_ns[$handler][$hook] = (self::$hook_ns[$handler][$hook] ?? 0) + $wall_ns;
    }

    /**
     * The totals by handler and hook. An anonymous class is named by the path of its file, so they all show as one.
     *
     * @return array<string, HookRecord>
     */
    private static function hookTotals(): array
    {
        $totals = [];

        foreach (self::$hook_calls as $handler => $hooks) {
            $name = str_contains($handler, '@anonymous') ? '{anonymous-class}' : $handler;

            foreach ($hooks as $hook => $calls) {
                $key = $name . '::' . $hook;
                $total = $totals[$key] ?? ['handler' => $name, 'hook' => $hook, 'calls' => 0, 'wall_ns' => 0];
                $total['calls'] += $calls;
                $total['wall_ns'] += self::$hook_ns[$handler][$hook] ?? 0;
                $totals[$key] = $total;
            }
        }

        return $totals;
    }

    /** @return list<array{handler: string, hook: string, origin: string, calls: int, wall_us: int}> */
    private static function hookRecords(): array
    {
        $records = [];
        foreach (self::hookTotals() as $total) {
            $records[] = [
                'handler' => $total['handler'],
                'hook' => $total['hook'],
                'origin' => str_starts_with($total['handler'], 'Psalm\\Internal\\') ? 'psalm' : 'plugin',
                'calls' => $total['calls'],
                'wall_us' => intdiv($total['wall_ns'], 1_000),
            ];
        }

        usort(
            $records,
            static fn(array $a, array $b): int
                => [$b['wall_us'], $a['handler'], $a['hook']] <=> [$a['wall_us'], $b['handler'], $b['hook']],
        );

        return $records;
    }

    /** @return ParserCounts */
    private static function parserCounts(): array
    {
        return ['parses' => self::$parses, 'cache_hits' => self::$cache_hits];
    }

    /** @psalm-pure */
    private static function isAbsolute(string $path): bool
    {
        return $path !== ''
            && ($path[0] === '/'
                || $path[0] === '\\'
                || preg_match('~^(?:[A-Za-z]:[/\\\\]|[A-Za-z][A-Za-z0-9+.-]*://)~', $path) === 1);
    }

    /**
     * @param array{int, int, int} $start
     * @return FileRecord
     */
    private static function fileRecord(string $op, string $file_path, string $file_name, array $start): array
    {
        $end_ns = (int) hrtime(true);
        $start_us = intdiv($start[0] - self::$t0, 1_000);
        $end_us = intdiv($end_ns - self::$t0, 1_000);

        if (self::$worker) {
            self::$worker_end_us = max(self::$worker_end_us, $end_us);
        }

        return [
            'op' => $op,
            'path' => self::displayPath($file_path, $file_name),
            'pid' => self::$pid,
            'start_us' => $start_us,
            'wall_us' => $end_us - $start_us,
            'cpu_us' => self::cpuUs(0) - $start[1],
            'peak_growth_bytes' => memory_get_peak_usage() - $start[2],
        ];
    }

    /**
     * @template T of StatementRecord|FunctionRecord
     * @param array<string, T> $records by place
     * @return list<T> the one that took longest of its own first
     * @psalm-pure
     */
    private static function ranked(array $records): array
    {
        $places = array_keys($records);
        usort(
            $places,
            static fn(string $a, string $b): int => [$records[$b]['self_us'], $a] <=> [$records[$a]['self_us'], $b],
        );

        return array_map(static fn(string $place): array => $records[$place], $places);
    }

    /** @return array{name: string, t_us: int, cpu_us: int, children_cpu_us: int, bytes: int, peak: int} */
    private static function mark(string $name): array
    {
        return [
            'name' => $name,
            't_us' => self::nowUs(),
            'cpu_us' => self::cpuUs(0),
            'children_cpu_us' => self::cpuUs(1),
            'bytes' => memory_get_usage(),
            'peak' => memory_get_peak_usage(),
        ];
    }

    /** Microseconds since the last pool mark, which moves to now */
    private static function sincePoolMark(): int
    {
        $now_ns = (int) hrtime(true);
        $elapsed_us = intdiv($now_ns - self::$pool_mark_ns, 1_000);
        self::$pool_mark_ns = $now_ns;

        return $elapsed_us;
    }

    private static function nowUs(): int
    {
        return intdiv((int) hrtime(true) - self::$t0, 1_000);
    }

    /** User plus system CPU time in microseconds of this process (0) or of its waited-for children (1) */
    private static function cpuUs(int $who): int
    {
        // on Windows getrusage() gives the numbers of the process itself for its children too
        if ($who === 1 && PHP_OS_FAMILY === 'Windows') {
            return 0;
        }

        $usage = getrusage($who);

        return self::usageUs($usage, 'ru_utime') + self::usageUs($usage, 'ru_stime');
    }

    private static function sysUs(): int
    {
        return self::usageUs(getrusage(), 'ru_stime');
    }

    /** macOS reports the peak resident set in bytes, Linux in kibibytes */
    private static function maxRssBytes(): int
    {
        return (int) (getrusage()['ru_maxrss'] ?? 0) * (PHP_OS_FAMILY === 'Darwin' ? 1 : 1_024);
    }

    /**
     * @param array<array-key, mixed> $usage
     * @param 'ru_utime'|'ru_stime' $kind
     * @psalm-pure
     */
    private static function usageUs(array $usage, string $kind): int
    {
        return (int) ($usage[$kind . '.tv_sec'] ?? 0) * 1_000_000 + (int) ($usage[$kind . '.tv_usec'] ?? 0);
    }
}
