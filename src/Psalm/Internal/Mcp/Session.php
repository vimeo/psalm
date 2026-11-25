<?php

declare(strict_types=1);

namespace Psalm\Internal\Mcp;

use InvalidArgumentException;
use LanguageServerProtocol\Position;
use LanguageServerProtocol\Range;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Config;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\BaselineMatcher;
use Psalm\Internal\CacheMode;
use Psalm\Internal\Cli\ProjectBootstrap;
use Psalm\Internal\Cli\Psalm;
use Psalm\Internal\CliUtils;
use Psalm\Internal\Composer;
use Psalm\Internal\LanguageServer\PHPMarkdownContent;
use Psalm\Internal\LanguageServer\Provider\InMemoryProjectCacheProvider;
use Psalm\Internal\LanguageServer\Reference;
use Psalm\Internal\Provider\ClassLikeStorageCacheProvider;
use Psalm\Internal\Provider\FileProvider;
use Psalm\Internal\Provider\FileReferenceCacheProvider;
use Psalm\Internal\Provider\FileStorageCacheProvider;
use Psalm\Internal\Provider\ParserCacheProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\Event\AfterCodebasePopulatedEvent;
use Psalm\Progress\VoidProgress;
use Psalm\Report\ConsoleReport;
use Psalm\Report\ReportOptions;
use Throwable;

use function array_keys;
use function array_slice;
use function clearstatcache;
use function count;
use function explode;
use function fclose;
use function filemtime;
use function filesize;
use function hash_file;
use function implode;
use function is_dir;
use function is_file;
use function is_resource;
use function is_string;
use function ksort;
use function ltrim;
use function preg_replace;
use function proc_close;
use function proc_open;
use function realpath;
use function rtrim;
use function str_repeat;
use function str_replace;
use function str_starts_with;
use function stream_get_contents;
use function strlen;
use function strpos;
use function strrpos;
use function strtolower;
use function substr;
use function substr_count;
use function time;
use function trim;
use function usort;

use const DIRECTORY_SEPARATOR;

/**
 * What the MCP tools answer from: the project's codebase, kept in memory and in server mode like the
 * language server keeps it, and brought up to date with the files on disk before each tool call
 *
 * @internal
 */
final class Session
{
    private const MAX_REFERENCES = 200;

    private readonly Codebase $codebase;

    private readonly Config $config;

    private bool $ready = false;

    /**
     * The modification time, size and hash of each project file, and when they were read
     *
     * @var array<string, array{int, int, string, int}>
     */
    private array $file_states = [];

    /**
     * The project files under their path keys, see getPathKey()
     *
     * @var array<string, string>
     */
    private array $file_keys = [];

    /**
     * Counts the changes to the project files, so that a file analysed before one of them is analysed again
     */
    private int $generation = 0;

    /**
     * The generation each file was last analysed in
     *
     * @var array<string, int>
     */
    private array $analyzed_in = [];

    /**
     * @param array<string,array<string,array{o:int, s: list<string>}>> $issue_baseline
     * @param list<string> $psalm_command the `psalm` run that checks the whole project
     * @psalm-capabilities read-props
     */
    public function __construct(
        private readonly ProjectAnalyzer $project_analyzer,
        private readonly array $issue_baseline,
        private readonly array $psalm_command,
        private readonly string $base_dir,
    ) {
        $this->codebase = $project_analyzer->getCodebase();
        $this->config = $this->codebase->config;
    }

