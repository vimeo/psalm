<?php

namespace Psalm\Internal\Provider;

use Psalm\Config;
use Psalm\Internal\Cache;
use Psalm\Internal\Codebase\Analyzer;
use Psalm\Internal\Provider\Providers;
use RuntimeException;
use UnexpectedValueException;

use function file_exists;
use function file_put_contents;
use function is_array;
use function is_dir;
use function is_readable;
use function mkdir;

use const DIRECTORY_SEPARATOR;
use const LOCK_EX;

/**
 * Used to determine which files reference other files, necessary for using the --diff
 * option from the command line.
 *
 * @psalm-import-type FileMapType from Analyzer
 * @internal
 */
class FileReferenceCacheProvider
{
    private const REFERENCE_CACHE_NAME = 'references';
    private const CLASSLIKE_FILE_CACHE_NAME = 'classlike_files';
    private const NONMETHOD_CLASS_REFERENCE_CACHE_NAME = 'file_class_references';
    private const METHOD_CLASS_REFERENCE_CACHE_NAME = 'method_class_references';
    private const ANALYZED_METHODS_CACHE_NAME = 'analyzed_methods';
    private const CLASS_METHOD_CACHE_NAME = 'class_method_references';
    private const METHOD_DEPENDENCIES_CACHE_NAME = 'class_method_dependencies';
    private const CLASS_PROPERTY_CACHE_NAME = 'class_property_references';
    private const CLASS_METHOD_RETURN_CACHE_NAME = 'class_method_return_references';
    private const FILE_METHOD_RETURN_CACHE_NAME = 'file_method_return_references';
    private const FILE_CLASS_MEMBER_CACHE_NAME = 'file_class_member_references';
    private const FILE_CLASS_PROPERTY_CACHE_NAME = 'file_class_property_references';
    private const ISSUES_CACHE_NAME = 'issues';
    private const FILE_MAPS_CACHE_NAME = 'file_maps';
    private const TYPE_COVERAGE_CACHE_NAME = 'type_coverage';
    private const CONFIG_HASH_CACHE_NAME = 'config';
    private const METHOD_MISSING_MEMBER_CACHE_NAME = 'method_missing_member';
    private const FILE_MISSING_MEMBER_CACHE_NAME = 'file_missing_member';
    private const UNKNOWN_MEMBER_CACHE_NAME = 'unknown_member_references';
    private const METHOD_PARAM_USE_CACHE_NAME = 'method_param_uses';

