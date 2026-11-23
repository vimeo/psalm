<?php

declare(strict_types=1);

namespace Psalm\Internal\Perf;

use Closure;
use PhpParser\Node\Stmt;
use Psalm\StatementsSource;
use ReflectionFunction;

use function array_column;
use function array_pop;
use function array_shift;
use function array_splice;
use function array_values;
use function basename;
use function count;
use function dirname;
use function explode;
use function getcwd;
use function getenv;
use function getmypid;
use function getrusage;
use function hrtime;
use function implode;
use function intdiv;
use function is_file;
use function is_link;
use function max;
use function memory_get_peak_usage;
use function memory_get_usage;
use function memory_reset_peak_usage;
use function min;
use function preg_match;
use function readlink;
use function spl_object_id;
use function stat;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strtolower;
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
 *     mem_bytes: int,
 *     peak_growth_bytes: int,
 *     deep?: bool,
 *     storage?: string,
 *     issues?: int,
 * }
 * @psalm-type StatementRecord = array{path: string, line: int, node: string, self_us: int, wall_us: int}
 * @psalm-type FunctionRecord = array{path: string, line: int, id: string, pass: string, wall_us: int}
 * @psalm-type HookRecord = array{handler: string, hook: string, calls: int, wall_ns: int}
 * @psalm-type ParserCounts = array{files_parsed: int, parser_cache_hits: int, parser_cache_misses: int}
 * @psalm-type WorkerPerf = array{
 *     pid: int,
 *     role: string,
 *     start_us: int,
 *     end_us: int,
 *     cpu_us: int,
 *     cpu_sys_us: int,
 *     php_start_bytes: int,
 *     php_peak_bytes: int,
 *     max_rss_bytes: int,
 *     files: list<FileRecord>,
 *     statements: list<StatementRecord>,
 *     functions: list<FunctionRecord>,
 *     top_statements: list<StatementRecord>,
 *     top_functions: list<FunctionRecord>,
 *     hooks: list<HookRecord>,
 *     parser: ParserCounts,
 * }
 * @psalm-type StageRecord = array{
 *     name: string,
 *     start_us: int,
 *     wall_us: int,
 *     cpu_us: int,
 *     children_cpu_us: int,
 *     php_bytes: int,
 *     php_peak_bytes: int,
 * }
 * @psalm-type PoolRecord = array{
 *     id: int,
 *     role: string,
 *     workers: int,
 *     tasks: int,
 *     start_us: int,
 *     init_us: int,
 *     run_us: int,
 *     collect_us: int,
 *     merge_us: int,
 *     payload_bytes: array{sum: ?int, max: ?int},
 * }
 * @psalm-type WorkerRecord = array{
 *     pool: int,
 *     pid: int,
 *     role: string,
 *     start_us: int,
 *     end_us: int,
 *     tasks: int,
 *     busy_us: int,
 *     cpu_us: int,
 *     cpu_sys_us: int,
 *     php_start_bytes: int,
 *     php_peak_bytes: int,
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
 *         children_max_rss_bytes: int,
 *         files_scanned: int,
 *         files_analyzed: int,
 *         issues: array{errors: int, infos: int},
 *     },
 *     stages: list<StageRecord>,
 *     pools: list<PoolRecord>,
 *     workers: list<WorkerRecord>,
 *     files: list<FileRecord>,
 *     taint: array{nodes: int, sources: int, sinks: int, specializations: int, resolver_tasks: int},
 *     hot: array{threshold_us: int, statements: list<StatementRecord>, functions: list<FunctionRecord>},
 *     top: array{statements: list<StatementRecord>, functions: list<FunctionRecord>},
 *     hooks: list<array{handler: string, hook: string, origin: string, calls: int, wall_us: int}>,
 *     counts: ParserCounts,
 * }
 */
final class Collector
{
    /** Read by every hook: nothing else is evaluated when false */
    public static bool $enabled = false;

    /** Where the storage of the file being scanned came from, set by the scanner while collecting */
    public static string $scan_storage = '';

    /** Inclusive nanoseconds of the statements analysed inside the statement being timed */
    public static int $child_ns = 0;

    public const HOT_THRESHOLD_US = 50_000;

    private const TOP_COUNT = 20;

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

    /** Statements whose self time is below this are neither hot nor in the top list */
    private static int $statement_floor_ns = 0;

