<?php

declare(strict_types=1);

namespace Psalm\Internal\Overlay;

use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Codebase\CodeUseGraph;
use Psalm\Internal\Json\Json;
use Psalm\Internal\Provider\FileStorageProvider;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\Event\AfterCodebasePopulatedEvent;

use function array_fill_keys;
use function array_intersect_key;
use function array_keys;
use function array_values;
use function in_array;
use function json_decode;
use function ksort;
use function preg_match;
use function str_starts_with;
use function strtolower;

use const JSON_THROW_ON_ERROR;

/**
 * One analysis of a scanned, unpopulated project: the overlays replace the scan results of their files, then the
 * rest runs as in a fresh run, from the populator on. The process is not usable for anything else afterwards.
 *
 * @psalm-import-type IssueArray from IssueDiff
 * @internal
 */
final class Pass
{
    /**
     * Replaces the scan results of the overlaid files
     *
     * @param array<string, string> $overlays
     * @return array<string, array{mixed, mixed}> path => the signatures before and after, see StorageFingerprint
     */
    public static function applyOverlays(ProjectAnalyzer $project_analyzer, array $overlays): array
    {
        $codebase = $project_analyzer->getCodebase();
        $scanned_files = $codebase->scanner->getScannedFiles();

        $signatures = [];
        foreach ($overlays as $path => $contents) {
            $signatures[$path] = [StorageFingerprint::getSignature($codebase, $path), null];
            $codebase->addTemporaryFileChanges($path, $contents);

            if (!isset($scanned_files[$path])) {
                // nothing scanned depends on it
                continue;
            }

            $codebase->invalidateInformationForFile($path);

            if ($scanned_files[$path]) {
                $codebase->scanner->addFileToDeepScan($path);
            } else {
                $codebase->scanner->addFileToShallowScan($path);
            }
        }

        $codebase->scanFilesWithoutPopulating();
        RuntimeDeclarations::forgetRemoved($project_analyzer->getConfig(), $overlays);

        foreach ($signatures as $path => [$before]) {
            $signatures[$path] = [$before, StorageFingerprint::getSignature($codebase, $path)];
        }

        return $signatures;
    }

    /**
     * @param array<string, array{mixed, mixed}> $signatures see applyOverlays()
     * @param list<string> $registry_files files whose functions and constants the scan registered globally
     * @return array{Strategy::BODY|Strategy::VISIBILITY|Strategy::SIGNATURE|Strategy::DECLARATIONS|Strategy::FRESH,
     *     list<string>} and the code use graph nodes of the members whose visibility changed
     */
    public static function classify(array $signatures, array $registry_files): array
    {
        $strategy = Strategy::BODY;
        $members = [];

        foreach ($signatures as $path => $signature) {
            $differences = StorageFingerprint::getDifferences($signature[0], $signature[1]);

            if (!$differences) {
                continue;
            }

            $is_registry_file = in_array($path, $registry_files, true);

            foreach ($differences as [$leaf, $kind]) {
                $leaf_strategy = self::classifyDifference($leaf, $kind, $is_registry_file, $members);

                if (self::RANK[$leaf_strategy] > self::RANK[$strategy]) {
                    $strategy = $leaf_strategy;
                }
            }
        }

        return [$strategy, $strategy === Strategy::VISIBILITY ? array_values($members) : []];
    }

    private const RANK = [
        Strategy::BODY => 0,
        Strategy::VISIBILITY => 1,
        Strategy::SIGNATURE => 2,
        Strategy::DECLARATIONS => 3,
        Strategy::FRESH => 4,
    ];

    /**
     * @param 'changed'|'added'|'removed' $kind
     * @param array<string, string> $members
     * @return key-of<self::RANK>
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private static function classifyDifference(
        string $leaf,
        string $kind,
        bool $is_registry_file,
        array &$members,
    ): string {
        // a function or a constant registered globally stays registered as it was
        if ($is_registry_file && preg_match('#^/file/(functions|constants)/#', $leaf)) {
            return Strategy::FRESH;
        }

        // other files resolve parameter defaults naming a class constant when they are scanned
        if (preg_match('#^/classlikes/[^/]+/(constants|enum_cases)/[^/]+/#', $leaf)) {
            return Strategy::FRESH;
        }

        // a private method is not inherited: this follows the visibility leaf of the method
        if (preg_match('#^/classlikes/[^/]+/inheritable_method_ids/[^/]+$#', $leaf)) {
            return Strategy::VISIBILITY;
        }

        if ($leaf === '' || $leaf === '/classlikes' || $leaf === '/file') {
            // a file that was not scanned before, or is not now
            return Strategy::DECLARATIONS;
        }

        if ($kind !== 'changed'
            && (preg_match('#^/classlikes/[^/]+$#', $leaf)
                || preg_match('#^/file/(functions|constants)/[^/]+$#', $leaf)
                || preg_match('#^/classlikes/[^/]+/(methods|properties|constants|enum_cases)/[^/]+$#', $leaf))
        ) {
            return Strategy::DECLARATIONS;
        }

        if (str_starts_with($leaf, '/file/constants/')) {
            return Strategy::DECLARATIONS;
        }

        if ($kind === 'changed'
            && preg_match('#^/classlikes/([^/]+)/(methods|properties|constants)/([^/]+)/visibility$#', $leaf, $matches)
        ) {
            [, $class, $member_kind, $name] = $matches;

            foreach (self::getMemberNodes($class, $member_kind, $name) as $node) {
                $members[$node] = $node;
            }

            return Strategy::VISIBILITY;
        }

        return Strategy::SIGNATURE;
    }

    /**
     * @return list<string>
     * @psalm-pure
     */
    private static function getMemberNodes(string $class, string $member_kind, string $name): array
    {
        /** @var lowercase-string $class */
        if ($member_kind === 'methods') {
            $method_id = strtolower($class . '::' . $name);

            return [CodeUseGraph::functionLikeNode($method_id), CodeUseGraph::functionLikeReturnNode($method_id)];
        }

        if ($member_kind === 'properties') {
            return [CodeUseGraph::propertyNode($class, $name)];
        }

        return [CodeUseGraph::classConstantNode($class, $name)];
    }

