<?php
namespace Psalm\Provider;

use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\ProjectChecker;
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
 */
/**
 * Used to determine which files reference other files, necessary for using the --diff
 * option from the command line.
 */
class FileReferenceProvider
{
    const REFERENCE_CACHE_NAME = 'references';
    const CORRECT_METHODS_CACHE_NAME = 'correct_methods';
    const CLASS_METHOD_CACHE_NAME = 'class_method_references';
    const ISSUES_CACHE_NAME = 'issues';

    /**
     * A lookup table used for getting all the files that reference a class
     *
     * @var array<string, array<string,bool>>
     */
    protected static $file_references_to_class = [];

    /**
     * A lookup table used for getting all the files that reference any other file
     *
     * @var array<string,array<string,bool>>
     */
    protected static $referencing_files = [];

    /**
     * @var array<string, array<int,string>>
     */
    protected static $files_inheriting_classes = [];

    /**
     * A list of all files deleted since the last successful run
     *
     * @var array<int, string>|null
     */
    protected static $deleted_files = null;

    /**
     * A lookup table used for getting all the files referenced by a file
     *
     * @var array<string, array{a:array<int, string>, i:array<int, string>}>
     */
    protected static $file_references = [];

    /**
     * @var array<string, array<string, bool>>
     */
    private static $class_method_references = [];

    /**
     * @var array<string, array<int, IssueData>>
     */
    private static $issues = [];

    /**
     * @return array<string>
     */
    public static function getDeletedReferencedFiles()
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
    public static function addFileReferenceToClass($source_file, $fq_class_name_lc)
    {
        self::$referencing_files[$source_file] = true;
        self::$file_references_to_class[$fq_class_name_lc][$source_file] = true;
    }

    /**
     * @return array<string, array<string,bool>>
     */
    public static function getAllFileReferences()
    {
        return self::$file_references_to_class;
    }

    /**
     * @param array<string, array<string,bool>> $references
     * @psalm-suppress MixedTypeCoercion
     *
     * @return void
     */
    public static function addFileReferences(array $references)
    {
        self::$file_references_to_class = array_merge_recursive($references, self::$file_references_to_class);
    }

    /**
     * @param string $source_file
     * @param string $fq_class_name_lc
     *
     * @return void
     */
    public static function addFileInheritanceToClass($source_file, $fq_class_name_lc)
    {
        self::$files_inheriting_classes[$fq_class_name_lc][$source_file] = true;
    }

    /**
     * @param   string $file
     *
     * @return  array
     */
    public static function calculateFilesReferencingFile(ProjectChecker $project_checker, $file)
    {
        $referenced_files = [];

        $file_classes = ClassLikeChecker::getClassesForFile($project_checker, $file);

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
    public static function calculateFilesInheritingFile(ProjectChecker $project_checker, $file)
    {
        $referenced_files = [];

        $file_classes = ClassLikeChecker::getClassesForFile($project_checker, $file);

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
    public static function removeDeletedFilesFromReferences()
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        $deleted_files = self::getDeletedReferencedFiles();

        if ($deleted_files) {
            foreach ($deleted_files as $file) {
                unset(self::$file_references[$file]);
            }

            file_put_contents(
                $cache_directory . DIRECTORY_SEPARATOR . self::REFERENCE_CACHE_NAME,
                serialize(self::$file_references)
            );
        }
    }

    /**
     * @param  string $file
     *
     * @return array<string>
     */
    public static function getFilesReferencingFile($file)
    {
        return isset(self::$file_references[$file]['a']) ? self::$file_references[$file]['a'] : [];
    }

    /**
     * @param  string $file
     *
     * @return array<string>
     */
    public static function getFilesInheritingFromFile($file)
    {
        return isset(self::$file_references[$file]['i']) ? self::$file_references[$file]['i'] : [];
    }

    /**
     * @return array<string, array<string, bool>>
     */
    public static function getMethodsReferencing()
    {
        return self::$class_method_references;
    }

    /**
     * @return bool
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedTypeCoercion
     */
    public static function loadReferenceCache()
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if ($cache_directory) {
            $reference_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::REFERENCE_CACHE_NAME;

            if (!is_readable($reference_cache_location)) {
                return false;
            }

            $reference_cache = unserialize((string) file_get_contents($reference_cache_location));

            if (!is_array($reference_cache)) {
                throw new \UnexpectedValueException('The reference cache must be an array');
            }

            self::$file_references = $reference_cache;

            $class_method_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::CLASS_METHOD_CACHE_NAME;

            if (!is_readable($class_method_cache_location)) {
                return false;
            }

            $class_method_reference_cache = unserialize((string) file_get_contents($class_method_cache_location));

            if (!is_array($class_method_reference_cache)) {
                throw new \UnexpectedValueException('The reference cache must be an array');
            }

            self::$class_method_references = $class_method_reference_cache;

            $issues_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::ISSUES_CACHE_NAME;

            if (!is_readable($issues_cache_location)) {
                return false;
            }

            $issues_cache = unserialize((string) file_get_contents($issues_cache_location));

            if (!is_array($issues_cache)) {
                throw new \UnexpectedValueException('The reference cache must be an array');
            }

            self::$issues = $issues_cache;

            return true;
        }

        return false;
    }

