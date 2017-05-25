<?php
namespace Psalm\Provider;

use Psalm\Checker\ClassLikeChecker;
use Psalm\Config;

/**
 * Used to determine which files reference other files, necessary for using the --diff
 * option from the command line.
 */
class FileReferenceProvider
{
    const REFERENCE_CACHE_NAME = 'references';

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
     * @return array<string>
     */
    public static function getDeletedReferencedFiles()
    {
        if (self::$deleted_files === null) {
            self::$deleted_files = array_filter(
                array_keys(self::$file_references),
                /**
                 * @param  string $file_name
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
     * @param string $fq_class_name
     * @return void
     */
    public static function addFileReferenceToClass($source_file, $fq_class_name)
    {
        self::$referencing_files[$source_file] = true;
        self::$file_references_to_class[$fq_class_name][$source_file] = true;
    }

    /**
     * @param string $source_file
     * @param string $fq_class_name
     * @return void
     */
    public static function addFileInheritanceToClass($source_file, $fq_class_name)
    {
        self::$files_inheriting_classes[$fq_class_name][$source_file] = true;
    }

    /**
     * @param   string $file
     * @return  array
     */
    public static function calculateFilesReferencingFile($file)
    {
        $referenced_files = [];

        $file_classes = ClassLikeChecker::getClassesForFile($file);

        foreach ($file_classes as $file_class) {
            if (isset(self::$file_references_to_class[$file_class])) {
                $referenced_files = array_merge(
                    $referenced_files,
                    array_keys(self::$file_references_to_class[$file_class])
                );
            }
        }

        return array_unique($referenced_files);
    }

    /**
     * @param   string $file
     * @return  array
     */
    public static function calculateFilesInheritingFile($file)
    {
        $referenced_files = [];

        $file_classes = ClassLikeChecker::getClassesForFile($file);

        foreach ($file_classes as $file_class) {
            if (isset(self::$files_inheriting_classes[$file_class])) {
                $referenced_files = array_merge(
                    $referenced_files,
                    array_keys(self::$files_inheriting_classes[$file_class])
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
     * @return array<string>
     */
    public static function getFilesReferencingFile($file)
    {
        return isset(self::$file_references[$file]['a']) ? self::$file_references[$file]['a'] : [];
    }

    /**
     * @param  string $file
     * @return array<string>
     */
    public static function getFilesInheritingFromFile($file)
    {
        return isset(self::$file_references[$file]['i']) ? self::$file_references[$file]['i'] : [];
    }

    /**
     * @return bool
     * @psalm-suppress MixedAssignment
     * @psalm-suppress InvalidPropertyAssignment
     */
    public static function loadReferenceCache()
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if ($cache_directory) {
            $cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::REFERENCE_CACHE_NAME;

            if (is_readable($cache_location)) {
                $reference_cache = unserialize((string) file_get_contents($cache_location));

                if (!is_array($reference_cache)) {
                    throw new \UnexpectedValueException('The reference cache must be an array');
                }

                self::$file_references = $reference_cache;

                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, bool>  $visited_files
     * @return void
     */
    public static function updateReferenceCache(array $visited_files)
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if ($cache_directory) {
            $cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::REFERENCE_CACHE_NAME;

            foreach ($visited_files as $file => $_) {
                $all_file_references = array_unique(
                    array_merge(
                        isset(self::$file_references[$file]['a']) ? self::$file_references[$file]['a'] : [],
                        FileReferenceProvider::calculateFilesReferencingFile($file)
                    )
                );

                $inheritance_references = array_unique(
                    array_merge(
                        isset(self::$file_references[$file]['i']) ? self::$file_references[$file]['i'] : [],
                        FileReferenceProvider::calculateFilesInheritingFile($file)
                    )
                );

                self::$file_references[$file] = [
                    'a' => $all_file_references,
                    'i' => $inheritance_references
                ];
            }

            file_put_contents($cache_location, serialize(self::$file_references));
        }
    }
}
