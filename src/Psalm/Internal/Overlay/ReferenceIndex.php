<?php

declare(strict_types=1);

namespace Psalm\Internal\Overlay;

use Psalm\Codebase;
use Psalm\Config;
use Psalm\Internal\Composer;
use Psalm\Internal\Provider\FileReferenceCacheProvider;
use Symfony\Component\Filesystem\Path;

use function array_keys;
use function array_pop;
use function array_values;
use function clearstatcache;
use function count;
use function file_exists;
use function hash_file;

/**
 * Which files depend on which, as a full run that recorded the code use edges found them, with a stamp of every
 * file the run read that the cache identity does not cover. The cached graph cannot serve: a partial run replaces it
 * with its own edges.
 *
 * @psalm-type Data = array{
 *     files: array<string, array{i: list<string>, a: list<string>}>,
 *     members: array<string, list<string>>,
 *     stamps: array<string, ?string>,
 *     project_files: list<string>,
 * }
 * @internal
 */
final class ReferenceIndex
{
    /**
     * @param array<string, array{i: list<string>, a: list<string>}> $files file => the files inheriting from its
     *      classes (or depending on them like it), the files referencing its classes and functions
     * @param array<string, list<string>> $members code use graph node of a class member => the files referencing it
     * @param array<string, ?string> $stamps file => its content hash, null if it did not exist
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
     *
     * @param list<string> $project_files
     * @param array<string, ?string> $stamps taken before the run read the files, see stampFiles()
     */
    public static function build(Codebase $codebase, array $project_files, array $stamps): self
    {
        $file_reference_provider = $codebase->file_reference_provider;

        $files = [];
        foreach ($codebase->scanner->getScannedFiles() as $file_path => $_) {
            $inheriting = $file_reference_provider->getFilesInheritingFromFile($file_path);
            $referencing = $file_reference_provider->getFilesReferencingFile($file_path);

            if ($inheriting || $referencing) {
                $files[$file_path] = ['i' => array_values($inheriting), 'a' => array_values($referencing)];
            }
        }

        $members = $file_reference_provider->getFilesReferencingMembers($codebase);

        return new self($files, $members, $stamps, $project_files);
    }

    public function save(FileReferenceCacheProvider $cache): void
    {
        $cache->setReferenceIndex([
            'files' => $this->files,
            'members' => $this->members,
            'stamps' => $this->stamps,
            'project_files' => $this->project_files,
        ]);
    }

    public static function load(FileReferenceCacheProvider $cache): ?self
    {
        $data = $cache->getReferenceIndex();

        if ($data === null) {
            return null;
        }

        return new self($data['files'], $data['members'], $data['stamps'], $data['project_files']);
    }

    /**
     * Why the index does not describe the project as it is, null if it does
     *
     * @param array<string, string> $project_files
     */
    public function getStaleReason(array $project_files): ?string
    {
        if (count($project_files) !== count($this->project_files)) {
            return 'the set of project files changed';
        }

        foreach ($this->project_files as $file_path) {
            if (!isset($project_files[$file_path])) {
                return $file_path . ' is not a project file any more';
            }
        }

        foreach ($this->stamps as $file_path => $stamp) {
            if (!self::isUnchanged($file_path, $stamp)) {
                return $file_path . ' changed';
            }
        }

        return null;
    }

    /**
     * Whether the file is as the stamp says. Not by the modification time: a same-size edit within its resolution
     * keeps it.
     */
    public static function isUnchanged(string $file_path, ?string $stamp): bool
    {
        return self::stamp($file_path) === $stamp;
    }

    /**
     * The content hash of the file, null if it does not exist
     */
    public static function stamp(string $file_path): ?string
    {
        clearstatcache(true, $file_path);

        if (!file_exists($file_path)) {
            return null;
        }

        $hash = hash_file('xxh128', $file_path);

        return $hash === false ? null : $hash;
    }

    /**
     * @param list<string> $file_paths
     * @return array<string, ?string>
     */
    public static function stampFiles(array $file_paths): array
    {
        $stamps = [];

        foreach ($file_paths as $file_path) {
            $stamps[$file_path] = self::stamp($file_path);
        }

        return $stamps;
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
     * Files besides the project and extra files that what a run finds depends on, and that the cache identity (the
     * config with what it includes, composer.lock, the Psalm version) does not cover
     *
     * @return list<string>
     */
    public static function getInputFiles(Config $config): array
    {
        $files = [
            Composer::getJsonFilePath($config->base_dir),
            ...array_values($config->getStubFiles()),
            ...array_values($config->getPreloadedStubFiles()),
            ...array_values($config->plugin_paths),
        ];

        if ($config->autoloader !== null) {
            $files[] = $config->autoloader;
        }

        if ($config->error_baseline !== null && $config->error_baseline !== '') {
            $files[] = Path::makeAbsolute($config->error_baseline, $config->base_dir);
        }

        return $files;
    }
}
