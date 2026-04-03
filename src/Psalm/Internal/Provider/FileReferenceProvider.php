<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\Codebase\Analyzer;

use function array_filter;
use function array_keys;
use function array_merge;
use function array_unique;

/**
 * Used to determine which files reference other files, necessary for using the --diff
 * option from the command line.
 *
 * @psalm-import-type FileMapType from Analyzer
 * @internal
 */
final class FileReferenceProvider
{
    private bool $loaded_from_cache = false;

    /**
     * @var array<string, array<string, true>>
     */
    private static array $files_inheriting_classes = [];

    /**
     * A list of all files deleted since the last successful run
     *
     * @var array<int, string>|null
     */
    private static ?array $deleted_files = null;

    /**
     * A lookup table used for getting all the files referenced by a file
     *
     * @var array<string, array{a:array<int, string>, i:array<int, string>}>
     */
    private static array $file_references = [];

    /**
     * @var array<string, array<string, bool>>
     */
    private static array $method_dependencies = [];

    /**
     * @var array<string, array<string, bool>>
     */
    private static array $references_to_mixed_member_names = [];

    /**
     * @var array<string, string>
     */
    private static array $classlike_files = [];

    /**
     * @var array<string, array<string, int>>
     */
    private static array $analyzed_methods = [];

    /**
     * @var array<string, array<int, IssueData>>
     */
    private static array $issues = [];

    /**
     * @var array<string, FileMapType>
     */
    private static array $file_maps = [];

    /**
     * @var array<string, array{int, int}>
     */
    private static array $mixed_counts = [];

    /**
     * @var array<string, array<int, array<string, bool>>>
     */
    private static array $method_param_uses = [];

    /**
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly FileProvider $file_provider,
        public ?FileReferenceCacheProvider $cache = null,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function getDeletedReferencedFiles(): array
    {
        if (self::$deleted_files === null) {
            self::$deleted_files = array_filter(
                array_keys(self::$file_references),
                fn(string $file_name): bool => !$this->file_provider->fileExists($file_name),
            );
        }

        return self::$deleted_files;
    }

    /**
     * @param array<string, string> $map
     * @psalm-external-mutation-free
     */
    public function addClassLikeFiles(array $map): void
    {
        self::$classlike_files += $map;
    }

    /**
     * @psalm-external-mutation-free
     */
    public function addFileInheritanceToClass(string $source_file, string $fq_class_name_lc): void
    {
        self::$files_inheriting_classes[$fq_class_name_lc][$source_file] = true;
    }

    /**
     * @psalm-external-mutation-free
     */
    public function addMethodParamUse(string $method_id, int $offset, string $referencing_method_id): void
    {
        self::$method_param_uses[$method_id][$offset][$referencing_method_id] = true;
    }

    /**
     * @return array<int, string>
     * @psalm-external-mutation-free
     */
    private function calculateFilesReferencingFile(Codebase $codebase, string $file): array
    {
        return [];
    }

    /**
     * @return array<int, string>
     * @psalm-external-mutation-free
     */
    private function calculateFilesInheritingFile(Codebase $codebase, string $file): array
    {
        $referenced_files = [];

        $file_classes = ClassLikeAnalyzer::getClassesForFile($codebase, $file);

        foreach ($file_classes as $file_class_lc => $_) {
            if (isset(self::$files_inheriting_classes[$file_class_lc])) {
                $referenced_files = [
                    ...$referenced_files,
                    ...array_keys(self::$files_inheriting_classes[$file_class_lc]),
                ];
            }
        }

        return array_unique($referenced_files);
    }

    public function removeDeletedFilesFromReferences(): void
    {
        $deleted_files = $this->getDeletedReferencedFiles();

        if ($deleted_files) {
            foreach ($deleted_files as $file) {
                unset(self::$file_references[$file]);
            }

            if ($this->cache) {
                $this->cache->setCachedFileReferences(self::$file_references);
            }
        }
    }

    /**
     * @return array<int, string>
     * @psalm-external-mutation-free
     */
    public function getFilesReferencingFile(string $file): array
    {
        return self::$file_references[$file]['a'] ?? [];
    }

    /**
     * @return array<int, string>
     * @psalm-external-mutation-free
     */
    public function getFilesInheritingFromFile(string $file): array
    {
        return self::$file_references[$file]['i'] ?? [];
    }

    /**
     * @return array<string, array<string, bool>>
     * @psalm-external-mutation-free
     */
    public function getAllMethodDependencies(): array
    {
        return self::$method_dependencies;
    }