    /**
     * @param array<string,string|false|list<string|false>> $options
     * @param list<string> $psalm_command
     */
    public static function create(Config $config, string $base_dir, array $options, array $psalm_command): self
    {
        // in memory, so that this server and a `psalm` run in the same project don't share a cache
        $lock_file = Composer::getLockFile($base_dir);
        $providers = new Providers(
            new FileProvider,
            new ParserCacheProvider($config, $lock_file, CacheMode::Memory),
            new FileStorageCacheProvider($config, $lock_file, CacheMode::Memory),
            new ClassLikeStorageCacheProvider($config, $lock_file, CacheMode::Memory),
            new FileReferenceCacheProvider($config, $lock_file, CacheMode::Memory),
            new InMemoryProjectCacheProvider,
        );

        $progress = new VoidProgress();

        $codebase = new Codebase($config, $providers, $progress);
        $codebase->language_server = true;
        $codebase->collectLocations();

        if ($config->find_unused_variables) {
            $codebase->reportUnusedVariables();
        }

        if ($config->find_unused_code) {
            $codebase->reportUnusedCode();
        }

        $threads = Psalm::getThreads($options, $config, false);

        $project_analyzer = new ProjectAnalyzer(
            $config,
            $providers,
            null,
            [],
            $threads,
            Psalm::getThreads($options, $config, true),
            $progress,
            $codebase,
        );

        CliUtils::initPhpVersion($options, $config, $project_analyzer);

        $issue_baseline = [];

        if (!isset($options['ignore-baseline'])) {
            $baseline_file = isset($options['use-baseline']) && is_string($options['use-baseline'])
                ? $options['use-baseline']
                : $config->error_baseline;

            if ($baseline_file !== null) {
                $issue_baseline = ProjectBootstrap::readBaselineFile($baseline_file);
            }
        }

        return new self($project_analyzer, $issue_baseline, $psalm_command, $base_dir);
    }

    /**
     * The issues Psalm reports in the given files, or in the whole project when no path is given
     *
     * @param list<string> $paths
     */
    public function check(array $paths, bool $show_info): string
    {
        if ($paths === []) {
            return $this->checkProject($show_info);
        }

        $this->sync();

        $files = [];
        foreach ($paths as $path) {
            foreach ($this->resolveProjectFiles($path) as $file_path) {
                $files[$file_path] = $file_path;
            }
        }

        $this->analyze($files);

        $issue_baseline = $this->issue_baseline;
        $issues = [];

        foreach (IssueBuffer::clear() as $file_path => $file_issues) {
            if (!isset($files[$file_path])) {
                continue;
            }

            foreach ($file_issues as $issue_data) {
                if (BaselineMatcher::consume(
                    $this->config,
                    $issue_baseline,
                    $issue_data->file_name,
                    $issue_data->type,
                    $issue_data->selected_text,
                )) {
                    continue;
                }

                $issues[] = $issue_data;
            }
        }

        $report_options = new ReportOptions();
        $report_options->use_color = false;
        $report_options->show_info = $show_info;

        $report = (new ConsoleReport($issues, [], $report_options))->create();

        $count = 0;
        foreach ($issues as $issue_data) {
            if ($show_info || $issue_data->severity !== IssueData::SEVERITY_INFO) {
                $count++;
            }
        }

        $summary = $count === 0
            ? 'No issues found in ' . self::countFiles(count($files)) . '.'
            : $count . ' issue' . ($count === 1 ? '' : 's') . ' found in ' . self::countFiles(count($files)) . '.';

        return rtrim($report) . ($report === '' ? '' : "\n\n") . $summary
            . ' Unused classes and methods and taint paths between files are only reported when the whole project'
            . ' is checked (call this tool without paths).';
    }