    /**
     * @param Strategy::* $strategy
     * @param list<string> $paths the overlaid files
     * @param list<string> $members see classify()
     * @param array<string, string> $project_files
     * @return list<string> the files both passes analyse
     */
    public static function getAffectedFiles(
        string $strategy,
        array $paths,
        array $members,
        ReferenceIndex $index,
        array $project_files,
    ): array {
        if ($strategy === Strategy::SIGNATURE && Strategy::getSignatureScope() === Strategy::SCOPE_PROJECT) {
            $strategy = Strategy::DECLARATIONS;
        }

        if ($strategy === Strategy::DECLARATIONS
            || $strategy === Strategy::FRESH
            || $strategy === Strategy::WHOLE_PROJECT
        ) {
            $files = $project_files;
        } else {
            $files = $strategy === Strategy::SIGNATURE ? $index->getDependents($paths) : $index->getInheritors($paths);

            // the files including them, directly or through other files
            $included = $paths;
            $followed = array_fill_keys($paths, true);
            while ($included) {
                $required = [];
                foreach ($included as $path) {
                    $required[strtolower($path)] = true;
                }

                $included = [];
                foreach (FileStorageProvider::getAll() as $file_storage) {
                    if (!isset($followed[$file_storage->file_path])
                        && array_intersect_key($file_storage->required_file_paths, $required)
                    ) {
                        $followed[$file_storage->file_path] = true;
                        $files[$file_storage->file_path] = true;
                        $included[] = $file_storage->file_path;
                    }
                }
            }

            if ($strategy === Strategy::VISIBILITY) {
                $files += $index->getFilesReferencingMembers($members);
            }
        }

        $affected = [];
        foreach (array_keys($files) as $file) {
            if (isset($project_files[$file])) {
                $affected[$file] = $file;
            }
        }
        ksort($affected);

        return array_values($affected);
    }

    /**
     * The rest of the scan phase of a fresh run, and the analysis of the files
     *
     * @param list<string> $files
     * @param bool $taint whether the configuration runs a taint analysis, for a pass over the whole project
     * @return list<IssueArray> the issues as the JSON report has them, before the baseline
     */
    public static function analyze(
        ProjectAnalyzer $project_analyzer,
        array $files,
        bool $whole_project,
        bool $taint,
    ): array {
        $codebase = $project_analyzer->getCodebase();
        $config = $codebase->config;

        if ($whole_project) {
            $codebase->code_use_graph->record_edges = $codebase->find_unused_code !== null;

            if ($taint) {
                $project_analyzer->trackTaintedInputs();
                // what a fresh run with a taint analysis scans besides
                $codebase->scanFilesWithoutPopulating();
            }
        } else {
            // the passes over the whole project do not run: nothing reads the edges, and purity is not suggested
            $codebase->code_use_graph->record_edges = false;
            $config->suggest_purity_annotations = false;
        }

        $codebase->populateScannedFiles();
        $codebase->infer_types_from_usage = true;
        $config->visitStubFiles($codebase, $project_analyzer->progress);
        $config->eventDispatcher->dispatchAfterCodebasePopulated(new AfterCodebasePopulatedEvent($codebase));

        $codebase->analyzer->addFilesToAnalyze(self::combine($files));
        $codebase->analyzer->analyzeFiles($project_analyzer, 1, false, $whole_project);

        $issues = [];
        foreach (IssueBuffer::getIssuesData() as $file_issues) {
            foreach ($file_issues as $issue) {
                $issue = (array) $issue;
                unset($issue['dupe_key'], $issue['group_key']);
                $issues[] = $issue;
            }
        }

        /** @var list<IssueArray> plain data, taint traces included */
        return json_decode(Json::encode($issues), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param list<string> $files
     * @return array<string, string>
     * @psalm-pure
     */
    private static function combine(array $files): array
    {
        $combined = [];
        foreach ($files as $file) {
            $combined[$file] = $file;
        }

        return $combined;
    }
}
