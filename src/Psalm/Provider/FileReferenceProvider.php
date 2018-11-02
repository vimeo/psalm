<?php
namespace Psalm\Provider;

use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\ProjectChecker;
use Psalm\Codebase;
use Psalm\Config;

/**
 * @psalm-type  IssueData = array{
 *     severity: string,
 *     line_from: int,
 *     line_to: int,
 *     type: string,
 *     message: string,
 *     file_name: string,
 *     file_path: string,
 *     snippet: string,
 *     from: int,
 *     to: int,
 *     snippet_from: int,
 *     snippet_to: int,
 *     column_from: int,
 *     column_to: int
 * }
 *
 * @psalm-type  TaggedCodeType = array<int, array{0: int, 1: string}>
 */
/**
 * Used to determine which files reference other files, necessary for using the --diff
 * option from the command line.
 */
class FileReferenceProvider
{
    /**
     * @var bool
     */
    private $loaded_from_cache = false;

    /**
     * A lookup table used for getting all the files that reference a class
     *
     * @var array<string, array<string,bool>>
     */
    private static $file_references_to_class = [];

    /**
     * A lookup table used for getting all the files that reference any other file
     *
     * @var array<string,array<string,bool>>
     */
    private static $referencing_files = [];

    /**
     * @var array<string, array<int,string>>
     */
    private static $files_inheriting_classes = [];

    /**
     * A list of all files deleted since the last successful run
     *
     * @var array<int, string>|null
     */
    private static $deleted_files = null;

    /**
     * A lookup table used for getting all the files referenced by a file
     *
     * @var array<string, array{a:array<int, string>, i:array<int, string>}>
     */
    private static $file_references = [];

    /**
     * @var array<string, array<string, bool>>
     */
    private static $class_method_references = [];

    /**
     * @var array<string, array<string, int>>
     */
    private static $analyzed_methods = [];

    /**
     * @var array<string, array<int, IssueData>>
     */
    private static $issues = [];

    /**
     * @var array<string, array{0: array<int, array{0: int, 1: string}>, 1: array<int, array{0: int, 1: string}>}>
     */
    private static $file_maps = [];

    /**
     * @var ?FileReferenceCacheProvider
     */
    public $cache;

    public function __construct(FileReferenceCacheProvider $cache = null)
    {
        $this->cache = $cache;
    }

    /**
     * @return array<string>
     */
    public function getDeletedReferencedFiles()
    {
        if (self::$deleted_files === null) {
            self::$deleted_files = array_filter(
                array_keys(self::$file_references),
                /**
                 * @param  string $file_name
                 *
                 * @return bool
                 */
                function ($file_name) {
                    return !file_exists($file_name);
                }
            );
        }

        return self::$deleted_files;
    }

    /**
     * @param string $source_file
     * @param string $fq_class_name_lc
     *
     * @return void
     */
    public function addFileReferenceToClass($source_file, $fq_class_name_lc)
    {
        self::$referencing_files[$source_file] = true;
        self::$file_references_to_class[$fq_class_name_lc][$source_file] = true;
    }

    /**
     * @return array<string, array<string,bool>>
     */
    public function getAllFileReferences()
    {
        return self::$file_references_to_class;
    }

    /**
     * @param array<string, array<string,bool>> $references
     * @psalm-suppress MixedTypeCoercion
     *
     * @return void
     */
    public function addFileReferences(array $references)
    {
        self::$file_references_to_class = array_merge_recursive($references, self::$file_references_to_class);
    }

    /**
     * @param string $source_file
     * @param string $fq_class_name_lc
     *
     * @return void
     */
    public function addFileInheritanceToClass($source_file, $fq_class_name_lc)
    {
        self::$files_inheriting_classes[$fq_class_name_lc][$source_file] = true;
    }

    /**
     * @param   string $file
     *
     * @return  array
     */
    private function calculateFilesReferencingFile(Codebase $codebase, $file)
    {
        $referenced_files = [];

        $file_classes = ClassLikeChecker::getClassesForFile($codebase, $file);

        foreach ($file_classes as $file_class_lc => $_) {
            if (isset(self::$file_references_to_class[$file_class_lc])) {
                $referenced_files = array_merge(
                    $referenced_files,
                    array_keys(self::$file_references_to_class[$file_class_lc])
                );
            }
        }

        return array_unique($referenced_files);
    }

    /**
     * @param   string $file
     *
     * @return  array
     */
    private function calculateFilesInheritingFile(Codebase $codebase, $file)
    {
        $referenced_files = [];

        $file_classes = ClassLikeChecker::getClassesForFile($codebase, $file);

        foreach ($file_classes as $file_class_lc => $_) {
            if (isset(self::$files_inheriting_classes[$file_class_lc])) {
                $referenced_files = array_merge(
                    $referenced_files,
                    array_keys(self::$files_inheriting_classes[$file_class_lc])
                );
            }
        }

        return array_unique($referenced_files);
    }