    /**
     * @return array<string, array<string,bool>>
     * @psalm-external-mutation-free
     */
    public function getAllReferencesToMixedMemberNames(): array
    {
        return self::$references_to_mixed_member_names;
    }

    /**
     * @return array<string, array<int, array<string, bool>>>
     * @psalm-external-mutation-free
     */
    public function getAllMethodParamUses(): array
    {
        return self::$method_param_uses;
    }

    /**
     * @psalm-suppress MixedPropertyTypeCoercion
     */
    public function loadReferenceCache(bool $force_reload = true): bool
    {
        if ($this->cache && (!$this->loaded_from_cache || $force_reload)) {
            $this->loaded_from_cache = true;

            $file_references = $this->cache->getCachedFileReferences();

            if ($file_references === null) {
                return false;
            }

            self::$file_references = $file_references;

            $method_dependencies = $this->cache->getCachedMethodDependencies();

            if ($method_dependencies === null) {
                return false;
            }

            self::$method_dependencies = $method_dependencies;

            $references_to_mixed_member_names = $this->cache->getCachedMixedMemberNameReferences();

            if ($references_to_mixed_member_names === null) {
                return false;
            }

            self::$references_to_mixed_member_names = $references_to_mixed_member_names;

            $analyzed_methods = $this->cache->getAnalyzedMethodCache();

            if ($analyzed_methods === false) {
                return false;
            }

            self::$analyzed_methods = $analyzed_methods;

            $issues = $this->cache->getCachedIssues();

            if ($issues === null) {
                return false;
            }

            self::$issues = $issues;

            $method_param_uses = $this->cache->getCachedMethodParamUses();

            if ($method_param_uses === null) {
                return false;
            }

            self::$method_param_uses = $method_param_uses;

            $mixed_counts = $this->cache->getTypeCoverage();

            if ($mixed_counts === false) {
                return false;
            }

            self::$mixed_counts = $mixed_counts;

            $classlike_files = $this->cache->getCachedClassLikeFiles();

            if ($classlike_files === null) {
                return false;
            }

            self::$classlike_files = $classlike_files;

            self::$file_maps = $this->cache->getFileMapCache() ?: [];

            return true;
        }

        return false;
    }

    /**
     * @param  array<string, string|bool>  $visited_files
     */
    public function updateReferenceCache(Codebase $codebase, array $visited_files): void
    {
        foreach ($visited_files as $file => $_) {
            $all_file_references = array_unique(
                array_merge(
                    self::$file_references[$file]['a'] ?? [],
                    $this->calculateFilesReferencingFile($codebase, $file),
                ),
            );

            $inheritance_references = array_unique(
                array_merge(
                    self::$file_references[$file]['i'] ?? [],
                    $this->calculateFilesInheritingFile($codebase, $file),
                ),
            );

            self::$file_references[$file] = [
                'a' => $all_file_references,
                'i' => $inheritance_references,
            ];
        }

        if ($this->cache) {
            $this->cache->setCachedFileReferences(self::$file_references);
            $this->cache->setCachedMethodDependencies(self::$method_dependencies);
            $this->cache->setCachedMixedMemberNameReferences(self::$references_to_mixed_member_names);
            $this->cache->setCachedMethodParamUses(self::$method_param_uses);
            $this->cache->setCachedIssues(self::$issues);
            $this->cache->setCachedClassLikeFiles(self::$classlike_files);
            $this->cache->setFileMapCache(self::$file_maps);
            $this->cache->setTypeCoverage(self::$mixed_counts);
            $this->cache->setAnalyzedMethodCache(self::$analyzed_methods);
        }
    }

    /**
     * @psalm-external-mutation-free
     */
    public function addMethodDependencyToClassMember(
        string $calling_function_id,
        string $referenced_member_id,
    ): void {
        if (!isset(self::$method_dependencies[$referenced_member_id])) {
            self::$method_dependencies[$referenced_member_id] = [$calling_function_id => true];
        } else {
            self::$method_dependencies[$referenced_member_id][$calling_function_id] = true;
        }
    }

    /**
     * @psalm-external-mutation-free
     */
    public function isMethodParamUsed(string $method_id, int $offset): bool
    {
        return !empty(self::$method_param_uses[$method_id][$offset]);
    }