    /**
     * What Psalm knows about the code at a position: the type of each expression there, and the declaration
     * and location of the class, function, method, property or constant it refers to
     */
    public function inspect(string $file, int $line, ?int $column, ?string $text): string
    {
        $file_path = $this->resolveFile($file);
        $this->analyzeForQuery($file_path);

        [$line_start, $line_end] = $this->getLineBounds($file_path, $line);
        [$from, $to] = $this->getSpan($file_path, $line, $line_start, $line_end, $column, $text);

        [$reference_map, $type_map] = $this->codebase->analyzer->getMapsForFile($file_path);
        $reference_map += $this->getDeclarationMap($file_path);

        $contents = $this->codebase->getFileContents($file_path);

        /** @var array<string, array{start: int, end: int, type: ?string, symbol: ?string}> $spans */
        $spans = [];

        foreach ($this->getEntries($type_map, $reference_map, $from, $to, $line_start, $line_end) as $entry) {
            [$start, $end, $value, $is_type] = $entry;

            $key = $start . ':' . $end;
            $spans[$key] ??= ['start' => $start, 'end' => $end, 'type' => null, 'symbol' => null];

            if ($is_type) {
                $spans[$key]['type'] = $value;
            } elseif (self::isExpressionSymbol($value)) {
                // an assigned value, recorded as `start-end:type`
                $spans[$key]['type'] ??= (string) preg_replace('/^[^:]*:/', '', $value);
            } else {
                $spans[$key]['symbol'] = $value;
            }
        }

        $output = [];

        foreach ($spans as $span) {
            $item = '`' . self::shorten(substr($contents, $span['start'], $span['end'] - $span['start'])) . '` at '
                . $this->formatPosition($contents, $span['start']);

            if ($span['type'] !== null) {
                $item .= "\n  type: " . $span['type'];
            }

            if ($span['symbol'] !== null) {
                $item .= "\n  symbol: " . $this->describeSymbol($file_path, $span['symbol']);
            }

            $output[] = $item;
        }

        if ($output === []) {
            return 'Psalm has no type or symbol at ' . $this->config->shortenFileName($file_path) . ':' . $line
                . ($column !== null ? ':' . $column : '') . '.';
        }

        return implode("\n\n", $output);
    }

    /**
     * Where a class, function, method, property or class constant is used in the project
     */
    public function references(
        ?string $symbol,
        ?string $file,
        ?int $line,
        ?int $column,
        ?string $text,
    ): string {
        if ($symbol === null) {
            if ($file === null || $line === null) {
                throw new ToolError('Give either `symbol` or `file` and `line` (with `column` or `text`).');
            }

            $symbol = $this->getSymbolAt($file, $line, $column, $text);
        } else {
            $this->sync();
        }

        $symbol = (string) preg_replace('/\(\)$/', '', ltrim(trim($symbol), '\\'));

        if ($symbol === '') {
            throw new ToolError('`symbol` is empty.');
        }

        $this->analyzeProject();

        $locations = $this->codebase->findReferencesToSymbol($symbol);

        if ($locations === []) {
            return 'No references to ' . $symbol . ' found. Names must be fully qualified: `App\Foo`,'
                . ' `App\Foo::bar` for a method, `App\Foo::$bar` for a property, `App\Foo::BAR` for a constant,'
                . ' `App\foo` for a function.';
        }

        $output = [];
        foreach (array_slice($locations, 0, self::MAX_REFERENCES) as $location) {
            $output[] = $location->file_name . ':' . $location->getLineNumber() . ':' . $location->getColumn()
                . ' ' . trim(self::shorten($location->getSnippet()));
        }

        $count = count($locations);

        return $count . ' reference' . ($count === 1 ? '' : 's') . ' to ' . $symbol . ':' . "\n"
            . implode("\n", $output)
            . ($count > self::MAX_REFERENCES ? "\n(" . ($count - self::MAX_REFERENCES) . ' more not shown)' : '');
    }

    private function getSymbolAt(string $file, int $line, ?int $column, ?string $text): string
    {
        $file_path = $this->resolveFile($file);
        $this->analyzeForQuery($file_path);

        [$line_start, $line_end] = $this->getLineBounds($file_path, $line);
        [$from, $to] = $this->getSpan($file_path, $line, $line_start, $line_end, $column, $text);

        [$reference_map] = $this->codebase->analyzer->getMapsForFile($file_path);
        $reference_map += $this->getDeclarationMap($file_path);

        foreach ($this->getEntries([], $reference_map, $from, $to, $line_start, $line_end) as [, , $symbol]) {
            if (str_starts_with($symbol, '*') || self::isExpressionSymbol($symbol)) {
                continue;
            }

            // the title has the declared case of a method's name, the symbol has it lowercased
            try {
                $markup = $this->codebase->getMarkupContentForSymbolByReference(
                    new Reference($file_path, $symbol, new Range(new Position(0, 0), new Position(0, 0))),
                );
            } catch (Throwable) {
                $markup = null;
            }

            return $markup instanceof PHPMarkdownContent && $markup->title !== null ? $markup->title : $symbol;
        }

        throw new ToolError('No class, function, method, property or constant is used there. Point `column` or'
            . ' `text` at its name, or pass its fully qualified name as `symbol`.');
    }