    /** @var list<array{name: string, t_us: int, cpu_us: int, children_cpu_us: int, bytes: int, peak: int}> */
    private static array $marks = [];

    /** @var list<FileRecord> */
    private static array $files = [];

    /** @var list<StatementRecord> */
    private static array $hot_statements = [];

    /** @var list<FunctionRecord> */
    private static array $hot_functions = [];

    /** @var list<array{int, StatementRecord}> the value that ranks a statement, and the statement */
    private static array $top_statements = [];

    /** @var list<array{int, FunctionRecord}> */
    private static array $top_functions = [];

    /** @var array<string, array<string, int>> calls of the handlers, by handler class and hook */
    private static array $hook_calls = [];

    /** @var array<string, array<string, int>> nanoseconds in the handlers, by handler class and hook */
    private static array $hook_ns = [];

    /** @var array<int, string> the class of a handler closure, by the id of the closure */
    private static array $closure_classes = [];

    /** @var list<Closure> the closures in $closure_classes: while they live, their ids are not given again */
    private static array $closures = [];

    private static int $files_parsed = 0;

    private static int $parser_cache_hits = 0;

    private static int $parser_cache_misses = 0;

    /** @var list<PoolRecord> */
    private static array $pools = [];

    /** @var list<WorkerRecord> */
    private static array $workers = [];

    /** @var ?PoolRecord */
    private static ?array $pool = null;

    private static int $pool_mark_ns = 0;

    private static ?int $payload_sum = null;

    private static int $payload_max = 0;