    protected Config $config;
    protected Cache $cache;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->cache = new Cache($config);
    }

    public function hasConfigChanged(): bool
    {
        $new_hash = $this->config->computeHash();
        return $new_hash !== $this->getConfigHashCache();
    }

    public function getCachedFileReferences(): ?array
    {
        return $this->getCacheItem(self::REFERENCE_CACHE_NAME);
    }

    public function getCachedClassLikeFiles(): ?array
    {
        return $this->getCacheItem(self::CLASSLIKE_FILE_CACHE_NAME);
    }

    public function getCachedNonMethodClassReferences(): ?array
    {
        return $this->getCacheItem(self::NONMETHOD_CLASS_REFERENCE_CACHE_NAME);
    }

    public function getCachedMethodClassReferences(): ?array
    {
        return $this->getCacheItem(self::METHOD_CLASS_REFERENCE_CACHE_NAME);
    }

    public function getCachedMethodMemberReferences(): ?array
    {
        return $this->getCacheItem(self::CLASS_METHOD_CACHE_NAME);
    }

    public function getCachedMethodDependencies(): ?array
    {
        return $this->getCacheItem(self::METHOD_DEPENDENCIES_CACHE_NAME);
    }

    public function getCachedMethodPropertyReferences(): ?array
    {
        return $this->getCacheItem(self::CLASS_PROPERTY_CACHE_NAME);
    }

    public function getCachedMethodMethodReturnReferences(): ?array
    {
        return $this->getCacheItem(self::CLASS_METHOD_RETURN_CACHE_NAME);
    }

    public function getCachedMethodMissingMemberReferences(): ?array
    {
        return $this->getCacheItem(self::METHOD_MISSING_MEMBER_CACHE_NAME);
    }

    public function getCachedFileMemberReferences(): ?array
    {
        return $this->getCacheItem(self::FILE_CLASS_MEMBER_CACHE_NAME);
    }

    public function getCachedFilePropertyReferences(): ?array
    {
        return $this->getCacheItem(self::FILE_CLASS_PROPERTY_CACHE_NAME);
    }

    public function getCachedFileMethodReturnReferences(): ?array
    {
        return $this->getCacheItem(self::FILE_METHOD_RETURN_CACHE_NAME);
    }

    public function getCachedFileMissingMemberReferences(): ?array
    {
        return $this->getCacheItem(self::FILE_MISSING_MEMBER_CACHE_NAME);
    }

    public function getCachedMixedMemberNameReferences(): ?array
    {
        return $this->getCacheItem(self::UNKNOWN_MEMBER_CACHE_NAME);
    }

    public function getCachedMethodParamUses(): ?array
    {
        return $this->getCacheItem(self::METHOD_PARAM_USE_CACHE_NAME);
    }

    public function getCachedIssues(): ?array
    {
        return $this->getCacheItem(self::ISSUES_CACHE_NAME);
    }

    public function setCachedFileReferences(array $file_references): void
    {
        $this->saveCacheItem(self::REFERENCE_CACHE_NAME, $file_references);
    }

    public function setCachedClassLikeFiles(array $file_references): void
    {
        $this->saveCacheItem(self::CLASSLIKE_FILE_CACHE_NAME, $file_references);
    }

    public function setCachedNonMethodClassReferences(array $file_class_references): void
    {
        $this->saveCacheItem(self::NONMETHOD_CLASS_REFERENCE_CACHE_NAME, $file_class_references);
    }

    public function setCachedMethodClassReferences(array $method_class_references): void
    {
        $this->saveCacheItem(self::METHOD_CLASS_REFERENCE_CACHE_NAME, $method_class_references);
    }

    public function setCachedMethodMemberReferences(array $member_references): void
    {
        $this->saveCacheItem(self::CLASS_METHOD_CACHE_NAME, $member_references);
    }

    public function setCachedMethodDependencies(array $member_references): void
    {
        $this->saveCacheItem(self::METHOD_DEPENDENCIES_CACHE_NAME, $member_references);
    }

    public function setCachedMethodPropertyReferences(array $property_references): void
    {
        $this->saveCacheItem(self::CLASS_PROPERTY_CACHE_NAME, $property_references);
    }

    public function setCachedMethodMethodReturnReferences(array $method_return_references): void
    {
        $this->saveCacheItem(self::CLASS_METHOD_RETURN_CACHE_NAME, $method_return_references);
    }

    public function setCachedMethodMissingMemberReferences(array $member_references): void
    {
        $this->saveCacheItem(self::METHOD_MISSING_MEMBER_CACHE_NAME, $member_references);
    }

    public function setCachedFileMemberReferences(array $member_references): void
    {
        $this->saveCacheItem(self::FILE_CLASS_MEMBER_CACHE_NAME, $member_references);
    }

    public function setCachedFilePropertyReferences(array $property_references): void
    {
        $this->saveCacheItem(self::FILE_CLASS_PROPERTY_CACHE_NAME, $property_references);
    }

    public function setCachedFileMethodReturnReferences(array $method_return_references): void
    {
        $this->saveCacheItem(self::FILE_METHOD_RETURN_CACHE_NAME, $method_return_references);
    }

    public function setCachedFileMissingMemberReferences(array $member_references): void
    {
        $this->saveCacheItem(self::FILE_MISSING_MEMBER_CACHE_NAME, $member_references);
    }

    public function setCachedMixedMemberNameReferences(array $references): void
    {
        $this->saveCacheItem(self::UNKNOWN_MEMBER_CACHE_NAME, $references);
    }

    public function setCachedMethodParamUses(array $uses): void
    {
        $this->saveCacheItem(self::METHOD_PARAM_USE_CACHE_NAME, $uses);
    }

    public function setCachedIssues(array $issues): void
    {
        $this->saveCacheItem(self::ISSUES_CACHE_NAME, $issues);
    }

    /**
     * @return array<string, array<string, int>>|false
     */
    public function getAnalyzedMethodCache()
    {
        /** @var null|array<string, array<string, int>> $cache_item */
        $cache_item = $this->getCacheItem(self::ANALYZED_METHODS_CACHE_NAME);

        return $cache_item ?? false;
    }

    /**
     * @param array<string, array<string, int>> $analyzed_methods
     */
    public function setAnalyzedMethodCache(array $analyzed_methods): void
    {
        $this->saveCacheItem(self::ANALYZED_METHODS_CACHE_NAME, $analyzed_methods);
    }

    /**
     * @return array<string, FileMapType>|false
     */
    public function getFileMapCache()
    {
        /** @var array<string, FileMapType>|null $cache_item */
        $cache_item = $this->getCacheItem(self::FILE_MAPS_CACHE_NAME);

        return $cache_item ?? false;
    }

    /**
     * @param array<string, FileMapType> $file_maps
     */
    public function setFileMapCache(array $file_maps): void
    {
        $this->saveCacheItem(self::FILE_MAPS_CACHE_NAME, $file_maps);
    }

    //phpcs:disable -- Remove this once the phpstan phpdoc parser MR is merged
    /**
     * @return array<string, array{int, int}>|false
     */
    public function getTypeCoverage()
    {
        //phpcs:enable -- Remove this once the phpstan phpdoc parser MR is merged
        /** @var array<string, array{int, int}>|null $cache_item */
        $cache_item = $this->getCacheItem(self::TYPE_COVERAGE_CACHE_NAME);

        return $cache_item ?? false;
    }

    /**
     * @param array<string, array{int, int}> $mixed_counts
     */
    public function setTypeCoverage(array $mixed_counts): void
    {
        $this->saveCacheItem(self::TYPE_COVERAGE_CACHE_NAME, $mixed_counts);
    }

    /**
     * @return string|false
     */
    public function getConfigHashCache()
    {
        $cache_directory = $this->config->getCacheDirectory();

        $config_hash_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::CONFIG_HASH_CACHE_NAME;

        if ($cache_directory
            && file_exists($config_hash_cache_location)
        ) {
            return Providers::safeFileGetContents($config_hash_cache_location);
        }

        return false;
    }

    public function setConfigHashCache(string $hash = ''): void
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        if ($hash === '') {
            $hash = $this->config->computeHash();
        }

        if (!is_dir($cache_directory)) {
            try {
                if (mkdir($cache_directory, 0777, true) === false) {
                    // any other error than directory already exists/permissions issue
                    throw new RuntimeException(
                        'Failed to create ' . $cache_directory . ' cache directory for unknown reasons',
                    );
                }
            } catch (RuntimeException $e) {
                // Race condition (#4483)
                if (!is_dir($cache_directory)) {
                    // rethrow the error with default message
                    // it contains the reason why creation failed
                    throw $e;
                }
            }
        }

        $config_hash_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::CONFIG_HASH_CACHE_NAME;

        file_put_contents(
            $config_hash_cache_location,
            $hash,
            LOCK_EX,
        );
    }

    private function getCacheItem(string $type): ?array
    {
        $cache_directory = $this->config->getCacheDirectory();
        if (!$cache_directory) {
            return null;
        }

        $cache_location = $cache_directory . DIRECTORY_SEPARATOR . $type;
        if (!is_readable($cache_location)) {
            return null;
        }

        $cache_item = $this->cache->getItem($cache_location);
        if ($cache_item === null) {
            return null;
        } elseif (!is_array($cache_item)) {
            throw new UnexpectedValueException('The reference cache must be an array');
        }

        return $cache_item;
    }

    private function saveCacheItem(string $type, array $cache_item): void
    {
        $cache_directory = $this->config->getCacheDirectory();
        if (!$cache_directory) {
            return;
        }
        $cache_location = $cache_directory . DIRECTORY_SEPARATOR . $type;

        $this->cache->saveItem($cache_location, $cache_item);
    }
}