    private function describeSymbol(string $file_path, string $symbol): string
    {
        if (str_starts_with($symbol, '*')) {
            return substr($symbol, 1) . ' (Psalm can\'t find it)';
        }

        $reference = new Reference($file_path, $symbol, new Range(new Position(0, 0), new Position(0, 0)));

        try {
            $markup = $this->codebase->getMarkupContentForSymbolByReference($reference);
            $location = $this->codebase->getSymbolLocationByReference($reference);
        } catch (Throwable) {
            return $symbol;
        }

        // the title has the declared case of a method's name, the symbol has it lowercased
        $description = $markup instanceof PHPMarkdownContent && $markup->title !== null ? $markup->title : $symbol;

        if ($location !== null) {
            $description .= ', declared at ' . $location->file_name . ':' . $location->getLineNumber() . ':'
                . $location->getColumn();
        }

        if ($markup instanceof PHPMarkdownContent) {
            $description .= "\n" . self::indent(trim($markup->code), 4);

            if ($markup->description !== null && trim($markup->description) !== '') {
                $description .= "\n" . self::indent(trim($markup->description), 4);
            }
        }

        return $description;
    }

    /**
     * The names of the classes, functions, methods, properties and constants declared in the file, as reference
     * map entries, since the reference map has only their uses
     *
     * @return array<int, array{0: int, 1: string}>
     */
    private function getDeclarationMap(string $file_path): array
    {
        try {
            $file_storage = $this->codebase->file_storage_provider->get($file_path);
        } catch (InvalidArgumentException) {
            return [];
        }

        $map = [];
        $add = /**
         * @psalm-capabilities read-props|write-this-props|write-refs
         */
        static function (?CodeLocation $location, string $symbol) use (&$map, $file_path): void {
            if ($location !== null && $location->file_path === $file_path) {
                [$start, $end] = $location->getSelectionBounds();
                $map[$start] = [$end, $symbol];
            }
        };

        foreach ($file_storage->classlikes_in_file as $fq_class_name_lc => $_) {
            try {
                $storage = $this->codebase->classlike_storage_provider->get($fq_class_name_lc);
            } catch (InvalidArgumentException) {
                continue;
            }

            $add($storage->location, $storage->name);

            // lowercase, as in the reference map
            foreach ($storage->methods as $method_name_lc => $method_storage) {
                $add($method_storage->location, $storage->name . '::' . $method_name_lc . '()');
            }

            foreach ($storage->properties as $property_name => $property_storage) {
                $add($property_storage->location, $storage->name . '::$' . $property_name);
            }

            foreach ($storage->constants as $constant_name => $constant_storage) {
                $add($constant_storage->location, $storage->name . '::' . $constant_name);
            }
        }

        foreach ($file_storage->functions as $function_id => $function_storage) {
            $cased_name = (string) $function_storage->cased_name;
            $add($function_storage->location, substr($function_id, 0, -strlen($cased_name)) . $cased_name . '()');
        }

        return $map;
    }

