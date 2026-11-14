<?php

declare(strict_types=1);

namespace Psalm\Internal\Overlay;

use Closure;
use Psalm\Config;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\RuntimeCaches;
use Symfony\Component\Filesystem\Path;

use function array_keys;
use function array_map;
use function file_get_contents;
use function fwrite;
use function getenv;
use function in_array;
use function realpath;
use function sort;

use const STDERR;

/**
 * Answers requests against a project scanned once and never populated (the snapshot): every pass runs in a process
 * forked from it, or, where nothing can fork, in this process after the snapshot is built again.
 *
 * @psalm-import-type IssueArray from IssueDiff
 * @internal
 */
final class Engine
{
    /**
     * @param array<string, string> $project_files
     * @param array<string, array<string, array{o: int, s: array<int, string>}>> $baseline
     * @param list<string> $registry_files files whose functions and constants the scan registered globally
     * @param list<string> $not_checked the passes over the whole project the configuration runs
     * @param bool $taint whether the configuration runs a taint analysis
     * @param Closure(): ProjectAnalyzer $rebootstrap a new, configured, unscanned ProjectAnalyzer
     */
    public function __construct(
        public readonly ProjectAnalyzer $project_analyzer,
        public readonly array $project_files,
        public readonly ReferenceIndex $index,
        private readonly array $baseline,
        private readonly array $registry_files,
        private readonly array $not_checked,
        private readonly bool $taint,
        private readonly Closure $rebootstrap,
    ) {
    }

    /**
     * Builds the snapshot: what check() scans, nothing populated, no taint analysis
     *
     * @param array<string, string> $overlays registered before anything is scanned (a fresh run of the overlays)
     * @return array{array<string, string>, bool} the project files, and whether the configuration runs taints
     */
    public static function scanSnapshot(ProjectAnalyzer $project_analyzer, array $overlays = []): array
    {
        $codebase = $project_analyzer->getCodebase();
        $taint = $codebase->taint_flow_graph !== null;
        $codebase->taint_flow_graph = null;
        $codebase->code_use_graph->record_edges = false;

        foreach ($overlays as $path => $contents) {
            $codebase->addTemporaryFileChanges($path, $contents);
        }

        return [$project_analyzer->scanWithoutPopulating(), $taint];
    }

    /**
     * @throws OverlayFailure
     */
    public function validate(Request $request): void
    {
        $config = $this->project_analyzer->getConfig();
        $snapshot_inputs = [
            ...ReferenceIndex::getConfigurationFiles($config),
            ...$config->getStubFiles(),
            ...$config->getPreloadedStubFiles(),
        ];
        $snapshot_inputs[] = Path::join($config->base_dir, 'composer.json');
        $snapshot_inputs = array_map(self::canonical(...), $snapshot_inputs);

        foreach (array_keys($request->overlays) as $path) {
            if (in_array(self::canonical($path), $snapshot_inputs, true)) {
                throw new OverlayFailure(
                    OverlayFailure::INVALID_OVERLAY,
                    $path . ' configures the analysis: it cannot be overlaid',
                );
            }
        }
    }

    private static function canonical(string $path): string
    {
        $real = realpath($path);

        return $real === false ? $path : $real;
    }

    /**
     * The overlay pass up to the choice of the files to analyse, in a process that is not usable afterwards
     *
     * @return array{Strategy::*, list<string>}
     */
    public function chooseFiles(Request $request): array
    {
        $signatures = Pass::applyOverlays($this->project_analyzer, $request->overlays);
        $paths = array_keys($request->overlays);

        if (getenv(Strategy::DEBUG_ENV) === '1') {
            foreach ($signatures as $path => $signature) {
                foreach (StorageFingerprint::getDifferences($signature[0], $signature[1]) as [$leaf, $kind]) {
                    fwrite(STDERR, Protocol::encode(['path' => $path, 'leaf' => $leaf, 'kind' => $kind]) . "\n");
                }
            }
        }

        $registry_files = $this->registry_files;
        $config = $this->project_analyzer->getConfig();
        if ($config->all_functions_global || $config->all_constants_global) {
            $registry_files = [...$registry_files, ...$paths];
        }

        [$strategy, $members] = Pass::classify($signatures, $registry_files);

        if ($request->forced_strategy !== null) {
            $strategy = $request->forced_strategy;
        }

        if ($request->whole_project) {
            // every project file, in a fresh process too when the scan registered what changed
            $strategy = $strategy === Strategy::FRESH ? Strategy::FRESH : Strategy::WHOLE_PROJECT;
            $members = [];
        }

        return [
            $strategy,
            Pass::getAffectedFiles($strategy, $paths, $members, $this->index, $this->project_files),
        ];
    }