    /**
     * @param array<string, array<string,bool>> $references
     * @psalm-external-mutation-free
     */
    public function addMethodDependencies(array $references): void
    {
        foreach ($references as $key => $reference) {
            if (isset(self::$method_dependencies[$key])) {
                self::$method_dependencies[$key] = array_merge(
                    $reference,
                    self::$method_dependencies[$key],
                );
            } else {
                self::$method_dependencies[$key] = $reference;
            }
        }
    }

    /**
     * @param array<string, array<int, array<string, bool>>> $references
     * @psalm-external-mutation-free
     */
    public function addMethodParamUses(array $references): void
    {
        foreach ($references as $method_id => $method_param_uses) {
            if (isset(self::$method_param_uses[$method_id])) {
                foreach ($method_param_uses as $offset => $reference_map) {
                    if (isset(self::$method_param_uses[$method_id][$offset])) {
                        self::$method_param_uses[$method_id][$offset] = array_merge(
                            self::$method_param_uses[$method_id][$offset],
                            $reference_map,
                        );
                    } else {
                        self::$method_param_uses[$method_id][$offset] = $reference_map;
                    }
                }
            } else {
                self::$method_param_uses[$method_id] = $method_param_uses;
            }
        }
    }

    /**
     * @param array<string, array<string,bool>> $references
     * @psalm-external-mutation-free
     */
    public function setMethodDependencies(array $references): void
    {
        self::$method_dependencies = $references;
    }

    /**
     * @param array<string, array<string,bool>> $references
     * @psalm-external-mutation-free
     */
    public function setReferencesToMixedMemberNames(array $references): void
    {
        self::$references_to_mixed_member_names = $references;
    }

    /**
     * @param array<string, array<int, array<string, bool>>> $references
     * @psalm-external-mutation-free
     */
    public function setMethodParamUses(array $references): void
    {
        self::$method_param_uses = $references;
    }

    /**
     * @return array<string, array<int, IssueData>>
     * @psalm-external-mutation-free
     */
    public function getExistingIssues(): array
    {
        return self::$issues;
    }

    /**
     * @psalm-external-mutation-free
     */
    public function clearExistingIssuesForFile(string $file_path): void
    {
        unset(self::$issues[$file_path]);
    }

    /**
     * @psalm-external-mutation-free
     */
    public function clearExistingFileMapsForFile(string $file_path): void
    {
        unset(self::$file_maps[$file_path]);
    }

    /**
     * @psalm-external-mutation-free
     */
    public function addIssue(string $file_path, IssueData $issue): void
    {
        // don’t save parse errors ever, as they're not responsive to AST diffing
        if ($issue->type === 'ParseError') {
            return;
        }

        if (!isset(self::$issues[$file_path])) {
            self::$issues[$file_path] = [$issue];
        } else {
            self::$issues[$file_path][] = $issue;
        }
    }

    /**
     * @param array<string, array<string, int>> $analyzed_methods
     * @psalm-external-mutation-free
     */
    public function setAnalyzedMethods(array $analyzed_methods): void
    {
        self::$analyzed_methods = $analyzed_methods;
    }

    /**
     * @param array<string, FileMapType> $file_maps
     * @psalm-external-mutation-free
     */
    public function setFileMaps(array $file_maps): void
    {
        self::$file_maps = $file_maps;
    }

    /**
     * @return array<string, array{int, int}>
     * @psalm-external-mutation-free
     */
    public function getTypeCoverage(): array
    {
        return self::$mixed_counts;
    }

    /**
     * @param array<string, array{int, int}> $mixed_counts
     * @psalm-external-mutation-free
     */
    public function setTypeCoverage(array $mixed_counts): void
    {
        self::$mixed_counts = [...self::$mixed_counts, ...$mixed_counts];
    }

    /**
     * @return array<string, array<string, int>>
     * @psalm-external-mutation-free
     */
    public function getAnalyzedMethods(): array
    {
        return self::$analyzed_methods;
    }

    /**
     * @return array<string, FileMapType>
     * @psalm-external-mutation-free
     */
    public function getFileMaps(): array
    {
        return self::$file_maps;
    }

    /**
     * @psalm-external-mutation-free
     */
    public static function clearCache(): void
    {
        self::$files_inheriting_classes = [];
        self::$deleted_files = null;
        self::$file_references = [];
        self::$method_dependencies = [];
        self::$references_to_mixed_member_names = [];
        self::$analyzed_methods = [];
        self::$issues = [];
        self::$file_maps = [];
        self::$method_param_uses = [];
        self::$classlike_files = [];
        self::$mixed_counts = [];
    }
}
