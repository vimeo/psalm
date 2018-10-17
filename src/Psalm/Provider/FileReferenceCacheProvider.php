<?php
namespace Psalm\Provider;

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
class FileReferenceCacheProvider
{
    const REFERENCE_CACHE_NAME = 'references';
    const CORRECT_METHODS_CACHE_NAME = 'correct_methods';
    const CLASS_METHOD_CACHE_NAME = 'class_method_references';
    const ISSUES_CACHE_NAME = 'issues';
    const FILE_MAPS_CACHE_NAME = 'file_maps';

    /**
     * @var Config
     */
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @return ?array
     *
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedTypeCoercion
     */
    public function getCachedFileReferences()
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return null;
        }

        $reference_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::REFERENCE_CACHE_NAME;

        if (!is_readable($reference_cache_location)) {
            return null;
        }

        $reference_cache = unserialize((string) file_get_contents($reference_cache_location));

        if (!is_array($reference_cache)) {
            throw new \UnexpectedValueException('The reference cache must be an array');
        }

        return $reference_cache;
    }

    /**
     * @return ?array
     *
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedTypeCoercion
     */
    public function getCachedMethodReferences()
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return null;
        }

        $class_method_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::CLASS_METHOD_CACHE_NAME;

        if (!is_readable($class_method_cache_location)) {
            return null;
        }

        $class_method_reference_cache = unserialize((string) file_get_contents($class_method_cache_location));

        if (!is_array($class_method_reference_cache)) {
            throw new \UnexpectedValueException('The reference cache must be an array');
        }

        return $class_method_reference_cache;
    }

    /**
     * @return ?array
     *
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedTypeCoercion
     */
    public function getCachedIssues()
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return null;
        }

        $issues_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::ISSUES_CACHE_NAME;

        if (!is_readable($issues_cache_location)) {
            return null;
        }

        $issues_cache = unserialize((string) file_get_contents($issues_cache_location));

        if (!is_array($issues_cache)) {
            throw new \UnexpectedValueException('The reference cache must be an array');
        }

        return $issues_cache;
    }

    /**
     * @return void
     */
    public function setCachedFileReferences(array $file_references)
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $reference_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::REFERENCE_CACHE_NAME;

        file_put_contents($reference_cache_location, serialize($file_references));
    }

    /**
     * @return void
     */
    public function setCachedMethodReferences(array $method_references)
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $method_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::CLASS_METHOD_CACHE_NAME;

        file_put_contents($method_cache_location, serialize($method_references));
    }

    /**
     * @return void
     */
    public function setCachedIssues(array $issues)
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $issues_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::ISSUES_CACHE_NAME;

        file_put_contents($issues_cache_location, serialize($issues));
    }

    /**
     * @return array<string, array<string, int>>|false
     */
    public function getCorrectMethodCache()
    {
        $cache_directory = $this->config->getCacheDirectory();

        $correct_methods_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::CORRECT_METHODS_CACHE_NAME;

        if ($cache_directory
            && file_exists($correct_methods_cache_location)
            && filemtime($correct_methods_cache_location) > $this->config->modified_time
        ) {
            /** @var array<string, array<string, int>> */
            return unserialize(file_get_contents($correct_methods_cache_location));
        }

        return false;
    }

    /**
     * @param array<string, array<string, int>> $correct_methods
     * @return void
     */
    public function setCorrectMethodCache(array $correct_methods)
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
}