    /**
     * @return void
     */
    public function removeDeletedFilesFromReferences()
    {
        $deleted_files = self::getDeletedReferencedFiles();

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
     * @param  string $file
     *
     * @return array<string>
     */
    public function getFilesReferencingFile($file)
    {
        return isset(self::$file_references[$file]['a']) ? self::$file_references[$file]['a'] : [];
    }

    /**
     * @param  string $file
     *
     * @return array<string>
     */
    public function getFilesInheritingFromFile($file)
    {
        return isset(self::$file_references[$file]['i']) ? self::$file_references[$file]['i'] : [];
    }

    /**
     * @return array<string, array<string, bool>>
     */
    public function getMethodsReferencing()
    {
        return self::$class_method_references;
    }

    /**
     * @param bool $force_reload
     * @return bool
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedTypeCoercion
     */
    public function loadReferenceCache($force_reload = true)
    {
        if ($this->cache && (!$this->loaded_from_cache || $force_reload)) {
            $this->loaded_from_cache = true;

            $file_references = $this->cache->getCachedFileReferences();

            if ($file_references === null) {
                return false;
            }

            self::$file_references = $file_references;

            $class_method_references = $this->cache->getCachedMethodReferences();

            if ($class_method_references === null) {
                return false;
            }

            self::$class_method_references = $class_method_references;

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

            self::$file_maps = $this->cache->getFileMapCache() ?: [];

            return true;
        }

        return false;
    }

    /**
     * @param  array<string, string|bool>  $visited_files
     *
     * @return void
     */
    public function updateReferenceCache(Codebase $codebase, array $visited_files)
    {
        foreach ($visited_files as $file => $_) {
            $all_file_references = array_unique(
                array_merge(
                    isset(self::$file_references[$file]['a']) ? self::$file_references[$file]['a'] : [],
                    $this->calculateFilesReferencingFile($codebase, $file)
                )
            );

            $inheritance_references = array_unique(
                array_merge(
                    isset(self::$file_references[$file]['i']) ? self::$file_references[$file]['i'] : [],
                    $this->calculateFilesInheritingFile($codebase, $file)
                )
            );

            self::$file_references[$file] = [
                'a' => $all_file_references,
                'i' => $inheritance_references,
            ];
        }

        if ($this->cache) {
            $this->cache->setCachedFileReferences(self::$file_references);
            $this->cache->setCachedMethodReferences(self::$class_method_references);
            $this->cache->setCachedIssues(self::$issues);
            $this->cache->setFileMapCache(self::$file_maps);
            $this->cache->setAnalyzedMethodCache(self::$analyzed_methods);
        }
    }

    /**
     * @param string $calling_method_id
     * @param string $referenced_member_id
     * @return void
     */
    public function addReferenceToClassMethod($calling_method_id, $referenced_member_id)
    {
        if (!isset(self::$class_method_references[$referenced_member_id])) {
            self::$class_method_references[$referenced_member_id] = [$calling_method_id => true];
        } else {
            self::$class_method_references[$referenced_member_id][$calling_method_id] = true;
        }
    }

    /**
     * @return array<string, array<string,bool>>
     */
    public function getClassMethodReferences() : array
    {
        return self::$class_method_references;
    }

    /**
     * @param array<string, array<string,bool>> $references
     * @psalm-suppress MixedTypeCoercion
     *
     * @return void
     */
    public function addClassMethodReferences(array $references)
    {
        foreach ($references as $referenced_member_id => $calling_method_ids) {
            if (isset(self::$class_method_references[$referenced_member_id])) {
                self::$class_method_references[$referenced_member_id] = array_merge(
                    self::$class_method_references[$referenced_member_id],
                    $calling_method_ids
                );
            } else {
                self::$class_method_references[$referenced_member_id] = $calling_method_ids;
            }
        }
    }

    /**
     * @return array<string, array<int, IssueData>>
     */
    public function getExistingIssues() : array
    {
        return self::$issues;
    }

    /**
     * @param string $file_path
     * @return void
     */
    public function clearExistingIssuesForFile($file_path)
    {
        unset(self::$issues[$file_path]);
    }

    /**
     * @param string $file_path
     * @param IssueData $issue
     * @return void
     */
    public function clearExistingFileMapsForFile($file_path)
    {
        unset(self::$file_maps[$file_path]);
    }

    /**
     * @param string $file_path
     * @return void
     */
    public function addIssue($file_path, array $issue)
    {
        // don’t save parse errors ever, as they're not responsive to AST diffing
        if ($issue['type'] === 'ParseError') {
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
     * @return  void
     */
    public function setAnalyzedMethods(array $analyzed_methods)
    {
        self::$analyzed_methods = $analyzed_methods;
    }

    /**
     * @param array<string, array{0: TaggedCodeType, 1: TaggedCodeType}> $file_maps
     * @return  void
     */
    public function setFileMaps(array $file_maps)
    {
        self::$file_maps = $file_maps;
    }

    /**
     * @return array<string, array<string, int>>
     */
    public function getAnalyzedMethods()
    {
        return self::$analyzed_methods;
    }

    /**
     * @return array<string, array{0: TaggedCodeType, 1: TaggedCodeType}>
     */
    public function getFileMaps()
    {
        return self::$file_maps;
    }

    /**
     * @return void
     */
    public static function clearCache()
    {
        self::$file_references_to_class = [];
        self::$referencing_files = [];
        self::$files_inheriting_classes = [];
        self::$deleted_files = null;
        self::$file_references = [];
        self::$class_method_references = [];
        self::$analyzed_methods = [];
        self::$issues = [];
        self::$file_maps = [];
    }
}