    /**
     * The entries of the type and reference maps at the span, innermost last, or those that start on the line
     * when no span is given
     *
     * @param array<int, array{0: int, 1: string}> $type_map
     * @param array<int, array{0: int, 1: string}> $reference_map
     * @return list<array{int, int, string, bool}> start, end, type or symbol, whether it is a type
     * @psalm-pure
     */
    private function getEntries(
        array $type_map,
        array $reference_map,
        ?int $from,
        ?int $to,
        int $line_start,
        int $line_end,
    ): array {
        $entries = [];

        foreach ([[$type_map, true], [$reference_map, false]] as [$map, $is_type]) {
            foreach ($map as $start => [$end, $value]) {
                if ($from === null || $to === null) {
                    $matches = $start >= $line_start && $start < $line_end;
                } else {
                    // ends are exclusive, but the language server treats them as inclusive too
                    $matches = $start < $to && $end >= $from && $start >= $line_start;
                }

                if ($matches) {
                    $entries[$start . ':' . $end . ':' . ($is_type ? 't' : 'r')] = [$start, $end, $value, $is_type];
                }
            }
        }

        // outermost first, so that what the agent pointed at comes last
        usort(
            $entries,
            static fn(array $a, array $b): int => [$a[0], -$a[1], $a[3] ? 0 : 1] <=> [$b[0], -$b[1], $b[3] ? 0 : 1],
        );

        return $entries;
    }

    /**
     * The byte range the agent points at on the line, or [null, null] for the whole line
     *
     * @return array{?int, ?int}
     */
    private function getSpan(
        string $file_path,
        int $line,
        int $line_start,
        int $line_end,
        ?int $column,
        ?string $text,
    ): array {
        if ($column !== null) {
            if ($column < 1 || $line_start + $column - 1 > $line_end) {
                throw new ToolError('Line ' . $line . ' has ' . ($line_end - $line_start) . ' columns.');
            }

            $offset = $line_start + $column - 1;

            return [$offset, $offset + 1];
        }

        if ($text !== null && $text !== '') {
            $line_text = substr($this->codebase->getFileContents($file_path), $line_start, $line_end - $line_start);
            $position = strpos($line_text, $text);

            if ($position === false) {
                throw new ToolError('`' . $text . '` is not on line ' . $line . ', which reads: ' . trim($line_text));
            }

            return [$line_start + $position, $line_start + $position + strlen($text)];
        }

        return [null, null];
    }

    /**
     * @return array{int, int} the offsets of the start and the end of the line, without its line break
     */
    private function getLineBounds(string $file_path, int $line): array
    {
        $contents = $this->codebase->getFileContents($file_path);
        $line_count = substr_count($contents, "\n") + 1;

        if ($line < 1 || $line > $line_count) {
            throw new ToolError($this->config->shortenFileName($file_path) . ' has ' . $line_count . ' lines.');
        }

        $start = 0;
        for ($i = 1; $i < $line; $i++) {
            $start = (int) strpos($contents, "\n", $start) + 1;
        }

        $end = strpos($contents, "\n", $start);
        $end = $end === false ? strlen($contents) : $end;

        if ($end > $start && $contents[$end - 1] === "\r") {
            $end--;
        }

        return [$start, $end];
    }

    /**
     * @psalm-pure
     */
    private function formatPosition(string $contents, int $offset): string
    {
        $before = substr($contents, 0, $offset);
        $line_start = strrpos($before, "\n");

        return (substr_count($before, "\n") + 1) . ':' . ($offset - ($line_start === false ? -1 : $line_start));
    }

    /**
     * Runs `psalm` on the whole project, so that the result is the one CI gets: with unused code, taint paths
     * between files and the project's cache
     */
    private function checkProject(bool $show_info): string
    {
        $command = $this->psalm_command;
        $command[] = '--show-info=' . ($show_info ? 'true' : 'false');

        // one pipe, so that a full stderr can't block the run while its stdout is read
        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['redirect', 1]];
        $process = proc_open($command, $descriptors, $pipes, $this->base_dir);

        if (!is_resource($process) || !isset($pipes[0], $pipes[1])) {
            throw new ToolError('Could not run ' . implode(' ', $command));
        }

        fclose($pipes[0]);
        $output = trim((string) stream_get_contents($pipes[1]));
        fclose($pipes[1]);
        $exit_code = proc_close($process);

        if ($exit_code !== 0 && $exit_code !== 2) {
            throw new ToolError('Psalm failed (exit code ' . $exit_code . '), this says nothing about the code:'
                . "\n" . $output);
        }

