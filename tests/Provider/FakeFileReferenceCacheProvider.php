<?php
namespace Psalm\Tests\Provider;

use Psalm\Config;

/**
 * Used to determine which files reference other files, necessary for using the --diff
 * option from the command line.
 */
class FakeFileReferenceCacheProvider extends \Psalm\Provider\FileReferenceCacheProvider
{
    /** @var ?array */
    private $cached_file_references;

    /** @var ?array */
    private $cached_method_references;

    /** @var ?array */
    private $cached_issues;

    /** @var array<string, array<string, bool>> */
    private $cached_correct_methods = [];

    public function __construct()
    {
    }

    /**
     * @return ?array
     *
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedTypeCoercion
     */
    public function getCachedFileReferences()
    {
        return $this->cached_file_references;
    }

    /**
     * @return ?array
     *
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedTypeCoercion
     */
    public function getCachedMethodReferences()
    {
        return $this->cached_method_references;
    }

    /**
     * @return ?array
     *
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedTypeCoercion
     */
    public function getCachedIssues()
    {
        return $this->cached_issues;
    }

    /**
     * @return void
     */
    public function setCachedFileReferences(array $file_references)
    {
        $this->cached_file_references = $file_references;
    }

    /**
     * @return void
     */
    public function setCachedMethodReferences(array $method_references)
    {
        $this->cached_method_references = $method_references;
    }

    /**
     * @return void
     */
    public function setCachedIssues(array $issues)
    {
        $this->cached_issues = $issues;
    }

    /**
     * @return array<string, array<string, bool>>
     */
    public function getCorrectMethodCache(Config $config)
    {
        return $this->cached_correct_methods;
    }

    /**
     * @param array<string, array<string, bool>> $correct_methods
     * @return void
     */
    public function setCorrectMethodCache(array $correct_methods)
    {
        $this->cached_correct_methods = $correct_methods;
    }
}
