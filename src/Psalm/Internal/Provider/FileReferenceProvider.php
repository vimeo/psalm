<?php
namespace Psalm\Internal\Provider;

use function array_filter;
use function array_keys;
use function array_merge;
use function array_unique;
use function file_exists;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;

/**
 * @psalm-import-type FileMapType from \Psalm\Internal\Codebase\Analyzer
 *
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
     * A lookup table used for getting all the references to a class not inside a method
     * indexed by file
     *
     * @var array<string, array<string,bool>>
     */
    private static $nonmethod_references_to_classes = [];

    /**
     * A lookup table used for getting all the methods that reference a class
     *
     * @var array<string, array<string,bool>>
     */
    private static $method_references_to_classes = [];

    /**
     * A lookup table used for getting all the files that reference a class member
     *
     * @var array<string, array<string,bool>>
     */
    private static $file_references_to_class_members = [];

    /**
     * A lookup table used for getting all the files that reference a missing class member
     *
     * @var array<string, array<string,bool>>
     */
    private static $file_references_to_missing_class_members = [];

    /**
     * @var array<string, array<string, true>>
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
    private static $method_references_to_class_members = [];

    /**
     * @var array<string, array<string, bool>>
     */
    private static $method_references_to_missing_class_members = [];

    /**
     * @var array<string, array<string, bool>>
     */
    private static $references_to_mixed_member_names = [];

    /**
     * @var array<string, array<int, CodeLocation>>
     */
    private static $class_method_locations = [];

    /**
     * @var array<string, array<int, CodeLocation>>
     */
    private static $class_property_locations = [];

    /**
     * @var array<string, array<int, CodeLocation>>
     */
    private static $class_locations = [];

    /**
     * @var array<string, string>
     */
    private static $classlike_files = [];

    /**
     * @var array<string, array<string, int>>
     */
    private static $analyzed_methods = [];

    /**
     * @var array<string, array<int, IssueData>>
     */
    private static $issues = [];

    /**
     * @var array<string, FileMapType>
     */
    private static $file_maps = [];

    /**
     * @var array<string, array{int, int}>
     */
    private static $mixed_counts = [];

    /**
     * @var array<string, array<int, array<string, bool>>>
     */
    private static $method_param_uses = [];

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
     * @param lowercase-string $fq_class_name_lc
     * @return void
     */
    public function addNonMethodReferenceToClass(string $source_file, string $fq_class_name_lc)
    {
        self::$nonmethod_references_to_classes[$fq_class_name_lc][$source_file] = true;
    }

    /**
     * @return array<string, array<string,bool>>
     */
    public function getAllNonMethodReferencesToClasses()
    {
        return self::$nonmethod_references_to_classes;
    }

    /**
     * @param array<string, array<string,bool>> $references
     *
     * @return void
     */
    public function addNonMethodReferencesToClasses(array $references)
    {
        foreach ($references as $key => $reference) {
            if (isset(self::$nonmethod_references_to_classes[$key])) {
                self::$nonmethod_references_to_classes[$key] = array_merge(
                    $reference,
                    self::$nonmethod_references_to_classes[$key]
                );
            } else {
                self::$nonmethod_references_to_classes[$key] = $reference;
            }
        }
    }

    /**
     * @param array<string, string> $map
     */
    public function addClassLikeFiles(array $map) : void
    {
        self::$classlike_files += $map;
    }

    /**
     * @return void
     */
    public function addFileReferenceToClassMember(string $source_file, string $referenced_member_id)
    {
        self::$file_references_to_class_members[$referenced_member_id][$source_file] = true;
    }

    /**
     * @return void
     */
    public function addFileReferenceToMissingClassMember(string $source_file, string $referenced_member_id)
    {
        self::$file_references_to_missing_class_members[$referenced_member_id][$source_file] = true;
    }

    /**
     * @return array<string, array<string,bool>>
     */
    public function getAllFileReferencesToClassMembers()
    {
        return self::$file_references_to_class_members;
    }

    /**
     * @return array<string, array<string,bool>>
     */
    public function getAllFileReferencesToMissingClassMembers()
    {
        return self::$file_references_to_missing_class_members;
    }

    /**
     * @param array<string, array<string,bool>> $references
     *
     * @return void
     */
    public function addFileReferencesToClassMembers(array $references)
    {
        foreach ($references as $key => $reference) {
            if (isset(self::$file_references_to_class_members[$key])) {
                self::$file_references_to_class_members[$key] = array_merge(
                    $reference,
                    self::$file_references_to_class_members[$key]
                );
            } else {
                self::$file_references_to_class_members[$key] = $reference;
            }
        }
    }

    /**
     * @param array<string, array<string,bool>> $references
     *
     * @return void
     */
    public function addFileReferencesToMissingClassMembers(array $references)
    {
        foreach ($references as $key => $reference) {
            if (isset(self::$file_references_to_missing_class_members[$key])) {
                self::$file_references_to_missing_class_members[$key] = array_merge(
                    $reference,
                    self::$file_references_to_missing_class_members[$key]
                );
            } else {
                self::$file_references_to_missing_class_members[$key] = $reference;
            }
        }
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
     * @return void
     */
    public function addMethodParamUse(string $method_id, int $offset, string $referencing_method_id)
    {
        self::$method_param_uses[$method_id][$offset][$referencing_method_id] = true;
    }

    /**
     * @param   string $file
     *
     * @return  array<int, string>
     */
    private function calculateFilesReferencingFile(Codebase $codebase, $file)
    {
        $referenced_files = [];

        $file_classes = ClassLikeAnalyzer::getClassesForFile($codebase, $file);

        foreach ($file_classes as $file_class_lc => $_) {
            if (isset(self::$nonmethod_references_to_classes[$file_class_lc])) {
                $new_files = array_keys(self::$nonmethod_references_to_classes[$file_class_lc]);

                $referenced_files = array_merge(
                    $referenced_files,
                    $new_files
                );
            }

            if (isset(self::$method_references_to_classes[$file_class_lc])) {
                $new_referencing_methods = array_keys(self::$method_references_to_classes[$file_class_lc]);

                foreach ($new_referencing_methods as $new_referencing_method_id) {
                    $fq_class_name_lc = \explode('::', $new_referencing_method_id)[0];

                    try {
                        $referenced_files[] = $codebase->scanner->getClassLikeFilePath($fq_class_name_lc);
                    } catch (\UnexpectedValueException $e) {
                        if (isset(self::$classlike_files[$fq_class_name_lc])) {
                            $referenced_files[] = self::$classlike_files[$fq_class_name_lc];
                        }
                    }
                }
            }
        }

        return array_unique($referenced_files);
    }

    /**
     * @param   string $file
     *
     * @return  array<int, string>
     */
    private function calculateFilesInheritingFile(Codebase $codebase, $file)
    {
        $referenced_files = [];

        $file_classes = ClassLikeAnalyzer::getClassesForFile($codebase, $file);

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
    public function getAllMethodReferencesToClassMembers()
    {
        return self::$method_references_to_class_members;
    }

    /**
     * @return array<string, array<string, bool>>
     */
    public function getAllMethodReferencesToClasses()
    {
        return self::$method_references_to_classes;
    }

    /**
     * @return array<string, array<string, bool>>
     */
    public function getAllMethodReferencesToMissingClassMembers()
    {
        return self::$method_references_to_missing_class_members;
    }

    /**
     * @return array<string, array<string,bool>>
     */
    public function getAllReferencesToMixedMemberNames()
    {
        return self::$references_to_mixed_member_names;
    }

    /**
     * @return array<string, array<int, array<string, bool>>>
     */
    public function getAllMethodParamUses()
    {
        return self::$method_param_uses;
    }

    /**
     * @param bool $force_reload
     *
     * @return bool
     * @psalm-suppress MixedPropertyTypeCoercion
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

            $nonmethod_references_to_classes = $this->cache->getCachedNonMethodClassReferences();

            if ($nonmethod_references_to_classes === null) {
                return false;
            }

            self::$nonmethod_references_to_classes = $nonmethod_references_to_classes;

            $method_references_to_classes = $this->cache->getCachedMethodClassReferences();

            if ($method_references_to_classes === null) {
                return false;
            }

            self::$method_references_to_classes = $method_references_to_classes;

            $method_references_to_class_members = $this->cache->getCachedMethodMemberReferences();

            if ($method_references_to_class_members === null) {
                return false;
            }

            self::$method_references_to_class_members = $method_references_to_class_members;

            $method_references_to_missing_class_members = $this->cache->getCachedMethodMissingMemberReferences();

            if ($method_references_to_missing_class_members === null) {
                return false;
            }

            self::$method_references_to_missing_class_members = $method_references_to_missing_class_members;

            $file_references_to_class_members = $this->cache->getCachedFileMemberReferences();

            if ($file_references_to_class_members === null) {
                return false;
            }

            self::$file_references_to_class_members = $file_references_to_class_members;

            $file_references_to_missing_class_members = $this->cache->getCachedFileMissingMemberReferences();

            if ($file_references_to_missing_class_members === null) {
                return false;
            }

            self::$file_references_to_missing_class_members = $file_references_to_missing_class_members;

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
            $this->cache->setCachedMethodClassReferences(self::$method_references_to_classes);
            $this->cache->setCachedNonMethodClassReferences(self::$nonmethod_references_to_classes);
            $this->cache->setCachedMethodMemberReferences(self::$method_references_to_class_members);
            $this->cache->setCachedFileMemberReferences(self::$file_references_to_class_members);
            $this->cache->setCachedMethodMissingMemberReferences(self::$method_references_to_missing_class_members);
            $this->cache->setCachedFileMissingMemberReferences(self::$file_references_to_missing_class_members);
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
     * @param lowercase-string $fq_class_name_lc
     * @return void
     */
    public function addMethodReferenceToClass(string $calling_function_id, string $fq_class_name_lc)
    {
        if (!isset(self::$method_references_to_classes[$fq_class_name_lc])) {
            self::$method_references_to_classes[$fq_class_name_lc] = [$calling_function_id => true];
        } else {
            self::$method_references_to_classes[$fq_class_name_lc][$calling_function_id] = true;
        }
    }

    /**
     * @return void
     */
    public function addMethodReferenceToClassMember(string $calling_function_id, string $referenced_member_id)
    {
        if (!isset(self::$method_references_to_class_members[$referenced_member_id])) {
            self::$method_references_to_class_members[$referenced_member_id] = [$calling_function_id => true];
        } else {
            self::$method_references_to_class_members[$referenced_member_id][$calling_function_id] = true;
        }
    }

    /**
     * @return void
     */
    public function addMethodReferenceToMissingClassMember(string $calling_function_id, string $referenced_member_id)
    {
        if (!isset(self::$method_references_to_missing_class_members[$referenced_member_id])) {
            self::$method_references_to_missing_class_members[$referenced_member_id] = [$calling_function_id => true];
        } else {
            self::$method_references_to_missing_class_members[$referenced_member_id][$calling_function_id] = true;
        }
    }

    /**
     * @return void
     */
    public function addCallingLocationForClassMethod(CodeLocation $code_location, string $referenced_member_id)
    {
        if (!isset(self::$class_method_locations[$referenced_member_id])) {
            self::$class_method_locations[$referenced_member_id] = [$code_location];
        } else {
            self::$class_method_locations[$referenced_member_id][] = $code_location;
        }
    }

    /**
     * @return void
     */
    public function addCallingLocationForClassProperty(CodeLocation $code_location, string $referenced_property_id)
    {
        if (!isset(self::$class_property_locations[$referenced_property_id])) {
            self::$class_property_locations[$referenced_property_id] = [$code_location];
        } else {
            self::$class_property_locations[$referenced_property_id][] = $code_location;
        }
    }

    /**
     * @return void
     */
    public function addCallingLocationForClass(CodeLocation $code_location, string $referenced_class)
    {
        if (!isset(self::$class_locations[$referenced_class])) {
            self::$class_locations[$referenced_class] = [$code_location];
        } else {
            self::$class_locations[$referenced_class][] = $code_location;
        }
    }

    public function isClassMethodReferenced(string $method_id) : bool
    {
        return !empty(self::$file_references_to_class_members[$method_id])
            || !empty(self::$method_references_to_class_members[$method_id]);
    }

    public function isClassPropertyReferenced(string $property_id) : bool
    {
        return !empty(self::$file_references_to_class_members[$property_id])
            || !empty(self::$method_references_to_class_members[$property_id]);
    }

    public function isClassReferenced(string $fq_class_name_lc) : bool
    {
        return isset(self::$method_references_to_classes[$fq_class_name_lc])
            || isset(self::$nonmethod_references_to_classes[$fq_class_name_lc]);
    }

    public function isMethodParamUsed(string $method_id, int $offset) : bool
    {
        return !empty(self::$method_param_uses[$method_id][$offset]);
    }

    /**
     * @param array<string, array<string,bool>> $references
     *
     * @return void
     */
    public function setNonMethodReferencesToClasses(array $references)
    {
        self::$nonmethod_references_to_classes = $references;
    }

    /**
     * @return array<string, array<int, CodeLocation>>
     */
    public function getAllClassMethodLocations() : array
    {
        return self::$class_method_locations;
    }

    /**
     * @return array<string, array<int, CodeLocation>>
     */
    public function getAllClassPropertyLocations() : array
    {
        return self::$class_property_locations;
    }

    /**
     * @return array<string, array<int, CodeLocation>>
     */
    public function getAllClassLocations() : array
    {
        return self::$class_locations;
    }

    /**
     * @return array<int, CodeLocation>
     */
    public function getClassMethodLocations(string $method_id) : array
    {
        return self::$class_method_locations[$method_id] ?? [];
    }

    /**
     * @return array<int, CodeLocation>
     */
    public function getClassPropertyLocations(string $property_id) : array
    {
        return self::$class_property_locations[$property_id] ?? [];
    }

    /**
     * @return array<int, CodeLocation>
     */
    public function getClassLocations(string $fq_class_name_lc) : array
    {
        return self::$class_locations[$fq_class_name_lc] ?? [];
    }

    /**
     * @param array<string, array<string,bool>> $references
     *
     * @return void
     */
    public function addMethodReferencesToClassMembers(array $references)
    {
        foreach ($references as $key => $reference) {
            if (isset(self::$method_references_to_class_members[$key])) {
                self::$method_references_to_class_members[$key] = array_merge(
                    $reference,
                    self::$method_references_to_class_members[$key]
                );
            } else {
                self::$method_references_to_class_members[$key] = $reference;
            }
        }
    }

    /**
     * @param array<string, array<string,bool>> $references
     *
     * @return void
     */
    public function addMethodReferencesToClasses(array $references)
    {
        foreach ($references as $key => $reference) {
            if (isset(self::$method_references_to_classes[$key])) {
                self::$method_references_to_classes[$key] = array_merge(
                    $reference,
                    self::$method_references_to_classes[$key]
                );
            } else {
                self::$method_references_to_classes[$key] = $reference;
            }
        }
    }

    /**
     * @param array<string, array<string,bool>> $references
     *
     * @return void
     */
    public function addMethodReferencesToMissingClassMembers(array $references)
    {
        foreach ($references as $key => $reference) {
            if (isset(self::$method_references_to_missing_class_members[$key])) {
                self::$method_references_to_missing_class_members[$key] = array_merge(
                    $reference,
                    self::$method_references_to_missing_class_members[$key]
                );
            } else {
                self::$method_references_to_missing_class_members[$key] = $reference;
            }
        }
    }

    /**
     * @param array<string, array<int, array<string, bool>>> $references
     *
     * @return void
     */
    public function addMethodParamUses(array $references)
    {
        foreach ($references as $method_id => $method_param_uses) {
            if (isset(self::$method_param_uses[$method_id])) {
                foreach ($method_param_uses as $offset => $reference_map) {
                    if (isset(self::$method_param_uses[$method_id][$offset])) {
                        self::$method_param_uses[$method_id][$offset] = array_merge(
                            self::$method_param_uses[$method_id][$offset],
                            $reference_map
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
     *
     * @return void
     */
    public function setCallingMethodReferencesToClasses(array $references)
    {
        self::$method_references_to_classes = $references;
    }

    /**
     * @param array<string, array<string,bool>> $references
     *
     * @return void
     */
    public function setCallingMethodReferencesToClassMembers(array $references)
    {
        self::$method_references_to_class_members = $references;
    }

    /**
     * @param array<string, array<string,bool>> $references
     *
     * @return void
     */
    public function setCallingMethodReferencesToMissingClassMembers(array $references)
    {
        self::$method_references_to_missing_class_members = $references;
    }

    /**
     * @param array<string, array<string,bool>> $references
     *
     * @return void
     */
    public function setFileReferencesToClassMembers(array $references)
    {
        self::$file_references_to_class_members = $references;
    }

    /**
     * @param array<string, array<string,bool>> $references
     *
     * @return void
     */
    public function setFileReferencesToMissingClassMembers(array $references)
    {
        self::$file_references_to_missing_class_members = $references;
    }

    /**
     * @param array<string, array<string,bool>> $references
     *
     * @return void
     */
    public function setReferencesToMixedMemberNames(array $references)
    {
        self::$references_to_mixed_member_names = $references;
    }

    /**
     * @param array<string, array<int, array<string, bool>>> $references
     *
     * @return void
     */
    public function setMethodParamUses(array $references)
    {
        self::$method_param_uses = $references;
    }

    /**
     * @param array<string, array<int, CodeLocation>> $references
     *
     * @return void
     */
    public function addClassMethodLocations(array $references)
    {
        foreach ($references as $referenced_member_id => $locations) {
            if (isset(self::$class_method_locations[$referenced_member_id])) {
                self::$class_method_locations[$referenced_member_id] = array_merge(
                    self::$class_method_locations[$referenced_member_id],
                    $locations
                );
            } else {
                self::$class_method_locations[$referenced_member_id] = $locations;
            }
        }
    }

    /**
     * @param array<string, array<int, CodeLocation>> $references
     *
     * @return void
     */
    public function addClassPropertyLocations(array $references)
    {
        foreach ($references as $referenced_member_id => $locations) {
            if (isset(self::$class_property_locations[$referenced_member_id])) {
                self::$class_property_locations[$referenced_member_id] = array_merge(
                    self::$class_property_locations[$referenced_member_id],
                    $locations
                );
            } else {
                self::$class_property_locations[$referenced_member_id] = $locations;
            }
        }
    }

    /**
     * @param array<string, array<int, CodeLocation>> $references
     *
     * @return void
     */
    public function addClassLocations(array $references)
    {
        foreach ($references as $referenced_member_id => $locations) {
            if (isset(self::$class_locations[$referenced_member_id])) {
                self::$class_locations[$referenced_member_id] = array_merge(
                    self::$class_locations[$referenced_member_id],
                    $locations
                );
            } else {
                self::$class_locations[$referenced_member_id] = $locations;
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
     *
     * @return void
     */
    public function clearExistingIssuesForFile($file_path)
    {
        unset(self::$issues[$file_path]);
    }

    /**
     * @param string $file_path
     * @param IssueData $issue
     *
     * @return void
     */
    public function clearExistingFileMapsForFile($file_path)
    {
        unset(self::$file_maps[$file_path]);
    }

    /**
     * @param string $file_path
     * @param IssueData $issue
     *
     * @return void
     */
    public function addIssue($file_path, IssueData $issue)
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
     *
     * @return  void
     */
    public function setAnalyzedMethods(array $analyzed_methods)
    {
        self::$analyzed_methods = $analyzed_methods;
    }

    /**
     * @param array<string, FileMapType> $file_maps
     */
    public function setFileMaps(array $file_maps) : void
    {
        self::$file_maps = $file_maps;
    }

    /**
     * @return array<string, array{int, int}>
     */
    public function getTypeCoverage()
    {
        return self::$mixed_counts;
    }

    /**
     * @param array<string, array{int, int}> $mixed_counts
     *
     * @return  void
     */
    public function setTypeCoverage(array $mixed_counts)
    {
        self::$mixed_counts = array_merge(self::$mixed_counts, $mixed_counts);
    }

    /**
     * @return array<string, array<string, int>>
     */
    public function getAnalyzedMethods()
    {
        return self::$analyzed_methods;
    }

    /**
     * @return array<string, FileMapType>
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
        self::$files_inheriting_classes = [];
        self::$deleted_files = null;
        self::$file_references = [];
        self::$file_references_to_class_members = [];
        self::$method_references_to_class_members = [];
        self::$method_references_to_classes = [];
        self::$nonmethod_references_to_classes = [];
        self::$file_references_to_missing_class_members = [];
        self::$method_references_to_missing_class_members = [];
        self::$references_to_mixed_member_names = [];
        self::$class_method_locations = [];
        self::$class_property_locations = [];
        self::$class_locations = [];
        self::$analyzed_methods = [];
        self::$issues = [];
        self::$file_maps = [];
        self::$method_param_uses = [];
        self::$classlike_files = [];
        self::$mixed_counts = [];
    }
}