    /** @var array{nodes: int, sources: int, sinks: int, specializations: int, resolver_tasks: int} */
    private static array $taint = ['nodes' => 0, 'sources' => 0, 'sinks' => 0, 'specializations' => 0,
        'resolver_tasks' => 0];

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
        self::updateStatementFloor();
    }

    public static function reset(): void
    {
        self::$enabled = false;
        self::$worker = false;
        self::$worker_end_us = 0;
        self::$scan_storage = '';
        self::$stage = '';
        self::$child_ns = 0;
        self::$dump_path = null;
        self::$option_names = [];
        self::$marks = [];
        self::$files = [];
        self::$hot_statements = [];
        self::$hot_functions = [];
        self::$top_statements = [];
        self::$top_functions = [];
        self::$pools = [];
        self::$workers = [];
        self::$pool = null;
        self::$payload_sum = null;
        self::$payload_max = 0;
        self::$hook_calls = [];
        self::$hook_ns = [];
        self::$closure_classes = [];
        self::$closures = [];
        self::$files_parsed = 0;
        self::$parser_cache_hits = 0;
        self::$parser_cache_misses = 0;
        self::$taint = ['nodes' => 0, 'sources' => 0, 'sinks' => 0, 'specializations' => 0, 'resolver_tasks' => 0];
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

        return $previous;
    }

    /** @return array{int, int, int, int} */
    public static function fileStart(): array
    {
        return [(int) hrtime(true), self::cpuUs(0), memory_get_usage(), memory_get_peak_usage()];
    }

    /**
     * @param string $file_path the path of the file, as it is on disk
     * @param string $file_name the path of the file as Psalm prints it (see Config::shortenFileName())
     * @param array{int, int, int, int} $start
     */
    public static function scanEnd(
        string $file_path,
        string $file_name,
        array $start,
        bool $deep,
        string $storage,
    ): void {
        self::$files[] = self::fileRecord('scan', $file_path, $file_name, $start)
            + ['deep' => $deep, 'storage' => $storage];
    }

    /** @param array{int, int, int, int} $start */
    public static function analysisEnd(string $file_path, string $file_name, array $start, int $issues): void
    {
        self::$files[] = self::fileRecord('analysis', $file_path, $file_name, $start) + ['issues' => $issues];
    }

    public static function statementEnd(StatementsSource $source, Stmt $stmt, int $start_ns, int $outer_child_ns): void
    {
        $inclusive_ns = (int) hrtime(true) - $start_ns;
        $self_ns = $inclusive_ns - self::$child_ns;
        self::$child_ns = $outer_child_ns + $inclusive_ns;

        if ($self_ns < self::$statement_floor_ns) {
            return;
        }

        $record = [
            'path' => self::displayPath($source->getFilePath(), $source->getFileName()),
            'line' => $stmt->getStartLine(),
            'node' => $stmt->getType(),
            'self_us' => intdiv($self_ns, 1_000),
            'wall_us' => intdiv($inclusive_ns, 1_000),
        ];

        if ($record['self_us'] >= self::HOT_THRESHOLD_US) {
            self::$hot_statements[] = $record;
        }

        self::$top_statements = self::insertTop(self::$top_statements, $record, $record['self_us']);
        self::updateStatementFloor();
    }

    public static function functionEnd(
        string $file_path,
        string $file_name,
        int $line,
        string $id,
        bool $initializations,
        int $start_ns,
    ): void {
        $wall_us = intdiv((int) hrtime(true) - $start_ns, 1_000);
        $record = [
            'path' => self::displayPath($file_path, $file_name),
            'line' => $line,
            'id' => $id,
            'pass' => $initializations ? 'initializations' : 'body',
            'wall_us' => $wall_us,
        ];

        if ($wall_us >= self::HOT_THRESHOLD_US) {
            self::$hot_functions[] = $record;
        }

        self::$top_functions = self::insertTop(self::$top_functions, $record, $wall_us);
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
        self::$files = [];
        self::$hot_statements = [];
        self::$hot_functions = [];
        self::$top_statements = [];
        self::$top_functions = [];
        self::$hook_calls = [];
        self::$hook_ns = [];
        self::$files_parsed = 0;
        self::$parser_cache_hits = 0;
        self::$parser_cache_misses = 0;
        self::$child_ns = 0;
        self::updateStatementFloor();
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
            'php_peak_bytes' => memory_get_peak_usage(),
            'max_rss_bytes' => self::maxRssBytes(0),
            'files' => self::$files,
            'statements' => self::$hot_statements,
            'functions' => self::$hot_functions,
            'top_statements' => array_column(self::$top_statements, 1),
            'top_functions' => array_column(self::$top_functions, 1),
            'hooks' => array_values(self::hookTotals()),
            'parser' => self::parserCounts(),
        ];
    }

    public static function poolStart(string $role, int $workers, int $tasks): void
    {
        if (!self::$enabled) {
            return;
        }

        self::$pool = [
            'id' => count(self::$pools) + 1,
            'role' => $role,
            'workers' => $workers,
            'tasks' => $tasks,
            'start_us' => self::nowUs(),
            'init_us' => 0,
            'run_us' => 0,
            'collect_us' => 0,
            'merge_us' => 0,
            'payload_bytes' => ['sum' => null, 'max' => null],
        ];
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

        foreach ($data['statements'] as $record) {
            self::$hot_statements[] = $record;
        }

        foreach ($data['functions'] as $record) {
            self::$hot_functions[] = $record;
        }

        foreach ($data['top_statements'] as $record) {
            self::$top_statements = self::insertTop(self::$top_statements, $record, $record['self_us']);
        }

        foreach ($data['top_functions'] as $record) {
            self::$top_functions = self::insertTop(self::$top_functions, $record, $record['wall_us']);
        }

        foreach ($data['hooks'] as $hook) {
            self::addHook($hook['handler'], $hook['hook'], $hook['calls'], $hook['wall_ns']);
        }

        self::$files_parsed += $data['parser']['files_parsed'];
        self::$parser_cache_hits += $data['parser']['parser_cache_hits'];
        self::$parser_cache_misses += $data['parser']['parser_cache_misses'];

        self::$workers[] = [
            'pool' => self::$pool['id'] ?? 0,
            'pid' => $data['pid'],
            'role' => $data['role'],
            'start_us' => $data['start_us'],
            'end_us' => $data['end_us'],
            'tasks' => $tasks,
            'busy_us' => $busy_us,
            'cpu_us' => $data['cpu_us'],
            'cpu_sys_us' => $data['cpu_sys_us'],
            'php_start_bytes' => $data['php_start_bytes'],
            'php_peak_bytes' => $data['php_peak_bytes'],
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

        if (self::$payload_sum !== null) {
            self::$pool['payload_bytes'] = ['sum' => self::$payload_sum, 'max' => self::$payload_max];
        }

        self::$pools[] = self::$pool;
        self::$pool = null;
    }

    /**
     * Calls the handlers of an event as EventDispatcher does, and times each call
     *
     * @param array<class-string> $handlers
     */
    public static function dispatchVoid(array $handlers, string $method, object $event, string $hook): void
    {
        foreach ($handlers as $handler) {
            $start = (int) hrtime(true);
            [$handler, $method]($event);
            $ns = (int) hrtime(true) - $start;

            self::$hook_calls[$handler][$hook] ??= 0;
            ++self::$hook_calls[$handler][$hook];
            self::$hook_ns[$handler][$hook] ??= 0;
            self::$hook_ns[$handler][$hook] += $ns;
        }
    }

    /**
     * Like dispatchVoid(), but a handler that returns false ends it with false
     *
     * @param array<class-string> $handlers
     */
    public static function dispatchUntilFalse(array $handlers, string $method, object $event, string $hook): ?bool
    {
        foreach ($handlers as $handler) {
            $start = (int) hrtime(true);
            /** @var ?bool $result */
            $result = [$handler, $method]($event);
            $ns = (int) hrtime(true) - $start;

            self::$hook_calls[$handler][$hook] ??= 0;
            ++self::$hook_calls[$handler][$hook];
            self::$hook_ns[$handler][$hook] ??= 0;
            self::$hook_ns[$handler][$hook] += $ns;

            if ($result === false) {
                return false;
            }
        }

        return null;
    }

    /**
     * Like dispatchVoid(), but the results are the bits of the handlers together
     *
     * @param array<class-string> $handlers
     */
    public static function dispatchOr(array $handlers, string $method, object $event, string $hook): int
    {
        $bits = 0;

        foreach ($handlers as $handler) {
            $start = (int) hrtime(true);
            /** @var int $result */
            $result = [$handler, $method]($event);
            $ns = (int) hrtime(true) - $start;

            self::$hook_calls[$handler][$hook] ??= 0;
            ++self::$hook_calls[$handler][$hook];
            self::$hook_ns[$handler][$hook] ??= 0;
            self::$hook_ns[$handler][$hook] += $ns;

            $bits |= $result;
        }

        return $bits;
    }

    /**
     * Like dispatchVoid(), but the first result that is not null ends it
     *
     * @param array<class-string> $handlers
     */
    public static function dispatchFirst(
        array $handlers,
        string $method,
        object $event,
        string $hook,
    ): object|bool|null {
        foreach ($handlers as $handler) {
            $start = (int) hrtime(true);
            /** @var object|bool|null $result */
            $result = [$handler, $method]($event);
            $ns = (int) hrtime(true) - $start;

            self::$hook_calls[$handler][$hook] ??= 0;
            ++self::$hook_calls[$handler][$hook];
            self::$hook_ns[$handler][$hook] ??= 0;
            self::$hook_ns[$handler][$hook] += $ns;

            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Calls a handler that a provider registry holds, and times the call
     *
     * @template TEvent of object
     * @template TResult
     * @param Closure(TEvent): TResult $handler
     * @param TEvent $event
     * @return TResult
     */
    public static function call(Closure $handler, object $event, string $hook): mixed
    {
        $start = (int) hrtime(true);
        $result = $handler($event);
        $ns = (int) hrtime(true) - $start;
        $class = self::$closure_classes[spl_object_id($handler)] ?? self::closureClass($handler);

        self::$hook_calls[$class][$hook] ??= 0;
        ++self::$hook_calls[$class][$hook];
        self::$hook_ns[$class][$hook] ??= 0;
        self::$hook_ns[$class][$hook] += $ns;

        return $result;
    }

    /** A file the parser parsed; $cache_missed if a parser cache was asked first */
    public static function parsed(bool $cache_missed): void
    {
        ++self::$files_parsed;
        self::$parser_cache_misses += $cache_missed ? 1 : 0;
    }

    public static function parserCacheHit(): void
    {
        ++self::$parser_cache_hits;
    }

    public static function taint(int $nodes, int $sources, int $sinks, int $specializations): void
    {
        if (self::$enabled) {
            self::$taint = [
                'nodes' => $nodes,
                'sources' => $sources,
                'sinks' => $sinks,
                'specializations' => $specializations,
                'resolver_tasks' => 0,
            ];
        }
    }

    /** A round of the taint resolution */
    public static function taintRound(): void
    {
        ++self::$taint['resolver_tasks'];
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
                'php_bytes' => $next['bytes'],
                'php_peak_bytes' => $next['peak'],
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

        $statements = self::$hot_statements;
        usort(
            $statements,
            static fn(array $a, array $b): int
                => [$b['self_us'], $a['path'], $a['line']] <=> [$a['self_us'], $b['path'], $b['line']],
        );
        $functions = self::$hot_functions;
        usort(
            $functions,
            static fn(array $a, array $b): int
                => [$b['wall_us'], $a['path'], $a['line']] <=> [$a['wall_us'], $b['path'], $b['line']],
        );

        return [
            'totals' => [
                'wall_us' => $end['t_us'] - $first['t_us'],
                'cpu_us' => $end['cpu_us'] - $first['cpu_us'],
                'cpu_sys_us' => self::sysUs() - self::$start_sys_us,
                'children_cpu_us' => $end['children_cpu_us'] - $first['children_cpu_us'],
                'php_peak_bytes' => $php_peak_bytes ?? $end['peak'],
                'max_rss_bytes' => self::maxRssBytes(0),
                'children_max_rss_bytes' => self::maxRssBytes(1),
                'files_scanned' => $scanned,
                'files_analyzed' => $analyzed,
                'issues' => ['errors' => $errors, 'infos' => $infos],
            ],
            'stages' => $stages,
            'pools' => self::$pools,
            'workers' => self::$workers,
            'files' => $files,
            'taint' => self::$taint,
            'hot' => [
                'threshold_us' => self::HOT_THRESHOLD_US,
                'statements' => $statements,
                'functions' => $functions,
            ],
            'top' => [
                'statements' => array_column(self::$top_statements, 1),
                'functions' => array_column(self::$top_functions, 1),
            ],
            'hooks' => self::hookRecords(),
            'counts' => self::parserCounts(),
        ];
    }

    /**
     * The path of a file as the dump shows it: as Psalm prints it (see Config::shortenFileName()), except that a
     * file of Psalm's own installation outside the project is `<psalm>/<path in Psalm>` and any other path that
     * is still absolute, or inside a phar, is `<external>/<file name>`: the dump names no directory above the
     * project that Psalm didn't print itself.
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
            return $in_psalm ? '<psalm>/' . substr($path, strlen($root) + 1) : '<external>/' . basename($path);
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
     * Whether two paths name the same file or, if there is none yet, the same place for it: through a symlinked
     * directory, a symlink that points where the file will be, `..` after a symlink, a different case on a
     * case-insensitive file system, or a hard link.
     */
    public static function isSameDestination(string $a, string $b, string $base): bool
    {
        $a = self::absolutePath($a, $base);
        $b = self::absolutePath($b, $base);

        return self::isSameFile($a, $b) || self::canonicalDestination($a) === self::canonicalDestination($b);
    }

    /** Whether two paths are one file that exists: a link, a hard link, or a name the file system maps to one */
    public static function isSameFile(string $a, string $b): bool
    {
        if (!is_file($a) || !is_file($b)) {
            return false;
        }

        $stat_a = @stat($a);
        $stat_b = @stat($b);

        return $stat_a !== false
            && $stat_b !== false
            && $stat_a['ino'] !== 0
            && $stat_a['ino'] === $stat_b['ino']
            && $stat_a['dev'] === $stat_b['dev'];
    }

    /**
     * @param list<string> $others
     * @return ?string the first of them that is the file at $path
     */
    public static function sameFileAs(string $path, array $others): ?string
    {
        foreach ($others as $other) {
            if (self::isSameFile($path, $other)) {
                return $other;
            }
        }

        return null;
    }

    /**
     * The `TRACEPARENT` environment variable if it is a valid W3C Trace Context header (version 00)
     */
    public static function traceparent(): ?string
    {
        $value = getenv('TRACEPARENT');

        if ($value === false
            || preg_match('/^00-(?!0{32}-)[0-9a-f]{32}-(?!0{16}-)[0-9a-f]{16}-[0-9a-f]{2}\z/', $value) !== 1
        ) {
            return null;
        }

        return $value;
    }

    /**
     * The path with every symlink in it followed, the last one included even if its target is missing, and
     * lowercase where the file system ignores case
     */
    private static function canonicalDestination(string $path): string
    {
        $windows = DIRECTORY_SEPARATOR === '\\';
        [$prefix, $segments] = self::splitRoot($windows ? str_replace('\\', '/', $path) : $path, $windows);
        $resolved = [];
        $followed = 0;

        while ($segments !== []) {
            $segment = array_shift($segments);

            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($resolved);
                continue;
            }

            $candidate = $prefix . '/' . implode('/', [...$resolved, $segment]);
            $target = $followed < 40 && is_link($candidate) ? readlink($candidate) : false;

            if ($target === false) {
                $resolved[] = $segment;
                continue;
            }

            ++$followed;
            $target = $windows ? str_replace('\\', '/', $target) : $target;

            if (self::isAbsolute($target)) {
                [$prefix, $target_segments] = self::splitRoot($target, $windows);
                $resolved = [];
            } else {
                $target_segments = explode('/', $target);
            }

            $segments = [...$target_segments, ...$segments];
        }

        $canonical = $prefix . '/' . implode('/', $resolved);

        return PHP_OS_FAMILY === 'Darwin' || PHP_OS_FAMILY === 'Windows' ? strtolower($canonical) : $canonical;
    }

    /**
     * @return array{string, list<string>} the drive or `/` of a network share, and the segments after it
     * @psalm-pure
     */
    private static function splitRoot(string $path, bool $windows): array
    {
        if (preg_match('~^[A-Za-z]:~', $path) === 1) {
            return [substr($path, 0, 2), explode('/', substr($path, 2))];
        }

        // \\server\share
        if ($windows && str_starts_with($path, '//') && !str_starts_with($path, '///')) {
            return ['/', explode('/', $path)];
        }

        return ['', explode('/', $path)];
    }

    private static function closureClass(Closure $closure): string
    {
        // the class the closure was made for, not the one that declares its method
        $name = (new ReflectionFunction($closure))->getClosureCalledClass()?->name ?? '{closure}';
        self::$closures[] = $closure;
        self::$closure_classes[spl_object_id($closure)] = $name;

        return $name;
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
                'origin' => str_starts_with($total['handler'], 'Psalm\\') ? 'psalm' : 'plugin',
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
        return [
            'files_parsed' => self::$files_parsed,
            'parser_cache_hits' => self::$parser_cache_hits,
            'parser_cache_misses' => self::$parser_cache_misses,
        ];
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
     * @param array{int, int, int, int} $start
     * @return FileRecord
     */
    private static function fileRecord(string $op, string $file_path, string $file_name, array $start): array
    {
        $end_ns = (int) hrtime(true);
        $start_us = intdiv($start[0] - self::$t0, 1_000);
        $wall_us = intdiv($end_ns - $start[0], 1_000);

        if (self::$worker) {
            self::$worker_end_us = max(self::$worker_end_us, $start_us + $wall_us);
        }

        return [
            'op' => $op,
            'path' => self::displayPath($file_path, $file_name),
            'pid' => self::$pid,
            'start_us' => $start_us,
            'wall_us' => $wall_us,
            'cpu_us' => self::cpuUs(0) - $start[1],
            'mem_bytes' => memory_get_usage() - $start[2],
            'peak_growth_bytes' => memory_get_peak_usage() - $start[3],
        ];
    }

    /**
     * Keeps the TOP_COUNT records with the greatest value, in descending order; a record that doesn't beat the
     * smallest of a full list is dropped.
     *
     * @template T
     * @param list<array{int, T}> $top
     * @param T $record
     * @return list<array{int, T}>
     * @psalm-pure
     */
    private static function insertTop(array $top, array $record, int $value): array
    {
        $count = count($top);

        if ($count >= self::TOP_COUNT && $top[$count - 1][0] >= $value) {
            return $top;
        }

        $position = $count;
        while ($position > 0 && $top[$position - 1][0] < $value) {
            --$position;
        }

        array_splice($top, $position, 0, [[$value, $record]]);

        if ($count >= self::TOP_COUNT) {
            array_pop($top);
        }

        return $top;
    }

    private static function updateStatementFloor(): void
    {
        $floor_us = count(self::$top_statements) < self::TOP_COUNT
            ? 0
            : min(self::HOT_THRESHOLD_US, self::$top_statements[self::TOP_COUNT - 1][0]);

        self::$statement_floor_ns = $floor_us * 1_000;
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
    private static function maxRssBytes(int $who): int
    {
        if ($who === 1 && PHP_OS_FAMILY === 'Windows') {
            return 0;
        }

        return (int) (getrusage($who)['ru_maxrss'] ?? 0) * (PHP_OS_FAMILY === 'Darwin' ? 1 : 1_024);
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