    /**
     * @param  array<string, bool>  $visited_files
     *
     * @return void
     */
    public static function updateReferenceCache(ProjectChecker $project_checker, array $visited_files)
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if ($cache_directory
            && !$project_checker->cache_provider instanceof \Psalm\Provider\NoCache\NoParserCacheProvider
        ) {
            foreach ($visited_files as $file => $_) {
                $all_file_references = array_unique(
                    array_merge(
                        isset(self::$file_references[$file]['a']) ? self::$file_references[$file]['a'] : [],
                        FileReferenceProvider::calculateFilesReferencingFile($project_checker, $file)
                    )
                );

                $inheritance_references = array_unique(
                    array_merge(
                        isset(self::$file_references[$file]['i']) ? self::$file_references[$file]['i'] : [],
                        FileReferenceProvider::calculateFilesInheritingFile($project_checker, $file)
                    )
                );

                self::$file_references[$file] = [
                    'a' => $all_file_references,
                    'i' => $inheritance_references,
                ];
            }

            $reference_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::REFERENCE_CACHE_NAME;

            file_put_contents($reference_cache_location, serialize(self::$file_references));

            $method_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::CLASS_METHOD_CACHE_NAME;

            file_put_contents($method_cache_location, serialize(self::$class_method_references));

            $issues_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::ISSUES_CACHE_NAME;

            file_put_contents($issues_cache_location, serialize(self::$issues));
        }
    }

    /**
     * @return array<string, array<string, bool>>
     */
    public static function getCorrectMethodCache(Config $config)
    {
        $cache_directory = $config->getCacheDirectory();

        $correct_methods_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::CORRECT_METHODS_CACHE_NAME;

        if ($cache_directory
            && file_exists($correct_methods_cache_location)
            && filemtime($correct_methods_cache_location) > $config->modified_time
        ) {
            /** @var array<string, array<string, bool>> */
            return unserialize(file_get_contents($correct_methods_cache_location));
        }

        return [];
    }

    /**
     * @param array<string, array<string, bool>> $correct_methods
     * @return void
     */
    public static function setCorrectMethodCache(array $correct_methods)
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if ($cache_directory) {
            $correct_methods_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::CORRECT_METHODS_CACHE_NAME;

            file_put_contents(
                $correct_methods_cache_location,
                serialize($correct_methods)
            );
        }
    }

    /**
     * @return void
     */
    public static function addReferenceToClassMethod(string $calling_method_id, string $referenced_member_id)
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
    public static function getClassMethodReferences() : array
    {
        return self::$class_method_references;
    }

    /**
     * @param array<string, array<string,bool>> $references
     * @psalm-suppress MixedTypeCoercion
     *
     * @return void
     */
    public static function addClassMethodReferences(array $references)
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
    public static function getExistingIssues() : array
    {
        return self::$issues;
    }

    /**
     * @return void
     */
    public static function clearExistingIssues()
    {
        self::$issues = [];
    }

    /**
     * @return void
     */
    public static function addIssue(string $file_path, array $issue)
    {
        if (!isset(self::$issues[$file_path])) {
            self::$issues[$file_path] = [$issue];
        } else {
            self::$issues[$file_path][] = $issue;
        }
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
        self::$issues = [];
    }
}