        return ($output === '' ? 'No issues found.' : $output) . "\n\n" . 'Exit code ' . $exit_code
            . ($exit_code === 0 ? ': no issues.' : ': issues found.');
    }

    /**
     * Analyses a file whose type and reference maps are older than the last change to the project
     */
    private function analyzeForQuery(string $file_path): void
    {
        $this->sync();

        if (($this->analyzed_in[$file_path] ?? -1) !== $this->generation) {
            $this->analyze([$file_path => $file_path]);
            IssueBuffer::clear();
        }
    }

    /**
     * Analyses every project file older than the last change, which reference lookups need
     */
    private function analyzeProject(): void
    {
        $stale = [];
        foreach (array_keys($this->file_states) as $file_path) {
            if (($this->analyzed_in[$file_path] ?? -1) !== $this->generation) {
                $stale[$file_path] = $file_path;
            }
        }

        if ($stale !== []) {
            $this->analyze($stale);
            IssueBuffer::clear();
        }
    }

    /**
     * @param array<string, string> $files
     */
    private function analyze(array $files): void
    {
        $this->codebase->reloadFiles($this->project_analyzer, array_keys($files));
        $this->codebase->analyzer->addFilesToAnalyze($files);
        $this->codebase->analyzer->analyzeFiles($this->project_analyzer, $this->project_analyzer->threads, false);

        foreach ($files as $file_path) {
            $this->analyzed_in[$file_path] = $this->generation;
        }
    }

    /**
     * Scans the project the first time, and later reloads the project files that changed on disk since
     */
    private function sync(): void
    {
        if (!$this->ready) {
            $this->start();
            return;
        }

        clearstatcache();

        $changed = [];
        $current = $this->getProjectFiles();

        foreach ($current as $file_path) {
            if ($this->recordState($file_path)) {
                $changed[] = $file_path;
            }
        }

        $deleted = [];
        foreach (array_keys($this->file_states) as $file_path) {
            if (!isset($current[$file_path])) {
                $deleted[] = $file_path;
                unset($this->file_states[$file_path], $this->analyzed_in[$file_path]);
            }
        }

        if ($changed === [] && $deleted === []) {
            return;
        }

        $this->generation++;

        $file_provider = $this->codebase->file_provider;

        foreach ([...$changed, ...$deleted] as $file_path) {
            // the file provider keeps what it read, which is now out of date
            $file_provider->closeFile($file_path);
        }

        foreach ($deleted as $file_path) {
            $this->codebase->invalidateInformationForFile($file_path);
        }

        if ($changed !== []) {
            $this->codebase->reloadFiles($this->project_analyzer, $changed);
        }
    }

    private function start(): void
    {
        // before the scan reads them, so that a file changed during the scan is seen as changed
        foreach ($this->getProjectFiles() as $file_path) {
            $this->recordState($file_path);
        }

        $this->project_analyzer->serverMode();

        $this->codebase->scanFiles($this->project_analyzer->scanThreads);
        $this->config->visitStubFiles($this->codebase, $this->project_analyzer->progress);
        $this->config->eventDispatcher->dispatchAfterCodebasePopulated(
            new AfterCodebasePopulatedEvent($this->codebase),
        );

        $this->ready = true;
    }

    /**
     * Records the state of a file, and tells whether its contents changed since it was last recorded
     */
    private function recordState(string $file_path): bool
    {
        $previous = $this->file_states[$file_path] ?? null;
        $now = time();
        $mtime = (int) @filemtime($file_path);
        $size = (int) @filesize($file_path);

        // PHP reports modification times in seconds, so a file written again in the second it was read keeps its time
        if ($previous !== null && $previous[0] === $mtime && $previous[1] === $size && $mtime < $previous[3] - 1) {
            return false;
        }

        $hash = (string) @hash_file('xxh128', $file_path);
        $this->file_states[$file_path] = [$mtime, $size, $hash, $now];

        return $previous === null || $previous[2] !== $hash;
    }

    /**
     * Lists the project files on disk, and indexes them under their path keys
     *
     * @return array<string, string>
     */
    private function getProjectFiles(): array
    {
        $project_files = [];
        $file_extensions = $this->config->getFileExtensions();

        foreach ($this->config->getProjectDirectories() as $dir_name) {
            if (!is_dir($dir_name)) {
                continue;
            }

            foreach ($this->codebase->file_provider->getFilesInDir(
                $dir_name,
                $file_extensions,
                $this->config->isInProjectDirs(...),
            ) as $file_path) {
                $project_files[$file_path] = $file_path;
            }
        }

        foreach ($this->config->getProjectFiles() as $file_path) {
            if (is_file($file_path)) {
                $project_files[$file_path] = $file_path;
            }
        }

        ksort($project_files);

        $this->file_keys = [];
        foreach ($project_files as $file_path) {
            $real_path = realpath($file_path);
            $this->file_keys[self::getPathKey($real_path === false ? $file_path : $real_path)] = $file_path;
        }

        return $project_files;
    }

    private function resolveFile(string $path): string
    {
        $files = $this->resolveProjectFiles($path);

        if (count($files) !== 1 || is_dir($this->resolvePath($path))) {
            throw new ToolError('`' . $path . '` is a directory, give a file.');
        }

        return $files[0];
    }

    /**
     * @return list<string>
     */
    private function resolveProjectFiles(string $path): array
    {
        $real_path = $this->resolvePath($path);
        $key = self::getPathKey($real_path);

        if (is_dir($real_path)) {
            $prefix = rtrim($key, '/') . '/';
            $files = [];

            foreach ($this->file_keys as $file_key => $file_path) {
                if (str_starts_with($file_key, $prefix)) {
                    $files[] = $file_path;
                }
            }

            if ($files === []) {
                throw new ToolError('`' . $path . '` has no files that psalm.xml includes in <projectFiles>.');
            }

            return $files;
        }

        if (!isset($this->file_keys[$key])) {
            throw new ToolError('`' . $path . '` is not one of the files that psalm.xml includes in <projectFiles>,'
                . ' so Psalm doesn\'t analyse it.');
        }

        return [$this->file_keys[$key]];
    }

    private function resolvePath(string $path): string
    {
        $this->sync();

        $candidate = str_starts_with($path, DIRECTORY_SEPARATOR) || (strlen($path) > 1 && $path[1] === ':')
            ? $path
            : $this->base_dir . DIRECTORY_SEPARATOR . $path;

        $real_path = realpath($candidate);

        if ($real_path === false) {
            throw new ToolError('`' . $path . '` does not exist (paths are relative to ' . $this->base_dir . ').');
        }

        return $real_path;
    }

    /**
     * The real path with forward slashes, and on Windows in lowercase, so that the paths an agent gives match the
     * project files whatever their separators, case and symlinks
     *
     * @psalm-pure
     */
    private static function getPathKey(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        return DIRECTORY_SEPARATOR === '\\' ? strtolower($path) : $path;
    }

    /**
     * Whether a reference map symbol stands for an assigned value, not for a declared symbol
     *
     * @psalm-pure
     */
    private static function isExpressionSymbol(string $symbol): bool
    {
        return $symbol !== '' && $symbol[0] >= '0' && $symbol[0] <= '9';
    }

    /** @psalm-pure */
    private static function countFiles(int $count): string
    {
        return $count . ' file' . ($count === 1 ? '' : 's');
    }

    /** @psalm-pure */
    private static function shorten(string $code): string
    {
        $code = (string) preg_replace('/\s+/', ' ', $code);

        return strlen($code) > 120 ? substr($code, 0, 117) . '...' : $code;
    }

    /** @psalm-pure */
    private static function indent(string $text, int $spaces): string
    {
        $indent = str_repeat(' ', $spaces);

        return $indent . implode("\n" . $indent, explode("\n", $text));
    }
}