    /**
     * @param list<string> $files
     * @return list<IssueArray>
     */
    public function analyze(ProjectAnalyzer $project_analyzer, array $files, bool $whole_project): array
    {
        return Pass::analyze($project_analyzer, $files, $whole_project, $this->taint);
    }

    /**
     * Both passes in this process, one after the other, each on a snapshot of its own
     *
     * @return array{Strategy::*, list<string>, list<IssueArray>, list<IssueArray>} the
     *      strategy, the files, the issues without and with the overlays
     */
    public function runWithoutFork(Request $request, bool $fresh): array
    {
        if ($fresh) {
            $strategy = Strategy::FRESH;
            $files = $this->getProjectFileList();
            $overlay_issues = $this->analyze($this->project_analyzer, $files, $request->whole_project);
        } else {
            [$strategy, $files] = $this->chooseFiles($request);

            if ($strategy === Strategy::FRESH) {
                $project_analyzer = $this->rebuildSnapshot($request->overlays);

                return [Strategy::FRESH, ...$this->runFreshPasses($project_analyzer, $request)];
            }

            $overlay_issues = $files ? $this->analyze($this->project_analyzer, $files, $request->whole_project) : [];
        }

        $base_issues = $files
        ? $this->analyze($this->rebuildSnapshot([]), $files, $request->whole_project)
        : [];

        return [$strategy, $files, $base_issues, $overlay_issues];
    }

    /**
     * @return array{list<string>, list<IssueArray>, list<IssueArray>}
     */
    private function runFreshPasses(ProjectAnalyzer $project_analyzer, Request $request): array
    {
        $files = $this->getProjectFileList();
        $overlay_issues = $this->analyze($project_analyzer, $files, $request->whole_project);
        $base_issues = $this->analyze($this->rebuildSnapshot([]), $files, $request->whole_project);

        return [$files, $base_issues, $overlay_issues];
    }

    /**
     * @param array<string, string> $overlays
     */
    private function rebuildSnapshot(array $overlays): ProjectAnalyzer
    {
        // the reset Psalm's own tests make between two analyses, with a new config, new providers and new caches
        RuntimeCaches::clearAll();
        $project_analyzer = ($this->rebootstrap)();
        self::scanSnapshot($project_analyzer, $overlays);

        return $project_analyzer;
    }

    /**
     * @return list<string>
     * @psalm-capabilities read-props
     */
    public function getProjectFileList(): array
    {
        $files = array_keys($this->project_files);
        sort($files);

        return $files;
    }

    /**
     * @param list<string> $files
     * @param list<IssueArray> $base_issues
     * @param list<IssueArray> $overlay_issues
     * @return array{protocol: int, new_issues: list<IssueArray>, analyzed_files: list<string>,
     *     not_checked: list<string>}
     */
    public function buildEnvelope(Request $request, array $files, array $base_issues, array $overlay_issues): array
    {
        $config = $this->project_analyzer->getConfig();

        $contents = [];
        foreach ($request->overlays as $path => $overlay) {
            $contents[$path] = [(string) file_get_contents($path), $overlay];
        }

        $new_issues = IssueDiff::getNewIssues(
            $base_issues,
            $overlay_issues,
            $contents,
            $this->baseline,
            static fn(string $type): bool => $config->isSuppressible($type),
        );

        return Protocol::envelope(
            $new_issues,
            array_map(static fn(string $file): string => self::relativePath($config, $file), $files),
            $request->whole_project ? [] : $this->not_checked,
        );
    }

    private static function relativePath(Config $config, string $file): string
    {
        return Path::makeRelative($file, $config->base_dir);
    }
}
