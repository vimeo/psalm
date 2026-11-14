<?php

declare(strict_types=1);

namespace Psalm\Internal\Overlay;

use Psalm\Config;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Codebase\CodeUseGraph;
use Psalm\Internal\Composer;
use Psalm\Internal\Provider\FileReferenceCacheProvider;
use Symfony\Component\Filesystem\Path;

use function array_keys;
use function array_pop;
use function array_values;
use function clearstatcache;
use function count;
use function file_exists;
use function filesize;
use function hash_file;
use function is_array;
use function ksort;
use function str_contains;

/**
 * Which files depend on which, as a full run that recorded the code use edges found them, with a stamp of every
 * file the run depended on. The cached graph cannot serve: a partial run replaces it with its own edges.
 *
 * @psalm-type Stamp = array{int, string}
 * @internal
 */
final class ReferenceIndex
{
    private const VERSION = 2;

    /**
     * @param array<string, array{i: list<string>, a: list<string>}> $files file => the files inheriting from its
     *      classes (or depending on them like it), the files referencing its classes and functions
     * @param array<string, list<string>> $members code use graph node of a class member => the files referencing it
     * @param array<string, Stamp> $stamps file => size and hash
     * @param list<string> $project_files
     */
    public function __construct(
        public readonly array $files,
        public readonly array $members,
        public readonly array $stamps,
        public readonly array $project_files,
    ) {
    }

    /**
     * After a full run that recorded the code use edges
     */
    public static function build(ProjectAnalyzer $project_analyzer): self
    {
        $codebase = $project_analyzer->getCodebase();
        $file_reference_provider = $codebase->file_reference_provider;

        $files = [];
        foreach ($codebase->scanner->getScannedFiles() as $file_path => $_) {
            $inheriting = $file_reference_provider->getFilesInheritingFromFile($file_path);
            $referencing = $file_reference_provider->getFilesReferencingFile($file_path);

            if ($inheriting || $referencing) {
                $files[$file_path] = ['i' => array_values($inheriting), 'a' => array_values($referencing)];
            }
        }

        $members = [];
        foreach ($file_reference_provider->code_use_graph->getAllReferences() as $target_node => $sources) {
            if (CodeUseGraph::getOwnerClass($target_node) === null || !str_contains($target_node, '::')) {
                continue;
            }

            $members[$target_node] = array_keys($file_reference_provider->getFilesOfNodes($codebase, $sources));
        }

        $project_files = array_values($project_analyzer->initProjectFiles());

        $stamps = [];
        foreach ([...$project_files, ...self::getConfigurationFiles($codebase->config)] as $file_path) {
            $stamp = self::stamp($file_path);
            if ($stamp !== null) {
                $stamps[$file_path] = $stamp;
            }
        }
        ksort($stamps);

        return new self($files, $members, $stamps, $project_files);
    }

    public function save(FileReferenceCacheProvider $cache): void
    {
        $cache->setReferenceIndex([
            'version' => self::VERSION,
            'files' => $this->files,
            'members' => $this->members,
            'stamps' => $this->stamps,
            'project_files' => $this->project_files,
        ]);
    }

    public static function load(FileReferenceCacheProvider $cache): ?self
    {
        $data = $cache->getReferenceIndex();

        if (!is_array($data) || ($data['version'] ?? null) !== self::VERSION) {
            return null;
        }

        /**
         * @var array{
         *     files: array<string, array{i: list<string>, a: list<string>}>,
         *     members: array<string, list<string>>,
         *     stamps: array<string, Stamp>,
         *     project_files: list<string>,
         * } $data
         */
        return new self($data['files'], $data['members'], $data['stamps'], $data['project_files']);
    }

    /**
     * Why the index does not describe the project as it is, null if it does
     *
     * @param array<string, string> $project_files
     */
    public function getStaleReason(array $project_files, Config $config): ?string
    {
        if (count($project_files) !== count($this->project_files)) {
            return 'the set of project files changed';
        }

        foreach ($this->project_files as $file_path) {
            if (!isset($project_files[$file_path])) {
                return $file_path . ' is not a project file any more';
            }
        }

        $configuration_files = self::getConfigurationFiles($config);

        foreach ([...$this->project_files, ...$configuration_files] as $file_path) {
            if (!self::isUnchanged($file_path, $this->stamps[$file_path] ?? null)) {
                return $file_path . ' changed';
            }
        }

        return null;
    }

    /**
     * Whether the file is as the stamp says (null: the file did not exist)
     *
     * @param Stamp|null $stamp
     */
    public static function isUnchanged(string $file_path, ?array $stamp): bool
    {
        clearstatcache(true, $file_path);

        if ($stamp === null) {
            return !file_exists($file_path);
        }

        if (!file_exists($file_path)) {
            return false;
        }

        // not the modification time: a same-size edit within its resolution keeps it
        return filesize($file_path) === $stamp[0] && hash_file('xxh128', $file_path) === $stamp[1];
    }

    /** @return Stamp|null */
    public static function stamp(string $file_path): ?array
    {
        clearstatcache(true, $file_path);

        if (!file_exists($file_path)) {
            return null;
        }

        $size = filesize($file_path);
        $hash = hash_file('xxh128', $file_path);

        if ($size === false || $hash === false) {
            return null;
        }

        return [$size, $hash];
    }

    /**
     * The files whose classes inherit from the classes of the given files, or depend on them like they do,
     * transitively, the given files included
     *
     * @param list<string> $file_paths
     * @return array<string, true>
     * @psalm-capabilities read-props
     */
    public function getInheritors(array $file_paths): array
    {
        $result = [];
        $pending = $file_paths;

        while ($pending) {
            $file_path = array_pop($pending);

            if (isset($result[$file_path])) {
                continue;
            }

            $result[$file_path] = true;

            foreach ($this->files[$file_path]['i'] ?? [] as $inheriting) {
                $pending[] = $inheriting;
            }
        }

        return $result;
    }

    /**
     * The inheritors of the files and the files referencing any of them
     *
     * @param list<string> $file_paths
     * @return array<string, true>
     * @psalm-capabilities read-props
     */
    public function getDependents(array $file_paths): array
    {
        $result = $this->getInheritors($file_paths);

        foreach (array_keys($result) as $file_path) {
            foreach ($this->files[$file_path]['a'] ?? [] as $referencing) {
                $result[$referencing] = true;
            }
        }

        return $result;
    }

    /**
     * @param list<string> $nodes code use graph nodes of class members
     * @return array<string, true>
     * @psalm-capabilities read-props
     */
    public function getFilesReferencingMembers(array $nodes): array
    {
        $result = [];

        foreach ($nodes as $node) {
            foreach ($this->members[$node] ?? [] as $file_path) {
                $result[$file_path] = true;
            }
        }

        return $result;
    }

    /**
     * Files the analysis of every project file depends on besides the project files: overlaying them would change
     * what the index describes
     *
     * @return list<string>
     */
    public static function getConfigurationFiles(Config $config): array
    {
        $files = [Composer::getLockFilePath($config->base_dir)];

        if ($config->source_filename !== null) {
            $files[] = $config->source_filename;
        }

        if ($config->error_baseline !== null && $config->error_baseline !== '') {
            $files[] = Path::makeAbsolute($config->error_baseline, $config->base_dir);
        }

        return $files;
    }
}
